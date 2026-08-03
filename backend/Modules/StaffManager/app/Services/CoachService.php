<?php

namespace Modules\StaffManager\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\StaffManager\Models\Staff;
use Modules\StaffManager\Models\CoachDetail;
use Modules\StaffManager\Models\CoachCertification;
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
                'national_id'=> $data['national_id'] ?? null,
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
            $username = 'Coa-' . $person->id . '-' . strtolower(Str::random(6));


            // 4. Create User
            $user = User::create([
                'person_id' => $person->id,
                'username'  => $username,
                'password'  => Hash::make('password123'), // Default password
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
                'is_active'       => $data['is_active'] ?? true,
                'start_date'      => $data['start_date'] ?? null,
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

            // 6. Generate 7 QR codes for this coach
            $this->qrCodeService->generateForPerson($person->id);

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
        $query = Staff::with(['coachDetail', 'person.contacts', 'activities', 'branches', 'user', 'activeContract'])->where('role', 'coach');

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
        return Staff::with(['coachDetail.certifications', 'activities', 'person.contacts', 'user', 'branches', 'activeContract'])
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
                $personFillable = ['gender', 'age', 'dob', 'national_id', 'address'];
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
                if (isset($data['phone_number'])) {
                    $contact = $person->contacts()->where('name', 'Personal')->first();
                    if ($contact) {
                        $contact->update([
                            'phone_number' => $data['phone_number'],
                            'country_code' => $data['country_code'] ?? $contact->country_code,
                        ]);
                    } else {
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

            $basicFillable = ['work_status', 'is_active', 'start_date', 'end_date'];
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

    public function assignActivities($id, array $activityIds)
    {
        $staff = Staff::where('role', 'coach')->with('activeContract')->findOrFail($id);
        $employmentType = $staff->activeContract?->employment_type ?? 'fixed_salary';

        $activities = \Modules\Sports\Models\Activity::with('activityType')->whereIn('id', $activityIds)->get();

        foreach ($activities as $activity) {
            $isSessionBased = (bool) ($activity->activityType?->is_session_based ?? false);

            if ($employmentType === 'fixed_salary' && $isSessionBased) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'activity_ids' => [__('لا يمكن الربط بسبب عدم توافق طبيعة عمل المدرب مع نوع الفعالية.')],
                ]);
            }

            if ($employmentType === 'commission_based' && !$isSessionBased) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'activity_ids' => [__('لا يمكن الربط بسبب عدم توافق طبيعة عمل المدرب مع نوع الفعالية.')],
                ]);
            }
        }

        // syncWithoutDetaching avoids duplicates and keeps existing associations
        $staff->activities()->syncWithoutDetaching($activityIds);

        return $staff->fresh(['activities']);
    }

    /**
     * Remove an activity from a coach.
     */
    public function removeActivity($id, $activityId)
    {
        $staff = Staff::where('role', 'coach')->findOrFail($id);
        $staff->activities()->detach($activityId);

        return true;
    }

    /**
     * Upload and save a certification for a coach.
     */
    public function uploadCertification($id, array $data)
    {
        $staff = Staff::where('role', 'coach')->findOrFail($id);
        $coachDetail = $staff->coachDetail;

        if (!$coachDetail) {
            throw new \Exception("Coach details not found.");
        }

        $documentUrl = null;
        if (isset($data['file']) && $data['file'] instanceof \Illuminate\Http\UploadedFile) {
            $path = $data['file']->store('coach_certifications', 'public');
            $documentUrl = $path;
        } elseif (isset($data['document_url'])) {
            $documentUrl = $data['document_url'];
        }

        if (!$documentUrl) {
            throw new \Exception("A document file or URL is required.");
        }

        $certification = CoachCertification::create([
            'coach_detail_id' => $coachDetail->id,
            'name'            => $data['name'],
            'issuer'          => $data['issuer'] ?? null,
            'issue_date'      => $data['issue_date'] ?? null,
            'expiry_date'     => $data['expiry_date'] ?? null,
            'document_url'    => $documentUrl,
        ]);

        return $certification;
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
     * Delete a coach (Soft Delete).
     */
    public function deleteCoach($id)
    {
        return DB::transaction(function () use ($id) {
            $staff = Staff::where('role', 'coach')->findOrFail($id);
            $deleted = $staff->delete();

            if ($staff->user) {
                $staff->user->update(['is_active' => false]);
            }

            return $deleted;
        });
    }

    /**
     * Restore a soft-deleted coach.
     */
    public function restoreCoach($id)
    {
        return DB::transaction(function () use ($id) {
            $staff = Staff::onlyTrashed()->where('role', 'coach')->findOrFail($id);
            $restored = $staff->restore();

            if ($staff->user) {
                $staff->user->update(['is_active' => true]);
            }

            return $restored;
        });
    }
}
