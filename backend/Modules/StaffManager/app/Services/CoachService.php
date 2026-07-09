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
            $firstNameStr = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $data['first_name']));
            if (empty($firstNameStr)) {
                $firstNameStr = 'coach';
            }

            do {
                $randomNumber = rand(100, 999);
                $username = $firstNameStr . $randomNumber;
            } while (User::where('username', $username)->exists());


            // 4. Create User
            $user = User::create([
                'person_id' => $person->id,
                'username'  => $username,
                'password'  => Hash::make('password123'), // Default password
                'is_active' => true,
                'role'      => 'coach',
            ]);

            // 5. Create Staff (Role = coach)
            $staff = Staff::create([
                'person_id'       => $person->id,
                'role'            => 'coach',
                'employment_type' => $data['employment_type'] ?? 'fixed_salary',
                'base_salary'     => $data['base_salary'] ?? 0,
                'is_active'       => $data['is_active'] ?? true,
                'start_date'      => $data['start_date'] ?? null,
                'end_date'        => $data['end_date'] ?? null,
                'contract_type'   => $data['contract_type'] ?? null,
                'shift_type'      => $data['shift_type'] ?? null,
                'work_types'      => $data['work_types'] ?? null,
                'work_status'     => $data['work_status'] ?? 'active',
            ]);

            $staff->branches()->sync($data['branch_ids']);

            // 6. Generate 7 QR codes for this coach
            $this->qrCodeService->generateForPerson($person->id);

            // 7. Create Coach Detail
            CoachDetail::create([
                'staff_id'               => $staff->id,
                'experience_years'       => $data['experience_years'] ?? 0,
                'payment_type'           => $data['payment_type'] ?? null,
                'commission_type'        => $data['commission_type'] ?? null,
                'default_commission_rate' => $data['default_commission_rate'] ?? 0,
                'gym_type'               => $data['gym_type'] ?? null,
            ]);

            return $this->getSingleCoach($staff->id);
        });
    }

    /**
     * Get all coaches with optional filters.
     */
    public function getAllCoaches(array $filters = [])
    {
        $query = Staff::with(['coachDetail', 'person', 'activities', 'branches', 'user'])->where('role', 'coach');

        if (!empty($filters['branch_id'])) {
            $query->whereHas('branches', function ($q) use ($filters) {
                $q->where('staff_branches.branch_id', $filters['branch_id']);
            });
        }

        if (!empty($filters['activity_id'])) {
            $query->whereHas('activities', function ($q) use ($filters) {
                $q->where('activities.id', $filters['activity_id']);
            });
        }
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
            'fixed_salary_coaches' => (clone $query)->where('employment_type', 'fixed_salary')->count(),
            'commission_based_coaches' => (clone $query)->where('employment_type', 'commission_based')->count(),
            'hybrid_coaches' => (clone $query)->where('employment_type', 'hybrid')->count(),
        ];
    }

    /**
     * Get a single coach with all related data.
     */
    public function getSingleCoach($id)
    {
        return Staff::with(['coachDetail.certifications', 'activities', 'person', 'user', 'branches'])
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
                $personFillable = ['gender', 'age', 'dob'];
                $personData = array_intersect_key($data, array_flip($personFillable));
                
                if (isset($data['first_name']) || isset($data['last_name'])) {
                    $firstName = $data['first_name'] ?? explode(' ', $person->full_name)[0];
                    $lastName = $data['last_name'] ?? (explode(' ', $person->full_name)[1] ?? '');
                    $personData['full_name'] = trim($firstName . ' ' . $lastName);
                }

                if (isset($data['photo']) && $data['photo'] instanceof \Illuminate\Http\UploadedFile) {
                    $personData['photo_url'] = $data['photo']->store('people/photos', 'public');
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

            // Update Basic Info
            $basicFillable = ['base_salary', 'employment_type', 'shift_type', 'work_status', 'is_active', 'work_types'];
            $basicData = array_intersect_key($data, array_flip($basicFillable));
            if (!empty($basicData)) {
                $staff->update($basicData);
            }

            if (isset($data['branch_ids'])) {
                $staff->branches()->sync($data['branch_ids']);
            }

            // Update Details
            $coachDetail = $staff->coachDetail;
            if (!$coachDetail) {
                $coachDetail = new CoachDetail(['staff_id' => $staff->id]);
            }

            $detailFillable = [
                'bio', 'experience_years', 'payment_type', 
                'commission_type', 'default_commission_rate', 'gym_type'
            ];
            $detailsData = array_intersect_key($data, array_flip($detailFillable));

            if (!empty($detailsData) || !$coachDetail->exists) {
                foreach ($detailsData as $key => $value) {
                    $coachDetail->{$key} = $value;
                }
                $coachDetail->save();
            }

            return $this->getSingleCoach($staff->id);
        });
    }

    /**
     * Assign activities to a coach.
     */
    public function assignActivities($id, array $activityIds)
    {
        $staff = Staff::where('role', 'coach')->findOrFail($id);

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
     * Soft delete a coach.
     */
    public function deleteCoach($id)
    {
        $staff = Staff::where('role', 'coach')->findOrFail($id);
        $staff->delete(); // Soft delete

        // Optional: deactivate user
        if ($staff->user) {
            $staff->user->update(['is_active' => false]);
        }

        return true;
    }
}
