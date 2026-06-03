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

class MemberService
{
    protected $repository;
    protected $genderRule;
    protected $personService;
    protected $branchService;

    public function __construct(
        MemberRepositoryInterface $repository,
        MemberGenderMatchRule $genderRule,
        PersonSharedServiceInterface $personService,
        BranchSharedServiceInterface $branchService
    ) {
        $this->repository = $repository;
        $this->genderRule = $genderRule;
        $this->personService = $personService;
        $this->branchService = $branchService;
    }

    public function getAllMembers(array $filters = [])
    {
        $query = \Modules\MemberManager\Models\Member::query();

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

            $query->where(function($q) use ($personIds, $filters) {
                $q->whereIn('person_id', $personIds);
                if (!empty($filters['search'])) {
                    $q->orWhere('member_number', 'like', "%{$filters['search']}%");
                }
            });
        }

        $perPage = $filters['per_page'] ?? 15;
        $members = $query->latest()->paginate($perPage);

        foreach ($members as $member) {
            $this->attachSharedDTOs($member);
        }

        return $members;
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
                    mobile1: $data['mobile_1'],
                    gender: isset($data['gender']) ? \Modules\Core\Enums\Gender::tryFrom($data['gender']) : null,
                    dob: $data['dob'] ?? null,
                    type: 'player',
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
                $personId = $person->id;
            }

            // 2. Execute Domain Rule
            $this->genderRule->validate($data['branch_id'], $personId);

            // Fetch Branch and Club Settings
            $branch = Branch::find($data['branch_id']);
            $clubSettings = ClubSetting::where('club_id', $branch->club_id ?? 1)->first();
            $enabledFeatures = $clubSettings ? ($clubSettings->enabled_features ?? []) : [];
            
            $autoGenerate = isset($enabledFeatures['auto_generate_credentials']) 
                            ? filter_var($enabledFeatures['auto_generate_credentials'], FILTER_VALIDATE_BOOLEAN) 
                            : true; // Default to true if not specified

            if ($autoGenerate) {
                // Generate QR Code
                $data['barcode_qr_code'] = 'QR-' . Str::uuid()->toString();

                // Generate User Account for Mobile App
                $username = 'player_' . $personId . '_' . Str::random(4);
                $password = Str::random(8); // Secure random string
                
                $user = User::create([
                    'username' => $username,
                    'password' => Hash::make($password),
                    'person_id' => $personId,
                    'is_active' => true,
                ]);
                $user->assignRole('player');
            }

            // 3. Create the member record
            $data['person_id'] = $personId;
            $member = $this->repository->create($data);

            // 4. Initialize health profile if provided
            if (isset($data['health_profile'])) {
                $member->healthProfile()->create($data['health_profile']);
            }

            // 5. Expose generated credentials and send welcome notification
            if ($autoGenerate && isset($username)) {
                $member->generated_username = $username;
                $member->generated_password = $password;

                try {
                    $notificationService = app(\Modules\NotificationManager\Services\NotificationService::class);
                    $notificationService->notifyWelcome($member, [
                        'name' => $data['full_name'],
                        'username' => $username,
                        'password' => $password,
                    ]);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Welcome notification failed: " . $e->getMessage());
                }
            }

            return $this->attachSharedDTOs($member);
        });
    }

    public function getMemberById($id)
    {
        $member = $this->repository->find($id);
        return $this->attachSharedDTOs($member);
    }

    public function updateMember($id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $member = $this->repository->find($id);

            // 1. Update Person Info if provided
            if ($member->person_id) {
                $personData = array_filter([
                    'fullName' => $data['full_name'] ?? null,
                    'mobile1' => $data['mobile_1'] ?? null,
                    'gender' => isset($data['gender']) ? \Modules\Core\Enums\Gender::tryFrom($data['gender']) : null,
                    'dob' => $data['dob'] ?? null,
                    'email' => $data['email'] ?? null,
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

    public function deleteMember($id)
    {
        return $this->repository->delete($id);
    }

    public function getMeasurements($memberId)
    {
        $member = $this->repository->find($memberId);
        return $member->measurements()->latest()->get();
    }

    public function getHealthProfile($memberId)
    {
        $member = $this->repository->find($memberId);
        return $member->healthProfile;
    }

    public function recordMeasurement($memberId, array $data)
    {
        $member = $this->getMemberById($memberId);
        return $member->measurements()->create($data);
    }

    /**
     * Helper to resolve and attach Person and Branch DTOs
     */
    protected function attachSharedDTOs($member)
    {
        if ($member) {
            $member->person = $member->person_id ? $this->personService->getPersonById($member->person_id) : null;
            $member->branch = $member->branch_id ? $this->branchService->getBranchById($member->branch_id) : null;
        }
        return $member;
    }
}
