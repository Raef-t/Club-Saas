<?php

namespace Modules\MemberManager\Services;

use Modules\MemberManager\Repositories\MemberRepositoryInterface;
use Modules\MemberManager\Domain\Rules\MemberGenderMatchRule;
use Illuminate\Support\Facades\DB;
use Modules\Core\Contracts\PersonSharedServiceInterface;
use Modules\Core\Contracts\BranchSharedServiceInterface;

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

    public function getAllMembers()
    {
        $members = $this->repository->all();
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
                );
                $person = $this->personService->createPerson($personDto);
                $personId = $person->id;
            }

            // 2. Execute Domain Rule
            $this->genderRule->validate($data['branch_id'], $personId);

            // 3. Create the member record
            $data['person_id'] = $personId;
            $member = $this->repository->create($data);

            // 4. Initialize health profile if provided
            if (isset($data['health_profile'])) {
                $member->healthProfile()->create($data['health_profile']);
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
