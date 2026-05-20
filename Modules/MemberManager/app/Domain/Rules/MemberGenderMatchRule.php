<?php

namespace Modules\MemberManager\Domain\Rules;

use Modules\Core\Contracts\BranchSharedServiceInterface;
use Modules\Core\Contracts\PersonSharedServiceInterface;
use Exception;

class MemberGenderMatchRule
{
    protected $branchSharedService;
    protected $personSharedService;

    public function __construct(
        BranchSharedServiceInterface $branchSharedService,
        PersonSharedServiceInterface $personSharedService
    ) {
        $this->branchSharedService = $branchSharedService;
        $this->personSharedService = $personSharedService;
    }

    /**
     * Rule: A member's gender must match the branch's gender restriction.
     */
    public function validate($branchId, $personId)
    {
        $branch = $this->branchSharedService->getBranchById($branchId);
        if (!$branch) {
            throw new Exception(__('Branch not found.'));
        }

        $person = $this->personSharedService->getPersonById($personId);
        if (!$person) {
            throw new Exception(__('Person not found.'));
        }

        if ($branch->genderRestriction !== \Modules\Core\Enums\Gender::MIXED && $branch->genderRestriction !== $person->gender) {
            throw new Exception(__('This branch is for :gender only.', ['gender' => $branch->genderRestriction->value]));
        }
    }
}
