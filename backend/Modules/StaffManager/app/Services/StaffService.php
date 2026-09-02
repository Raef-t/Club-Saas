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
        $query = \Modules\StaffManager\Models\Staff::query()
            ->where(function ($q) {
                $q->where('role', '!=', 'coach')
                  ->orWhere(function ($coachQuery) {
                      $coachQuery->where('role', 'coach')
                          ->whereHas('activities.activityType', function ($actTypeQuery) {
                              $actTypeQuery->where('name', 'like', '%تدريب عام%')
                                           ->orWhere('id', 4);
                          });
                  });
            });

        if (!empty($filters['branch_id'])) {
            $query->whereHas('branches', function ($q) use ($filters) {
                $q->where('staff_branches.branch_id', $filters['branch_id']);
            });
        }

        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (!empty($filters['work_status'])) {
            $query->where('work_status', $filters['work_status']);
        } elseif (isset($filters['is_active'])) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        if (!empty($filters['gender'])) {
            $query->whereHas('person', function ($q) use ($filters) {
                $q->where('gender', $filters['gender']);
            });
        }

        // Eager-load person contacts, coach details, active contract, branches, user and shifts
        $query->with([
            'person.contacts',
            'coachDetail',
            'activeContract',
            'branches',
            'user',
            'shifts.branchShift',
        ]);

        $query->latest();

        if (!isset($filters['per_page']) || $filters['per_page'] === 'all' || (isset($filters['paginate']) && filter_var($filters['paginate'], FILTER_VALIDATE_BOOLEAN) === false) || (isset($filters['all']) && filter_var($filters['all'], FILTER_VALIDATE_BOOLEAN) === true)) {
            $staffMembers = $query->get();
            foreach ($staffMembers as $staff) {
                $this->attachSharedDTOs($staff);
            }
            return $staffMembers;
        }

        $perPage = min(max((int)$filters['per_page'], 1), 100);
        $staffMembers = $query->paginate($perPage);

        foreach ($staffMembers->items() as $staff) {
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
        $staff->load([
            'person.contacts',
            'coachDetail',
            'activeContract',
            'branches',
            'user',
            'shifts.branchShift',
        ]);
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

            // Generate single permanent QR code for staff
            app(\Modules\Authentication\Services\PersonQrCodeService::class)->generateSingleForPerson($person->id);

            // 2. Create the staff record
            $staff = $this->staffRepository->create(array_merge($data, [
                'person_id' => $person->id,
            ]));

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

            if (!empty($data['branch_ids'])) {
                $staff->branches()->sync($data['branch_ids']);
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

            $roleName = $data['role'] ?? 'staff';
            $username = !empty($data['username']) 
                ? $data['username'] 
                : \Modules\Authentication\Services\UsernameGeneratorService::generateForRole($roleName);
            $password = $data['password'] ?? '12345678';

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
            if ($staff->relationLoaded('person') && $staff->person) {
                $staff->personDto = $this->personService->mapToDTO($staff->person);
            } elseif ($staff->person_id) {
                $staff->personDto = $this->personService->getPersonById($staff->person_id);
            } else {
                $staff->personDto = null;
            }

            $firstBranch = $staff->relationLoaded('branches')
                ? $staff->branches->first()
                : $staff->branches()->first();

            $staff->branchDto = $firstBranch ? $this->branchService->mapToDTO($firstBranch) : null;
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
            $newPrivateCommissionRate = $data['private_commission_rate'] ?? ($activeContract ? $activeContract->private_commission_rate : 0);
            $newCommissionType = $data['commission_type'] ?? ($activeContract ? $activeContract->commission_type : null);
            if (empty($newCommissionType) && $newCommissionRate > 0) {
                $newCommissionType = 'percentage';
            }

            // Only create new contract if financial data actually changed
            if (!$activeContract || 
                $activeContract->employment_type !== $newEmploymentType ||
                (float)$activeContract->base_salary !== (float)$newBaseSalary ||
                $activeContract->commission_type !== $newCommissionType ||
                (float)$activeContract->commission_rate !== (float)$newCommissionRate ||
                (float)$activeContract->private_commission_rate !== (float)$newPrivateCommissionRate
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
                    'private_commission_rate' => $newPrivateCommissionRate,
                    'start_date' => now()->toDateString(),
                    'is_active' => true,
                ]);
            }

            if (isset($data['branch_ids'])) {
                $staff->branches()->sync($data['branch_ids']);
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
        $staffMembers = $this->staffRepository->getTrashed($filters);
        $items = $staffMembers instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator ? $staffMembers->items() : $staffMembers;
        foreach ($items as $staff) {
            $this->attachSharedDTOs($staff);
        }

        return $staffMembers;
    }

    public function restoreStaff(int $id)
    {
        $staff = $this->staffRepository->restore($id);
        return $this->attachSharedDTOs($staff);
    }
}
