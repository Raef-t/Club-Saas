<?php

namespace Modules\MemberManager\Services;

use Modules\MemberManager\Repositories\MemberRepositoryInterface;
use Modules\MemberManager\Domain\Rules\MemberGenderMatchRule;
use Illuminate\Support\Facades\DB;
use Modules\Authentication\Services\PersonServiceInterface;

class MemberService
{
    protected $repository;
    protected $genderRule;
    protected $personService;

    public function __construct(
        MemberRepositoryInterface $repository,
        MemberGenderMatchRule $genderRule,
        PersonServiceInterface $personService
    ) {
        $this->repository = $repository;
        $this->genderRule = $genderRule;
        $this->personService = $personService;
    }

    public function getAllMembers()
    {
        return $this->repository->all();
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
                $person = $this->personService->createPerson([
                    'full_name' => $data['full_name'],
                    'mobile_1' => $data['mobile_1'],
                    'gender' => $data['gender'],
                    'dob' => $data['dob'] ?? null,
                    'type' => 'player',
                    'tenant_id' => $data['tenant_id'] ?? session()->get('tenant_id'),
                ]);
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

            return $member;
        });
    }

    public function getMemberById($id)
    {
        return $this->repository->find($id);
    }

    public function updateMember($id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $member = $this->repository->find($id);

            // 1. Update Person Info if provided
            if ($member->person_id) {
                $this->personService->updatePerson($member->person_id, [
                    'full_name' => $data['full_name'] ?? null,
                    'mobile_1' => $data['mobile_1'] ?? null,
                    'gender' => $data['gender'] ?? null,
                    'dob' => $data['dob'] ?? null,
                ]);
            }

            // 2. Update Member Info
            $member->update($data);

            // 3. Update Health Profile if provided
            if (isset($data['health_profile'])) {
                $member->healthProfile()->updateOrCreate(
                    ['member_id' => $member->id],
                    array_merge($data['health_profile'], ['tenant_id' => $member->tenant_id])
                );
            }

            return $member->load(['person', 'healthProfile']);
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
}
