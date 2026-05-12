<?php

namespace Modules\MemberManager\Domain\Rules;

use Modules\BranchManager\Models\Branch;
use Modules\Authentication\Services\PersonServiceInterface;
use Exception;

class MemberGenderMatchRule
{
    protected $personService;

    public function __construct(PersonServiceInterface $personService)
    {
        $this->personService = $personService;
    }

    /**
     * Rule: A member's gender must match the branch's gender restriction.
     */
    public function validate($branchId, $personId)
    {
        $branch = Branch::findOrFail($branchId);
        $person = $this->personService->findPersonById($personId);

        if (!$person) {
            throw new Exception(__('Person not found.'));
        }

        if ($branch->gender_restriction !== 'mixed' && $branch->gender_restriction !== $person->gender) {
            throw new Exception(__('This branch is for :gender only.', ['gender' => $branch->gender_restriction]));
        }
    }
}
