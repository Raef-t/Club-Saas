<?php

namespace Modules\StaffManager\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\StaffManager\Models\Staff;
use Modules\StaffManager\Models\CoachDetail;
use Modules\Authentication\Models\Person;
use Modules\Authentication\Models\PersonContact;
use Modules\Authentication\Models\User;
use Modules\Sports\Models\Activity;
use Modules\Authentication\Services\PersonQrCodeService;
use Spatie\Permission\Models\Role;

class CoachService
{
    public function __construct(
        protected PersonQrCodeService $qrCodeService
    ) {}

    /**
     * Create a new coach, along with person, user, and details in one transaction.
     */
    public function createCoach(array $data)
    {
        return DB::transaction(function () use ($data) {
            foreach ($data['branch_ids'] as $bId) {
                $branch = \Modules\ClubManager\Models\Branch::find($bId);
                if ($branch && $branch->gender_restriction !== 'mixed' && isset($data['gender']) && $branch->gender_restriction !== $data['gender']) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'gender' => 'لا يمكن إضافة هذا المدرب/ة في الفرع بسبب قيود الجنس الخاصة بالفرع.'
                    ]);
                }
            }

            // 1. Create Person
            $photoUrl = null;
            if (isset($data['photo']) && $data['photo'] instanceof \Illuminate\Http\UploadedFile) {
                $photoUrl = $data['photo']->store('people/photos', 'public');
            }

            $fullName = trim($data['first_name'] . ' ' . $data['last_name']);
            $person = Person::create([
                'full_name' => $fullName,
                'type'      => 'coach',
                'gender'    => $data['gender'] ?? null,
                'age'       => $data['age'] ?? null,
                'dob'       => $data['dob'] ?? null,
                'address'   => $data['address'] ?? null,
                'photo_url' => $photoUrl,
            ]);

            // 1.5 Create Person Contact
            if (!empty($data['phone_number'])) {
                PersonContact::create([
                    'person_id'    => $person->id,
                    'name'         => 'Personal',
                    'relation'     => 'self',
                    'phone_number' => $data['phone_number'],
                    'country_code' => $data['country_code'] ?? null,
                ]);
            }

            // 2. Generate unique username
            $username = \Modules\Authentication\Services\UsernameGeneratorService::generateForRole('coach');
            $password = '12345678';


            // 4. Create User
            $user = User::create([
                'person_id' => $person->id,
                'username'  => $username,
                'password'  => Hash::make($password), // Default password
                'is_active' => true,
                'role'      => 'coach',
            ]);

            // Assign Spatie Coach Role
            $coachRole = Role::firstOrCreate(['name' => 'coach', 'guard_name' => 'sanctum']);
            $user->assignRole($coachRole);

            // 5. Create Staff (Role = coach)
            $staff = Staff::create([
                'person_id'       => $person->id,
                'role'            => 'coach',
                'start_date'      => $data['start_date'] ?? now()->toDateString(),
                'end_date'        => $data['end_date'] ?? null,
                'work_status'     => $data['work_status'] ?? 'active',
            ]);

            // Create Staff Contract
            $commissionRate = $data['default_commission_rate'] ?? ($data['commission_rate'] ?? 0);
            $commissionType = $data['commission_type'] ?? ($commissionRate > 0 ? 'percentage' : null);
            $privateCommissionRate = $data['private_commission_rate'] ?? 0;

            $staff->contracts()->create([
                'employment_type'         => $data['employment_type'] ?? 'fixed_salary',
                'base_salary'             => $data['base_salary'] ?? 0,
                'commission_type'         => $commissionType,
                'commission_rate'         => $commissionRate,
                'private_commission_rate' => $privateCommissionRate,
                'start_date'              => now()->toDateString(),
                'is_active'               => true,
            ]);

            $staff->branches()->sync($data['branch_ids']);

            // 6. Generate single permanent QR code for this coach
            $this->qrCodeService->generateSingleForPerson($person->id);

            CoachDetail::create([
                'staff_id'               => $staff->id,
                'experience_years'       => $data['experience_years'] ?? 0,
                'gym_type'               => $data['gym_type'] ?? null,
                'work_types'             => $data['work_types'] ?? null,
            ]);

            // 8. Assign Activities if provided
            if (!empty($data['activity_ids']) && is_array($data['activity_ids'])) {
                $staff->activities()->syncWithoutDetaching($data['activity_ids']);
            }

            // 9. Assign Shifts if provided
            if (!empty($data['shifts']) && is_array($data['shifts'])) {
                foreach ($data['shifts'] as $branchShiftId) {
                    $staff->shifts()->create(['branch_shift_id' => $branchShiftId]);
                }
            }

            $coach = $this->getSingleCoach($staff->id);
            $coach->generated_username = $username;
            $coach->generated_password = $password;

            return $coach;
        });
    }

    /**
     * Get all coaches with optional filters.
     */
    public function getAllCoaches(array $filters = [])
    {
        $query = Staff::with([
            'coachDetail',
            'person.contacts',
            'activities.activityType',
            'branches',
            'user',
            'activeContract',
            'shifts.branchShift',
            'staffActivities.planActivities.plan.sessionTemplates',
            'staffActivities.planActivities.sessionTemplate',
        ])->where('role', 'coach');

        if (!empty($filters['branch_id'])) {
            $query->whereHas('branches', function ($q) use ($filters) {
                $q->where('staff_branches.branch_id', $filters['branch_id']);
            });
        }

        if (!empty($filters['gender'])) {
            $query->whereHas('person', function ($q) use ($filters) {
                $q->where('gender', $filters['gender']);
            });
        }

        if (!empty($filters['work_status'])) {
            $query->where('work_status', $filters['work_status']);
        } elseif (isset($filters['is_active'])) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        } elseif (
            (!empty($filters['per_page']) && $filters['per_page'] === 'all' && empty($filters['all_statuses'])) ||
            (!empty($filters['status']) && $filters['status'] === 'active') ||
            (!empty($filters['available']) && filter_var($filters['available'], FILTER_VALIDATE_BOOLEAN))
        ) {
            $query->where('is_active', true)->where('work_status', 'active');
        } elseif (!empty($filters['status']) && $filters['status'] === 'inactive') {
            $query->where(function ($q) {
                $q->where('is_active', false)->orWhere('work_status', '!=', 'active');
            });
        }

        if (!empty($filters['activity_id'])) {
            $query->whereHas('activities', function ($q) use ($filters) {
                $q->where('activities.id', $filters['activity_id']);
            });
        }

        if (!empty($filters['activity_type_id'])) {
            $this->applyActivityTypeFilter($query, $filters['activity_type_id']);
        } elseif (!empty($filters['activity_type'])) {
            $this->applyActivityTypeFilter($query, $filters['activity_type']);
        } elseif (!empty($filters['activity_type_name'])) {
            $this->applyActivityTypeFilter($query, $filters['activity_type_name']);
        }

        $dayParam = $filters['day_of_week'] ?? $filters['day'] ?? $filters['duty_day'] ?? $filters['day_name'] ?? null;
        $dayOfWeek = $this->normalizeDayOfWeek($dayParam);
        if ($dayOfWeek !== null) {
            $this->applyDayOfWeekFilter($query, $dayOfWeek, $filters);
        }

        if (!empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function ($q) use ($search) {
                $q->whereHas('person', function ($pq) use ($search) {
                    $pq->where('full_name', 'like', "%{$search}%")
                       ->orWhereHas('contacts', function ($cq) use ($search) {
                           $cq->where('phone_number', 'like', "%{$search}%");
                       });
                })->orWhereHas('user', function ($uq) use ($search) {
                    $uq->where('username', 'like', "%{$search}%")
                       ->orWhere('custom_username', 'like', "%{$search}%");
                });
            });
        } elseif (!empty($filters['name'])) {
            $name = trim((string) $filters['name']);
            $query->whereHas('person', function ($pq) use ($name) {
                $pq->where('full_name', 'like', "%{$name}%");
            });
        }

        $employmentType = $filters['employment_type'] ?? $filters['payment_type'] ?? null;
        if (!empty($employmentType)) {
            $normalizedType = match (trim((string) $employmentType)) {
                'fixed_salary', 'salary', 'راتب', 'راتب_ثابت', 'ثابت' => 'fixed_salary',
                'commission_based', 'commission', 'نسبة', 'عمولة' => 'commission_based',
                'hybrid', 'نسبة وراتب', 'نسبة_وراتب', 'راتب ونسبة', 'راتب_ونسبة', 'مختلط' => 'hybrid',
                default => trim((string) $employmentType),
            };

            $query->where(function ($q) use ($normalizedType) {
                $q->whereHas('activeContract', function ($cq) use ($normalizedType) {
                    $cq->where('employment_type', $normalizedType);
                })->orWhereHas('contracts', function ($cq) use ($normalizedType) {
                    $cq->where('employment_type', $normalizedType);
                });
            });
        }

        $query->orderBy('id', 'desc');

        if (!isset($filters['per_page']) || $filters['per_page'] === 'all' || (isset($filters['paginate']) && filter_var($filters['paginate'], FILTER_VALIDATE_BOOLEAN) === false) || (isset($filters['all']) && filter_var($filters['all'], FILTER_VALIDATE_BOOLEAN) === true)) {
            return $query->get();
        }

        $perPage = min(max((int)$filters['per_page'], 1), 100);
        return $query->paginate($perPage);
    }

    /**
     * Get statistics for coaches.
     */
    public function getStats(array $filters = [])
    {
        $query = Staff::where('role', 'coach');

        if (!empty($filters['branch_id'])) {
            $query->whereHas('branches', function ($q) use ($filters) {
                $q->where('staff_branches.branch_id', $filters['branch_id']);
            });
        }

        return [
            'total_coaches' => (clone $query)->count(),
            'active_coaches' => (clone $query)->where('is_active', true)->count(),
            'fixed_salary_coaches' => (clone $query)->whereHas('activeContract', fn($q) => $q->where('employment_type', 'fixed_salary'))->count(),
            'commission_based_coaches' => (clone $query)->whereHas('activeContract', fn($q) => $q->where('employment_type', 'commission_based'))->count(),
            'hybrid_coaches' => (clone $query)->whereHas('activeContract', fn($q) => $q->where('employment_type', 'hybrid'))->count(),
        ];
    }

    /**
     * Get a single coach with all related data.
     */
    public function getSingleCoach($id)
    {
        return Staff::with([
            'coachDetail',
            'activities.activityType',
            'person.contacts',
            'user',
            'branches',
            'activeContract',
            'shifts.branchShift',
            'staffActivities.planActivities.plan.sessionTemplates',
            'staffActivities.planActivities.sessionTemplate',
        ])
            ->where('role', 'coach')
            ->findOrFail($id);
    }

    /**
     * Update coach basic info and details.
     */
    public function updateCoach($id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $staff = Staff::where('role', 'coach')->findOrFail($id);

            // Update Person Info
            $person = $staff->person;
            if ($person) {
                $personFillable = ['gender', 'age', 'dob', 'address'];
                $personData = array_intersect_key($data, array_flip($personFillable));

                if (isset($data['first_name']) || isset($data['last_name'])) {
                    $firstName = $data['first_name'] ?? explode(' ', $person->full_name)[0];
                    $lastName = $data['last_name'] ?? (explode(' ', $person->full_name)[1] ?? '');
                    $personData['full_name'] = trim($firstName . ' ' . $lastName);
                }

                // Handle Photo Update or Deletion
                if (!empty($data['delete_photo']) || (array_key_exists('photo', $data) && empty($data['photo']) && !isset($data['photo_url']))) {
                    $oldPhoto = $person->getRawOriginal('photo_url') ?: $person->photo_url;
                    if ($oldPhoto) {
                        $relativePath = preg_replace('#^/?storage/#', '', $oldPhoto);
                        \Illuminate\Support\Facades\Storage::disk('public')->delete($relativePath);
                    }
                    $personData['photo_url'] = null;
                } elseif (isset($data['photo']) && $data['photo'] instanceof \Illuminate\Http\UploadedFile) {
                    $oldPhoto = $person->getRawOriginal('photo_url') ?: $person->photo_url;
                    if ($oldPhoto) {
                        $relativePath = preg_replace('#^/?storage/#', '', $oldPhoto);
                        \Illuminate\Support\Facades\Storage::disk('public')->delete($relativePath);
                    }
                    $personData['photo_url'] = $data['photo']->store('people/photos', 'public');
                }

                if (!empty($personData)) {
                    $person->update($personData);
                }

                // Update Person Contact
                if (isset($data['phone_number']) || isset($data['country_code'])) {
                    $contact = $person->contacts()->where(function ($q) {
                        $q->where('relation', 'self')
                          ->orWhere('name', 'Personal');
                    })->first() ?? $person->contacts()->first();

                    if ($contact) {
                        $contactData = [];
                        if (isset($data['phone_number'])) $contactData['phone_number'] = $data['phone_number'];
                        if (isset($data['country_code'])) $contactData['country_code'] = $data['country_code'];
                        $contact->update($contactData);
                    } elseif (!empty($data['phone_number'])) {
                        PersonContact::create([
                            'person_id'    => $person->id,
                            'name'         => 'Personal',
                            'relation'     => 'self',
                            'phone_number' => $data['phone_number'],
                            'country_code' => $data['country_code'] ?? null,
                        ]);
                    }
                }
            }

            $basicFillable = ['work_status', 'start_date', 'end_date', 'reason'];
            $basicData = array_intersect_key($data, array_flip($basicFillable));
            if (!empty($basicData)) {
                $staff->update($basicData);
            }

            if (isset($data['branch_ids'])) {
                $staff->branches()->sync($data['branch_ids']);
            }

            // Update Staff Contract
            if (isset($data['employment_type']) || isset($data['base_salary']) || isset($data['default_commission_rate']) || isset($data['private_commission_rate']) || isset($data['commission_type']) || isset($data['payment_type'])) {
                $activeContract = $staff->activeContract;
                
                $commissionRate = $data['default_commission_rate'] ?? ($data['commission_rate'] ?? ($activeContract ? $activeContract->commission_rate : 0));
                $privateCommissionRate = $data['private_commission_rate'] ?? ($activeContract ? $activeContract->private_commission_rate : 0);
                // Use explicitly provided commission_type, otherwise fallback to activeContract's type, otherwise auto-determine
                $commissionType = $data['commission_type'] ?? ($activeContract ? $activeContract->commission_type : ($commissionRate > 0 ? 'percentage' : null));

                $contractData = [
                    'employment_type'         => $data['employment_type'] ?? ($data['payment_type'] ?? ($activeContract ? $activeContract->employment_type : 'fixed_salary')),
                    'base_salary'             => $data['base_salary'] ?? ($activeContract ? $activeContract->base_salary : 0),
                    'commission_type'         => $commissionType,
                    'commission_rate'         => $commissionRate,
                    'private_commission_rate' => $privateCommissionRate,
                ];

                if ($activeContract) {
                    $activeContract->update($contractData);
                } else {
                    $contractData['start_date'] = now()->toDateString();
                    $contractData['is_active'] = true;
                    $staff->contracts()->create($contractData);
                }
            }

            // Update Details
            $coachDetail = $staff->coachDetail;
            if (!$coachDetail) {
                $coachDetail = new CoachDetail(['staff_id' => $staff->id]);
            }

            $detailFillable = [
                'bio',
                'experience_years',
                'gym_type',
                'work_types'
            ];
            $detailsData = array_intersect_key($data, array_flip($detailFillable));

            if (!empty($detailsData) || !$coachDetail->exists) {
                foreach ($detailsData as $key => $value) {
                    $coachDetail->{$key} = $value;
                }
                $coachDetail->save();
            }

            // Update Activities if provided
            if (isset($data['activity_ids']) && is_array($data['activity_ids'])) {
                $newActivityIds = array_map('intval', $data['activity_ids']);
                $currentActivities = $staff->activities()->get();
                $removedActivities = $currentActivities->whereNotIn('id', $newActivityIds);

                if ($removedActivities->isNotEmpty()) {
                    $removedPivotIds = $removedActivities->pluck('pivot.id')->filter()->values();

                    $conflictingPivotIds = DB::table('plan_activities')
                        ->join('subscription_plans', 'subscription_plans.id', '=', 'plan_activities.plan_id')
                        ->whereIn('plan_activities.staff_activity_id', $removedPivotIds)
                        ->whereNull('plan_activities.deleted_at')
                        ->whereNull('subscription_plans.deleted_at')
                        ->pluck('plan_activities.staff_activity_id')
                        ->unique()
                        ->all();

                    if (!empty($conflictingPivotIds)) {
                        $conflictNames = $removedActivities
                            ->whereIn('pivot.id', $conflictingPivotIds)
                            ->pluck('name')
                            ->unique()
                            ->values()
                            ->all();

                        $namesString = implode('، ', $conflictNames);

                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'activity_ids' => [
                                "لا يمكن فك ارتباط الأنشطة التالية: ({$namesString}) بهذا المدرب، نظراً لوجود فعاليات مرتبطة بها مسبقاً."
                            ]
                        ]);
                    }
                }

                if ($removedActivities->isNotEmpty()) {
                    DB::table('staff_activities')
                        ->where('staff_id', $staff->id)
                        ->whereIn('activity_id', $removedActivities->pluck('id')->all())
                        ->update(['deleted_at' => now()]);
                }

                foreach ($newActivityIds as $actId) {
                    $existing = DB::table('staff_activities')
                        ->where('staff_id', $staff->id)
                        ->where('activity_id', $actId)
                        ->first();

                    if ($existing) {
                        if ($existing->deleted_at !== null) {
                            DB::table('staff_activities')->where('id', $existing->id)->update(['deleted_at' => null]);
                        }
                    } else {
                        DB::table('staff_activities')->insert([
                            'staff_id'    => $staff->id,
                            'activity_id'  => $actId,
                            'created_at'   => now(),
                            'updated_at'   => now(),
                        ]);
                    }
                }
            }

            // Update Shifts if provided
            if (isset($data['shifts']) && is_array($data['shifts'])) {
                $staff->shifts()->delete();
                foreach ($data['shifts'] as $branchShiftId) {
                    $staff->shifts()->create(['branch_shift_id' => $branchShiftId]);
                }
            }

            return $this->getSingleCoach($staff->id);
        });
    }

    /**
     * Update coach profile photo (or remove it if photo is null).
     */
    public function updateCoachPhoto($id, ?\Illuminate\Http\UploadedFile $photo = null)
    {
        return DB::transaction(function () use ($id, $photo) {
            $staff = Staff::where('role', 'coach')->findOrFail($id);
            $person = $staff->person;

            if (!$person) {
                throw new \Exception('Coach person record not found.');
            }

            // Delete old photo if exists
            $oldPhoto = $person->getRawOriginal('photo_url') ?: $person->photo_url;
            if ($oldPhoto) {
                $relativePath = preg_replace('#^/?storage/#', '', $oldPhoto);
                \Illuminate\Support\Facades\Storage::disk('public')->delete($relativePath);
            }

            $photoUrl = $photo ? $photo->store('people/photos', 'public') : null;

            $person->update([
                'photo_url' => $photoUrl,
            ]);

            return $this->getSingleCoach($staff->id);
        });
    }

    /**
     * Delete a coach.
     *
     * Blocked if:
     *   - Coach has active/frozen subscribers (player_subscription_items via active subscriptions)
     *   - Coach has payslips (financial records)
     *
     * Contracts are kept as financial records and NOT deleted.
     *
     * Soft-deletes (records that lose value without the coach):
     * - coach_certifications    → soft-deleted (via coachDetail)
     * - coach_details           → soft-deleted
     * - staff_leaves            → soft-deleted
     * - staff_unavailabilities  → soft-deleted
     * - staff_shifts            → soft-deleted
     *
     * Other:
     * - staff_activities                    → detached from pivot table
     * - staff_contracts                     → KEPT (financial reference records)
     * - player_subscription_items.coach_id  → nullified manually (soft delete won't trigger DB onDelete)
     *
    /**
     * Delete a coach (Soft Delete). Requires confirmation string "delete".
     */
    public function deleteCoach($id, string $confirmation = ''): bool
    {
        if (strtolower(trim($confirmation)) !== 'delete') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'confirmation' => __('يجب إرسال كلمة "delete" لتأكيد عملية الحذف.')
            ]);
        }

        $staff = Staff::where('role', 'coach')->findOrFail($id);
        return (bool) $staff->delete();
    }

    /**
     * Get trashed coaches (role = coach)
     */
    public function getTrashedCoaches(array $filters = [])
    {
        $query = Staff::onlyTrashed()
            ->where('role', 'coach')
            ->with(['coachDetail.certifications', 'activities', 'person.contacts', 'user', 'branches', 'activeContract']);

        if (!empty($filters['branch_id'])) {
            $query->whereHas('branches', function ($q) use ($filters) {
                $q->where('staff_branches.branch_id', $filters['branch_id']);
            });
        }

        $query->latest();

        if (!isset($filters['per_page']) || $filters['per_page'] === 'all' || (isset($filters['paginate']) && filter_var($filters['paginate'], FILTER_VALIDATE_BOOLEAN) === false) || (isset($filters['all']) && filter_var($filters['all'], FILTER_VALIDATE_BOOLEAN) === true)) {
            return $query->get();
        }

        $perPage = min(max((int)$filters['per_page'], 1), 100);
        return $query->paginate($perPage);
    }

    /**
     * Restore a deleted coach
     */
    public function restoreCoach(int $id)
    {
        $staff = Staff::onlyTrashed()
            ->where('role', 'coach')
            ->findOrFail($id);
        $staff->restore();
        return $this->getSingleCoach($staff->id);
    }

    /**
     * Normalize day of week input (0-6 or string like 'friday', 'الجمعة', 'يوم الجمعة') to integer 0..6.
     */
    public function normalizeDayOfWeek(mixed $day): ?int
    {
        if ($day === null || $day === '') {
            return null;
        }

        if (is_numeric($day)) {
            $intVal = (int) $day;
            if ($intVal >= 0 && $intVal <= 6) {
                return $intVal;
            }
            if ($intVal === 7) {
                return 0; // ISO Sunday
            }
            return null;
        }

        $clean = mb_strtolower(trim((string) $day));
        $clean = preg_replace('/^يوم\s+/u', '', $clean);

        return match ($clean) {
            '0', 'sunday', 'sun', 'الأحد', 'الاحد', 'أحد', 'احد' => 0,
            '1', 'monday', 'mon', 'الإثنين', 'الاثنين', 'إثنين', 'اثنين', 'تنين' => 1,
            '2', 'tuesday', 'tue', 'الثلاثاء', 'تلاتاء', 'ثلاثاء' => 2,
            '3', 'wednesday', 'wed', 'الأربعاء', 'الاربعاء', 'أربعاء', 'اربعاء' => 3,
            '4', 'thursday', 'thu', 'الخميس', 'خميس' => 4,
            '5', 'friday', 'fri', 'الجمعة', 'الجمعه', 'جمعة', 'جمعه' => 5,
            '6', 'saturday', 'sat', 'السبت', 'سبت' => 6,
            default => null,
        };
    }

    /**
     * Apply activity type filter to coach query.
     */
    protected function applyActivityTypeFilter($query, mixed $activityType): void
    {
        if (empty($activityType)) {
            return;
        }

        if (is_array($activityType)) {
            $query->whereHas('activities', function ($q) use ($activityType) {
                $q->whereIn('activities.activity_type_id', $activityType);
            });
            return;
        }

        if (is_numeric($activityType)) {
            $query->whereHas('activities', function ($q) use ($activityType) {
                $q->where('activities.activity_type_id', (int) $activityType);
            });
            return;
        }

        if (is_string($activityType) && str_contains($activityType, ',')) {
            $ids = array_filter(array_map('trim', explode(',', $activityType)), 'is_numeric');
            if (!empty($ids)) {
                $query->whereHas('activities', function ($q) use ($ids) {
                    $q->whereIn('activities.activity_type_id', $ids);
                });
                return;
            }
        }

        $typeStr = mb_strtolower(trim((string) $activityType));

        $privateKeywords = ['الخاص', 'خاص', 'تدريب خاص', 'تدريب_خاص', 'private', 'private_training', 'private_equipment'];
        if (in_array($typeStr, $privateKeywords, true)) {
            $query->whereHas('activities', function ($q) {
                $q->where(function ($aq) {
                    $aq->where('activities.is_private_equipment', true)
                       ->orWhereHas('activityType', function ($tq) {
                           $tq->where('is_private_equipment', true)
                              ->orWhere('name', 'like', '%خاص%')
                              ->orWhere('name', 'like', '%private%');
                       });
                });
            });
            return;
        }

        $groupKeywords = ['الحصة الجماعية', 'الحصة_الجماعية', 'حصة جماعية', 'حصة_جماعية', 'حصة جماعيه', 'جماعي', 'جماعية', 'group', 'group_session', 'group_sessions', 'session', 'sessions'];
        if (in_array($typeStr, $groupKeywords, true)) {
            $query->whereHas('activities', function ($q) {
                $q->whereHas('activityType', function ($tq) {
                    $tq->where('is_session_based', true)
                       ->orWhere('name', 'like', '%جماع%')
                       ->orWhere('name', 'like', '%حصة%')
                       ->orWhere('name', 'like', '%session%')
                       ->orWhere('name', 'like', '%group%');
                });
            });
            return;
        }

        $generalKeywords = ['العام', 'عام', 'تدريب عام', 'تدريب_عام', 'general', 'public', 'general_training'];
        if (in_array($typeStr, $generalKeywords, true)) {
            $query->whereHas('activities', function ($q) {
                $q->whereHas('activityType', function ($tq) {
                    $tq->where('has_shifts', true)
                       ->orWhere('name', 'like', '%عام%')
                       ->orWhere('name', 'like', '%general%')
                       ->orWhere('name', 'like', '%public%');
                });
            });
            return;
        }

        $stripped = preg_replace('/^ال/u', '', $typeStr);
        $query->whereHas('activities.activityType', function ($tq) use ($typeStr, $stripped) {
            $tq->where('name', 'like', "%{$typeStr}%")
               ->orWhere('name', 'like', "%{$stripped}%")
               ->orWhere('id', $typeStr);
        });
    }

    /**
     * Apply day of week filter to coach query.
     */
    protected function applyDayOfWeekFilter($query, int $dayOfWeek, array $filters = []): void
    {
        $activityId = $filters['activity_id'] ?? null;
        $activityType = $filters['activity_type_id'] ?? $filters['activity_type'] ?? $filters['activity_type_name'] ?? null;

        $query->where(function ($dayQuery) use ($dayOfWeek, $activityId, $activityType) {
            // 1. Session templates on this day_of_week
            $dayQuery->whereHas('staffActivities', function ($saq) use ($dayOfWeek, $activityId, $activityType) {
                if (!empty($activityId)) {
                    $saq->where('activity_id', $activityId);
                }
                if (!empty($activityType)) {
                    $saq->whereHas('activity', function ($aq) use ($activityType) {
                        if (is_numeric($activityType)) {
                            $aq->where('activity_type_id', (int) $activityType);
                        } else {
                            $typeStr = mb_strtolower(trim((string) $activityType));
                            $stripped = preg_replace('/^ال/u', '', $typeStr);
                            $aq->whereHas('activityType', function ($atq) use ($typeStr, $stripped) {
                                $atq->where('name', 'like', "%{$typeStr}%")
                                    ->orWhere('name', 'like', "%{$stripped}%");
                            });
                        }
                    });
                }

                $saq->whereHas('planActivities', function ($paq) use ($dayOfWeek) {
                    $paq->where(function ($sub) use ($dayOfWeek) {
                        $sub->whereHas('plan.sessionTemplates', function ($stq) use ($dayOfWeek) {
                            $stq->where('day_of_week', $dayOfWeek)->where('is_active', true);
                        });

                        if (\Illuminate\Support\Facades\Schema::hasColumn('plan_activities', 'session_template_id')) {
                            $sub->orWhereHas('sessionTemplate', function ($stq) use ($dayOfWeek) {
                                $stq->where('day_of_week', $dayOfWeek)->where('is_active', true);
                            });
                        }
                    });
                });
            });

            // 2. OR coach has duty through shifts in a branch that does not have a weekly holiday on that day
            $isRestrictedToSessionType = false;
            if (!empty($activityType) && is_string($activityType)) {
                $typeStr = mb_strtolower(trim($activityType));
                if (in_array($typeStr, ['حصة جماعية', 'حصة_جماعية', 'حصة جماعيه', 'جماعي', 'جماعية', 'group', 'group_session', 'session'], true)) {
                    $isRestrictedToSessionType = true;
                }
            }

            if (!$isRestrictedToSessionType) {
                $dayQuery->orWhere(function ($shiftQ) use ($dayOfWeek, $activityId) {
                    $shiftQ->whereHas('shifts.branchShift.branch', function ($bq) use ($dayOfWeek) {
                        $bq->whereDoesntHave('holidays', function ($hq) use ($dayOfWeek) {
                            $hq->where('type', 'weekly')->where('day_of_week', $dayOfWeek);
                        });
                    });

                    if (!empty($activityId)) {
                        $shiftQ->whereHas('activities', function ($aq) use ($activityId) {
                            $aq->where('activities.id', $activityId);
                        });
                    }
                });
            }
        });
    }
}
