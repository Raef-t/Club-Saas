<?php

namespace Modules\StaffManager\Services;

use Modules\StaffManager\Repositories\StaffRepositoryInterface;
use Modules\Core\Contracts\PersonSharedServiceInterface;
use Modules\Core\Contracts\BranchSharedServiceInterface;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
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

        if (!empty($filters['gender'])) {
            $query->whereHas('person', function ($q) use ($filters) {
                $q->where('gender', $filters['gender']);
            });
        }

        // Eager-load coach details, active contract, branches, user and shifts
        $query->with(['coachDetail', 'activeContract', 'branches', 'user', 'shifts.branchShift']);

        $staffMembers = $query->latest()->get();

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
        $staff->load(['coachDetail.certifications', 'activeContract', 'user', 'shifts.branchShift']);
        return $this->attachSharedDTOs($staff);
    }

    /**
     * Register a new staff member.
     * If role is 'coach', automatically creates coach_details record.
     */
    public function onboardStaff(array $data)
    {
        return DB::transaction(function () use ($data) {
            // Handle Photo Upload
            $photoUrl = $data['photo_url'] ?? null;
            if (isset($data['photo']) && $data['photo'] instanceof \Illuminate\Http\UploadedFile) {
                $photoUrl = $data['photo']->store('people/photos', 'public');
            }

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
                photoUrl: $photoUrl,
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

            // Create Staff Contract
            $commissionRate = $data['default_commission_rate'] ?? ($data['commission_rate'] ?? 0);
            $commissionType = $data['commission_type'] ?? ($commissionRate > 0 ? 'percentage' : null);

            $staff->contracts()->create([
                'employment_type' => $data['employment_type'] ?? 'fixed_salary',
                'base_salary' => $data['base_salary'] ?? 0,
                'commission_type' => $commissionType,
                'commission_rate' => $commissionRate,
                'start_date' => now()->toDateString(),
                'is_active' => true,
            ]);

            if (!empty($data['branch_ids'])) {
                $staff->branches()->sync($data['branch_ids']);
            }

            if (isset($data['shifts']) && is_array($data['shifts'])) {
                foreach ($data['shifts'] as $shiftId) {
                    $staff->shifts()->create(['branch_shift_id' => $shiftId]);
                }
            }

            // 3. If coach, create coach_details record
            if (($data['role'] ?? 'staff') === 'coach') {
                $staff->coachDetail()->create([
                    'specialization'          => $data['specialization'] ?? null,
                    'bio'                     => $data['bio'] ?? null,
                    'experience_years'        => $data['experience_years'] ?? 0,
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
            $roleName = $data['role'] ?? 'staff';
            $prefix = ucfirst(substr(str_replace('_', '', $roleName), 0, 3)) . '-';
            $username = $data['username'] ?? ($prefix . $person->id . '-' . strtolower(\Illuminate\Support\Str::random(6)));
            $password = $data['password'] ?? 'password123';

            $user = \Modules\Authentication\Models\User::create([
                'username' => $username,
                'password' => \Illuminate\Support\Facades\Hash::make($password),
                'person_id' => $person->id,
                'is_active' => true,
            ]);

            // Assign matching role (admin, receptionist, cleaner, manager, staff, etc.)
            $roleName = $data['role'] ?? 'staff';
            $spatieRoleName = $roleName === 'receptionist' ? 'reception' : $roleName;
            $spatieRole = Role::firstOrCreate(['name' => $spatieRoleName, 'guard_name' => 'sanctum']);
            $user->assignRole($spatieRole);

            // Expose credentials temporarily
            $staff->generated_username = $username;
            $staff->generated_password = $password;

            $staff->load(['coachDetail.certifications', 'shifts.branchShift']);
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
            foreach ($shifts as $branchShiftId) {
                $staff->shifts()->create(['branch_shift_id' => $branchShiftId]);
            }

            $staff->load('shifts.branchShift');
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

            // Update Staff record (fillable will ignore removed fields)
            $staff->update($data);

            // Handle Contract Updates
            $activeContract = $staff->activeContract;
            
            $newEmploymentType = $data['employment_type'] ?? ($activeContract ? $activeContract->employment_type : 'fixed_salary');
            $newBaseSalary = $data['base_salary'] ?? ($activeContract ? $activeContract->base_salary : 0);
            
            $newCommissionRate = $data['default_commission_rate'] ?? ($data['commission_rate'] ?? ($activeContract ? $activeContract->commission_rate : 0));
            $newCommissionType = $data['commission_type'] ?? ($activeContract ? $activeContract->commission_type : null);
            if (empty($newCommissionType) && $newCommissionRate > 0) {
                $newCommissionType = 'percentage';
            }

            // Only create new contract if financial data actually changed
            if (!$activeContract || 
                $activeContract->employment_type !== $newEmploymentType ||
                (float)$activeContract->base_salary !== (float)$newBaseSalary ||
                $activeContract->commission_type !== $newCommissionType ||
                (float)$activeContract->commission_rate !== (float)$newCommissionRate
            ) {
                if ($activeContract) {
                    $activeContract->update([
                        'end_date' => now()->toDateString(),
                        'is_active' => false
                    ]);
                }

                $staff->contracts()->create([
                    'employment_type' => $newEmploymentType,
                    'base_salary' => $newBaseSalary,
                    'commission_type' => $newCommissionType,
                    'commission_rate' => $newCommissionRate,
                    'start_date' => now()->toDateString(),
                    'is_active' => true,
                ]);
            }

            if (isset($data['branch_ids'])) {
                $staff->branches()->sync($data['branch_ids']);
            }

            if (isset($data['shifts']) && is_array($data['shifts'])) {
                $staff->shifts()->delete();
                foreach ($data['shifts'] as $shiftId) {
                    $staff->shifts()->create(['branch_shift_id' => $shiftId]);
                }
            }

            // Update coach_details if this is a coach
            if ($staff->isCoach()) {
                $coachFields = array_filter([
                    'specialization'          => $data['specialization'] ?? null,
                    'bio'                     => $data['bio'] ?? null,
                    'experience_years'        => $data['experience_years'] ?? null,
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

            $staff->load(['coachDetail.certifications', 'activeContract', 'shifts.branchShift']);
            return $this->attachSharedDTOs($staff->fresh());
        });
    }

    /**
     * Update staff profile photo.
     */
    public function updateStaffPhoto($id, \Illuminate\Http\UploadedFile $photo)
    {
        return DB::transaction(function () use ($id, $photo) {
            $staff = $this->staffRepository->find($id);
            $person = \Modules\Authentication\Models\Person::find($staff->person_id);

            if (!$person) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Staff person record not found.');
            }

            // Delete old photo if exists
            if ($person->photo_url) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($person->photo_url);
            }

            $person->update([
                'photo_url' => $photo->store('people/photos', 'public'),
            ]);

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

    /**
     * Soft delete a staff member. Requires confirmation string "delete".
     */
    public function deleteStaff(int $id, string $confirmation = ''): bool
    {
        if (strtolower(trim($confirmation)) !== 'delete') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'confirmation' => __('يجب إرسال كلمة "delete" لتأكيد عملية الحذف.')
            ]);
        }

        $staff = \Modules\StaffManager\Models\Staff::findOrFail($id);
        return (bool) $staff->delete();
    }

    public function getTrashedStaff(array $filters = [])
    {
        $query = \Modules\StaffManager\Models\Staff::onlyTrashed()
            ->with(['coachDetail', 'branches', 'user']);

        if (!empty($filters['branch_id'])) {
            $query->whereHas('branches', function ($q) use ($filters) {
                $q->where('staff_branches.branch_id', $filters['branch_id']);
            });
        }

        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        $staffMembers = $query->latest()->get();
        foreach ($staffMembers as $staff) {
            $this->attachSharedDTOs($staff);
        }

        return $staffMembers;
    }

    public function restoreStaff(int $id)
    {
        $staff = \Modules\StaffManager\Models\Staff::onlyTrashed()->findOrFail($id);
        $staff->restore();
        return $this->attachSharedDTOs($staff);
    }
}
