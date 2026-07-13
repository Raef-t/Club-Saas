<?php

namespace Modules\MemberManager\Services;

use Modules\MemberManager\Repositories\MemberRepositoryInterface;
use Modules\MemberManager\Domain\Rules\MemberGenderMatchRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Modules\Core\Contracts\PersonSharedServiceInterface;
use Modules\Core\Contracts\BranchSharedServiceInterface;
use Modules\ClubManager\Models\Branch;
use Modules\ClubManager\Models\ClubSetting;
use Modules\Authentication\Models\User;
use Modules\Authentication\Services\PersonQrCodeService;

class MemberService
{
    protected MemberRepositoryInterface $repository;
    protected MemberGenderMatchRule $genderRule;
    protected PersonSharedServiceInterface $personService;
    protected BranchSharedServiceInterface $branchService;
    protected PersonQrCodeService $qrCodeService;

    public function __construct(
        MemberRepositoryInterface $repository,
        MemberGenderMatchRule $genderRule,
        PersonSharedServiceInterface $personService,
        BranchSharedServiceInterface $branchService,
        PersonQrCodeService $qrCodeService
    ) {
        $this->repository    = $repository;
        $this->genderRule    = $genderRule;
        $this->personService = $personService;
        $this->branchService = $branchService;
        $this->qrCodeService = $qrCodeService;
    }

    public function getAllMembers(array $filters = [])
    {
        $query = \Modules\MemberManager\Models\Member::query()->with(['person.contacts', 'branch', 'subscriptions.items.activity', 'subscriptions.plan', 'healthProfile', 'measurements']);

        // 1. Filtering by Branch
        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        // 2. Filtering by Status
        if (!empty($filters['status'])) {
            $query->where('membership_status', $filters['status']);
        }

        // 3. Filtering by Gender or Search (Full name, phone, email, national_id)
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

            $query->where(function($q) use ($personIds, $filters) {
                $q->whereIn('person_id', $personIds);
                if (!empty($filters['search'])) {
                    $q->orWhere('member_number', 'like', "%{$filters['search']}%");
                }
            });
        }

        return $query->latest()->get();
    }

    /**
     * Register a new member (Orchestration).
     */
    public function registerMember(array $data)
    {
        return DB::transaction(function () use ($data) {
            // 1. Resolve or Create Person
            $personId = $data['person_id'] ?? null;

            if (!$personId) {
                $personDto = new \Modules\Core\DTOs\CreatePersonDTO(
                    fullName: $data['full_name'],
                    mobile1: $data['phone_number'] ?? null,
                    mobile1CountryCode: $data['country_code'] ?? null,
                    gender: isset($data['gender']) ? \Modules\Core\Enums\Gender::tryFrom($data['gender']) : null,
                    dob: $data['dob'] ?? null,
                    type: 'player',
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
                $personId = $person->id;
            }

            // 2. Execute Domain Rule
            $this->genderRule->validate($data['branch_id'], $personId);

            // Fetch Branch and Club Settings
            $branch = Branch::find($data['branch_id']);
            if (!$branch) {
                throw new \Exception(__('Member branch not found.'));
            }
            $clubSettings = ClubSetting::where('club_id', $branch->club_id)->first();
            $enabledFeatures = $clubSettings ? ($clubSettings->enabled_features ?? []) : [];
            
            $autoGenerate = isset($enabledFeatures['auto_generate_credentials']) 
                            ? filter_var($enabledFeatures['auto_generate_credentials'], FILTER_VALIDATE_BOOLEAN) 
                            : true; // Default to true if not specified

            $username = null;
            $password = null;
            $user = null;

            if ($autoGenerate) {
                // Generate User Account for Mobile App
                $username = 'Mem-' . $personId . '-' . strtolower(Str::random(6));
                $password = 'password123'; // Default password
                
                $user = User::create([
                    'username'  => $username,
                    'password'  => Hash::make($password),
                    'person_id' => $personId,
                    'is_active' => true,
                ]);
                $user->assignRole('player');
            }

            // 3. Create the member record
            $data['person_id'] = $personId;
            $member = $this->repository->create($data);

            // 4. Generate 7 QR codes for this person
            $this->qrCodeService->generateForPerson($personId);

            // 5. Initialize health profile if provided
            if (isset($data['health_profile'])) {
                $member->healthProfile()->create($data['health_profile']);
            }

            // 5. Expose generated credentials and send welcome notification
            if ($autoGenerate && $username && $password && $user) {
                $member->generated_username = $username;
                $member->generated_password = $password;

                try {
                    $notificationService = app(\Modules\NotificationManager\Services\NotificationService::class);
                    $notificationService->createNotification([
                        'title' => 'Welcome!',
                        'body' => "Hello {$data['full_name']}, your account has been created. Username: {$username}, Password: {$password}",
                        'sender_type' => 'system',
                        'user_ids' => [$user->id],
                    ]);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Welcome notification failed: " . $e->getMessage());
                }
            }

            return $this->attachSharedDTOs($member);
        });
    }

    public function getMemberById(int $id)
    {
        $member = $this->repository->find($id);
        return $this->attachSharedDTOs($member);
    }

    public function updateMember(int $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $member = $this->repository->find($id);

            // 1. Update Person Info if provided
            if ($member->person_id) {
                $personData = array_filter([
                    'fullName' => $data['full_name'] ?? null,
                    'mobile1' => $data['phone_number'] ?? null,
                    'mobile1CountryCode' => $data['country_code'] ?? null,
                    'gender' => isset($data['gender']) ? \Modules\Core\Enums\Gender::tryFrom($data['gender']) : null,
                    'dob' => $data['dob'] ?? null,
                    'email' => $data['email'] ?? null,
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
                    $this->personService->updatePerson($member->person_id, $updateDto);
                }
            }

            // 2. Update Member Info
            $member->update($data);

            // 3. Update Health Profile if provided
            if (isset($data['health_profile'])) {
                $member->healthProfile()->updateOrCreate(
                    ['member_id' => $member->id],
                    $data['health_profile']
                );
            }

            $member->load(['healthProfile']);
            return $this->attachSharedDTOs($member);
        });
    }

    public function deleteMember(int $id): void
    {
        $member = $this->repository->find($id);

        // Check for financial records
        $invoicesCount = \Modules\SubscriptionManager\Models\Invoice::where('member_id', $id)->count();

        // Check for attendance records (polymorphic: attendable_type = Member class)
        $attendanceCount = \Modules\AttendanceManager\Models\Attendance::where('attendable_type', 'Modules\\MemberManager\\Models\\Member')
            ->where('attendable_id', $id)
            ->count();

        $blocked = [];

        if ($invoicesCount > 0) {
            $blocked[] = "يوجد {$invoicesCount} " . ($invoicesCount === 1 ? 'فاتورة' : 'فواتير') . " مالية مرتبطة بهذا العضو";
        }
        if ($attendanceCount > 0) {
            $blocked[] = "يوجد {$attendanceCount} " . ($attendanceCount === 1 ? 'سجل حضور' : 'سجلات حضور') . " مرتبطة بهذا العضو";
        }

        if (!empty($blocked)) {
            $reasons = implode('، و', $blocked);
            throw new \Modules\Core\Exceptions\CannotDeleteException(
                "لا يمكن حذف هذا العضو لأن: {$reasons}. يمكنك تغيير حالة العضو إلى 'غير نشط' بدلاً من الحذف.",
                [
                    'invoices_count'   => $invoicesCount,
                    'attendance_count' => $attendanceCount,
                ]
            );
        }

        // Safe to delete — cascade will clean up non-financial child records
        $this->repository->delete($id);
    }

    public function getMeasurements(int $memberId)
    {
        $member = $this->repository->find($memberId);
        return $member->measurements()->latest()->get();
    }

    public function getHealthProfile(int $memberId)
    {
        $member = $this->repository->find($memberId);
        return $member->healthProfile;
    }

    public function recordMeasurement(int $memberId, array $data)
    {
        $member = $this->getMemberById($memberId);
        return $member->measurements()->create($data);
    }

    /**
     * Helper to resolve and attach Person and Branch Eloquent Models
     */
    protected function attachSharedDTOs(?\Modules\MemberManager\Models\Member $member)
    {
        if ($member) {
            $member->loadMissing(['person.contacts', 'branch', 'subscriptions.items.activity', 'subscriptions.plan', 'healthProfile', 'measurements']);
        }
        return $member;
    }

    /**
     * Get statistics for members.
     */
    public function getStats(array $filters = [])
    {
        $query = \Modules\MemberManager\Models\Member::query();

        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        $baseQuery = clone $query;

        return [
            'total_members' => (clone $baseQuery)->count(),
            'active_members' => (clone $baseQuery)->where('membership_status', 'active')->count(),
            'male_members' => (clone $baseQuery)->whereHas('person', function ($q) {
                $q->where('gender', 'male');
            })->count(),
            'female_members' => (clone $baseQuery)->whereHas('person', function ($q) {
                $q->where('gender', 'female');
            })->count(),
        ];
    }
}
