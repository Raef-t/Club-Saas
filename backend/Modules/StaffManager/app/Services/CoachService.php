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


            // 4. Create User
            $user = User::create([
                'person_id' => $person->id,
                'username'  => $username,
                'password'  => Hash::make('12345678'), // Default password
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

            $staff->contracts()->create([
                'employment_type' => $data['employment_type'] ?? 'fixed_salary',
                'base_salary'     => $data['base_salary'] ?? 0,
                'commission_type' => $commissionType,
                'commission_rate' => $commissionRate,
                'start_date'      => now()->toDateString(),
                'is_active'       => true,
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

            return $this->getSingleCoach($staff->id);
        });
    }

    /**
     * Get all coaches with optional filters.
     */
    public function getAllCoaches(array $filters = [])
    {
        $query = Staff::with(['coachDetail', 'person.contacts', 'activities', 'branches', 'user', 'activeContract', 'shifts.branchShift'])->where('role', 'coach');

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
        }

        if (!empty($filters['activity_id'])) {
            $query->whereHas('activities', function ($q) use ($filters) {
                $q->where('activities.id', $filters['activity_id']);
            });
        }

        $query->orderBy('id', 'desc');

        return $query->get();
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
        return Staff::with(['coachDetail', 'activities', 'person.contacts', 'user', 'branches', 'activeContract', 'shifts.branchShift'])
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

                if (!empty($personData)) {
                    $person->update($personData);
                }

                // Update Person Contact
                if (isset($data['phone_number']) || isset($data['country_code'])) {
                    $contact = $person->contacts()->where('name', 'Personal')->first();
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

            $basicFillable = ['work_status', 'start_date', 'end_date'];
            $basicData = array_intersect_key($data, array_flip($basicFillable));
            if (!empty($basicData)) {
                $staff->update($basicData);
            }

            if (isset($data['branch_ids'])) {
                $staff->branches()->sync($data['branch_ids']);
            }

            // Update Staff Contract
            if (isset($data['employment_type']) || isset($data['base_salary']) || isset($data['default_commission_rate']) || isset($data['commission_type']) || isset($data['payment_type'])) {
                $activeContract = $staff->activeContract;
                
                $commissionRate = $data['default_commission_rate'] ?? ($data['commission_rate'] ?? ($activeContract ? $activeContract->commission_rate : 0));
                // Use explicitly provided commission_type, otherwise fallback to activeContract's type, otherwise auto-determine
                $commissionType = $data['commission_type'] ?? ($activeContract ? $activeContract->commission_type : ($commissionRate > 0 ? 'percentage' : null));

                $contractData = [
                    'employment_type' => $data['employment_type'] ?? ($data['payment_type'] ?? ($activeContract ? $activeContract->employment_type : 'fixed_salary')),
                    'base_salary'     => $data['base_salary'] ?? ($activeContract ? $activeContract->base_salary : 0),
                    'commission_type' => $commissionType,
                    'commission_rate' => $commissionRate,
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
                $staff->activities()->sync($data['activity_ids']);
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
     * Update coach profile photo.
     */
    public function updateCoachPhoto($id, \Illuminate\Http\UploadedFile $photo)
    {
        return DB::transaction(function () use ($id, $photo) {
            $staff = Staff::where('role', 'coach')->findOrFail($id);
            $person = $staff->person;

            if (!$person) {
                throw new \Exception('Coach person record not found.');
            }

            // Delete old photo if exists
            if ($person->photo_url) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($person->photo_url);
            }

            $person->update([
                'photo_url' => $photo->store('people/photos', 'public'),
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

        return $query->latest()->get();
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
}
