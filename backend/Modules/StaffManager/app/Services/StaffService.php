<?php

namespace Modules\StaffManager\Services;

use Modules\StaffManager\Repositories\StaffRepositoryInterface;
use Modules\Core\Contracts\PersonSharedServiceInterface;
use Modules\Core\Contracts\BranchSharedServiceInterface;
use Illuminate\Support\Facades\DB;
use Exception;

class StaffService
{
    protected $staffRepository;
    protected $personService;
    protected $branchService;

    public function __construct(
        StaffRepositoryInterface $staffRepository,
        PersonSharedServiceInterface $personService,
        BranchSharedServiceInterface $branchService
    ) {
        $this->staffRepository = $staffRepository;
        $this->personService = $personService;
        $this->branchService = $branchService;
    }

    /**
     * Get all staff and coaches with resolved person/branch DTOs.
     */
    public function getAllStaff(array $filters = [])
    {
        $query = \Modules\StaffManager\Models\Staff::query();

        if (!empty($filters['branch_id'])) {
            $query->whereHas('branches', function ($q) use ($filters) {
                $q->where('staff_branches.branch_id', $filters['branch_id']);
            });
        }

        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        if (!empty($filters['gender']) || !empty($filters['search'])) {
            $peopleQuery = \Modules\Authentication\Models\Person::query();
            
            if (!empty($filters['gender'])) {
                $peopleQuery->where('gender', $filters['gender']);
            }
            
            if (!empty($filters['search'])) {
                $search = $filters['search'];
                $peopleQuery->where(function($sq) use ($search) {
                    $sq->where('full_name', 'like', "%{$search}%")
                       ->orWhereHas('contacts', function($cq) use ($search) {
                           $cq->where('phone_number', 'like', "%{$search}%");
                       })
                       ->orWhere('email', 'like', "%{$search}%")
                       ->orWhere('national_id', 'like', "%{$search}%");
                });
            }

            $personIds = $peopleQuery->pluck('id')->toArray();
            $query->whereIn('person_id', $personIds);
        }

        // Eager-load coach details and branches
        $query->with(['coachDetail', 'branches']);

        $perPage = $filters['per_page'] ?? 15;
        $staffMembers = $query->latest()->paginate($perPage);

        foreach ($staffMembers as $staff) {
            $this->attachSharedDTOs($staff);
        }

        return $staffMembers;
    }

    /**
     * Get staff member by ID with resolved person/branch DTOs.
     */
    public function getStaffById($id)
    {
        $staff = $this->staffRepository->find($id);
        $staff->load('coachDetail.certifications');
        return $this->attachSharedDTOs($staff);
    }

    /**
     * Register a new staff member.
     * If role is 'coach', automatically creates coach_details record.
     */
    public function onboardStaff(array $data)
    {
        return DB::transaction(function () use ($data) {
            // 1. Create the person profile in Authentication module
            $personDto = new \Modules\Core\DTOs\CreatePersonDTO(
                fullName: $data['full_name'],
                mobile1: $data['phone_number'],
                mobile1CountryCode: $data['country_code'] ?? null,
                gender: isset($data['gender']) ? \Modules\Core\Enums\Gender::tryFrom($data['gender']) : null,
                dob: $data['dob'] ?? null,
                type: ($data['role'] ?? 'staff') === 'coach' ? 'coach' : 'staff',
                email: $data['email'] ?? null,
                nationalId: $data['national_id'] ?? null,
                socialStatus: $data['social_status'] ?? null,
                address: $data['address'] ?? null,
                photoUrl: $data['photo_url'] ?? null,
                mobile2: $data['secondary_phone_number'] ?? null,
                mobile2CountryCode: $data['secondary_country_code'] ?? null,
                landline: $data['landline'] ?? null,
                emergencyContactName: $data['emergency_contact_name'] ?? null,
                emergencyContactPhone: $data['emergency_contact_phone'] ?? null,
                emergencyContactCountryCode: $data['emergency_contact_country_code'] ?? null,
                chronicDiseases: $data['chronic_diseases'] ?? null,
                childrenCount: isset($data['children_count']) ? (int)$data['children_count'] : null,
                howDidYouHear: $data['how_did_you_hear'] ?? null,
                notes: $data['notes'] ?? null
            );
            $person = $this->personService->createPerson($personDto);

            // 2. Create the staff record
            $staff = $this->staffRepository->create(array_merge($data, [
                'person_id' => $person->id,
            ]));
            
            if (!empty($data['branch_ids'])) {
                $staff->branches()->sync($data['branch_ids']);
            }

            // 3. If coach, create coach_details record
            if (($data['role'] ?? 'staff') === 'coach') {
                $staff->coachDetail()->create([
                    'specialization'          => $data['specialization'] ?? null,
                    'bio'                     => $data['bio'] ?? null,
                    'experience_years'        => $data['experience_years'] ?? 0,
                    'payment_type'            => $data['payment_type'] ?? null,
                    'commission_type'         => $data['commission_type'] ?? null,
                    'default_commission_rate' => $data['default_commission_rate'] ?? null,
                    'working_hours_per_week'  => $data['working_hours_per_week'] ?? null,
                    'gym_type'                => $data['gym_type'] ?? null,
                ]);

                // Create certifications if provided
                if (!empty($data['certifications']) && is_array($data['certifications'])) {
                    foreach ($data['certifications'] as $cert) {
                        $staff->coachDetail->certifications()->create([
                            'name'         => $cert['name'] ?? $cert,
                            'issuer'       => $cert['issuer'] ?? null,
                            'issue_date'   => $cert['issue_date'] ?? null,
                            'expiry_date'  => $cert['expiry_date'] ?? null,
                            'document_url' => $cert['document_url'] ?? null,
                        ]);
                    }
                }
            }

            // 4. Create active User Account so they can login to the Employee/Trainer App
            $username = $data['username'] ?? ('staff_' . $person->id . '_' . \Illuminate\Support\Str::random(4));
            $password = $data['password'] ?? 'password123';
            
            $user = \Modules\Authentication\Models\User::create([
                'username' => $username,
                'password' => \Illuminate\Support\Facades\Hash::make($password),
                'person_id' => $person->id,
                'is_active' => true,
            ]);

            // Assign matching role (admin, receptionist, coach, cleaner, manager)
            $roleName = $data['role'] ?? 'staff';
            $user->assignRole($roleName);

            // Expose credentials temporarily
            $staff->generated_username = $username;
            $staff->generated_password = $password;

            $staff->load('coachDetail.certifications');
            return $this->attachSharedDTOs($staff);
        });
    }

    /**
     * Update staff schedule
     */
    public function setStaffSchedule($staffId, array $shifts)
    {
        $staff = $this->staffRepository->find($staffId);
        
        return DB::transaction(function () use ($staff, $shifts) {
            // Remove old shifts
            $staff->shifts()->delete();

            // Add new shifts
            foreach ($shifts as $shift) {
                $staff->shifts()->create($shift);
            }

            $staff->load('shifts');
            return $this->attachSharedDTOs($staff);
        });
    }


    /**
     * Helper to resolve and attach Person and Branch DTOs
     */
    protected function attachSharedDTOs($staff)
    {
        if ($staff) {
            $staff->personDto = $staff->person_id ? $this->personService->getPersonById($staff->person_id) : null;
            $firstBranch = $staff->branches->first();
            $staff->branchDto = $firstBranch ? $this->branchService->getBranchById($firstBranch->id) : null;
        }
        return $staff;
    }

    /**
     * Update staff member data.
     * If staff is a coach, also updates coach_details.
     */
    public function updateStaff($id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $staff = $this->staffRepository->find($id);

            // Update Person data via Core contract if provided
            if ($staff->person_id) {
                $personData = array_filter([
                    'fullName' => $data['full_name'] ?? null,
                    'mobile1' => $data['phone_number'] ?? null,
                    'mobile1CountryCode' => $data['country_code'] ?? null,
                    'email' => $data['email'] ?? null,
                    'gender' => isset($data['gender']) ? \Modules\Core\Enums\Gender::tryFrom($data['gender']) : null,
                    'dob' => $data['dob'] ?? null,
                    'nationalId' => $data['national_id'] ?? null,
                    'socialStatus' => $data['social_status'] ?? null,
                    'address' => $data['address'] ?? null,
                    'photoUrl' => $data['photo_url'] ?? null,
                    'mobile2' => $data['secondary_phone_number'] ?? null,
                    'mobile2CountryCode' => $data['secondary_country_code'] ?? null,
                    'landline' => $data['landline'] ?? null,
                    'emergencyContactName' => $data['emergency_contact_name'] ?? null,
                    'emergencyContactPhone' => $data['emergency_contact_phone'] ?? null,
                    'emergencyContactCountryCode' => $data['emergency_contact_country_code'] ?? null,
                    'chronicDiseases' => $data['chronic_diseases'] ?? null,
                    'childrenCount' => isset($data['children_count']) ? (int)$data['children_count'] : null,
                    'howDidYouHear' => $data['how_did_you_hear'] ?? null,
                    'notes' => $data['notes'] ?? null,
                ], fn($value) => !is_null($value));

                if (!empty($personData)) {
                    $updateDto = new \Modules\Core\DTOs\UpdatePersonDTO(...$personData);
                    $this->personService->updatePerson($staff->person_id, $updateDto);
                }
            }

            // Update Staff record
            $staff->update($data);

            if (isset($data['branch_ids'])) {
                $staff->branches()->sync($data['branch_ids']);
            }

            // Update coach_details if this is a coach
            if ($staff->isCoach()) {
                $coachFields = array_filter([
                    'specialization'          => $data['specialization'] ?? null,
                    'bio'                     => $data['bio'] ?? null,
                    'experience_years'        => $data['experience_years'] ?? null,
                    'payment_type'            => $data['payment_type'] ?? null,
                    'commission_type'         => $data['commission_type'] ?? null,
                    'default_commission_rate' => $data['default_commission_rate'] ?? null,
                    'working_hours_per_week'  => $data['working_hours_per_week'] ?? null,
                    'gym_type'                => $data['gym_type'] ?? null,
                ], fn($value) => !is_null($value));

                if (!empty($coachFields)) {
                    $staff->coachDetail()->updateOrCreate(
                        ['staff_id' => $staff->id],
                        $coachFields
                    );
                }
            }

            $staff->load('coachDetail.certifications');
            return $this->attachSharedDTOs($staff->fresh());
        });
    }

    /**
     * Toggle staff active status.
     */
    public function toggleStatus($id)
    {
        $staff = $this->staffRepository->find($id);
        $staff->update(['is_active' => !$staff->is_active]);
        return $this->attachSharedDTOs($staff->fresh());
    }

}
