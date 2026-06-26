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
            $query->where('branch_id', $filters['branch_id']);
        }

        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        if (!empty($filters['gender']) || !empty($filters['search'])) {
            $peopleQuery = \Illuminate\Support\Facades\DB::table('people');
            
            if (!empty($filters['gender'])) {
                $peopleQuery->where('gender', $filters['gender']);
            }
            
            if (!empty($filters['search'])) {
                $search = $filters['search'];
                $peopleQuery->where(function($sq) use ($search) {
                    $sq->where('full_name', 'like', "%{$search}%")
                       ->orWhere('mobile_1', 'like', "%{$search}%")
                       ->orWhere('email', 'like', "%{$search}%")
                       ->orWhere('national_id', 'like', "%{$search}%");
                });
            }

            $personIds = $peopleQuery->pluck('id')->toArray();
            $query->whereIn('person_id', $personIds);
        }

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
        return $this->attachSharedDTOs($staff);
    }

    /**
     * Register a new staff member
     */
    public function onboardStaff(array $data)
    {
        return DB::transaction(function () use ($data) {
            // 1. Create the person profile in Authentication module
            $personDto = new \Modules\Core\DTOs\CreatePersonDTO(
                fullName: $data['full_name'],
                mobile1: $data['mobile_1'],
                gender: isset($data['gender']) ? \Modules\Core\Enums\Gender::tryFrom($data['gender']) : null,
                dob: $data['dob'] ?? null,
                type: 'staff',
                email: $data['email'] ?? null,
                nationalId: $data['national_id'] ?? null,
                socialStatus: $data['social_status'] ?? null,
                address: $data['address'] ?? null,
                photoUrl: $data['photo_url'] ?? null,
                mobile2: $data['mobile_2'] ?? null,
                landline: $data['landline'] ?? null,
                emergencyContactName: $data['emergency_contact_name'] ?? null,
                emergencyContactPhone: $data['emergency_contact_phone'] ?? null,
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

            // 3. Create active User Account so they can login to the Employee/Trainer App
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
            $staff->person = $staff->person_id ? $this->personService->getPersonById($staff->person_id) : null;
            $staff->branch = $staff->branch_id ? $this->branchService->getBranchById($staff->branch_id) : null;
        }
        return $staff;
    }

    /**
     * Update staff member data.
     */
    public function updateStaff($id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $staff = $this->staffRepository->find($id);

            // Update Person data via Core contract if provided
            if ($staff->person_id) {
                $personData = array_filter([
                    'fullName' => $data['full_name'] ?? null,
                    'mobile1' => $data['mobile_1'] ?? null,
                    'email' => $data['email'] ?? null,
                    'gender' => isset($data['gender']) ? \Modules\Core\Enums\Gender::tryFrom($data['gender']) : null,
                    'dob' => $data['dob'] ?? null,
                    'nationalId' => $data['national_id'] ?? null,
                    'socialStatus' => $data['social_status'] ?? null,
                    'address' => $data['address'] ?? null,
                    'photoUrl' => $data['photo_url'] ?? null,
                    'mobile2' => $data['mobile_2'] ?? null,
                    'landline' => $data['landline'] ?? null,
                    'emergencyContactName' => $data['emergency_contact_name'] ?? null,
                    'emergencyContactPhone' => $data['emergency_contact_phone'] ?? null,
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
