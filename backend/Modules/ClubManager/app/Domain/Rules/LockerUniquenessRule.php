<?php

namespace Modules\ClubManager\Domain\Rules;

use Modules\ClubManager\Repositories\LockerRepositoryInterface;
use Exception;

class LockerUniquenessRule
{
    protected $repository;

    public function __construct(LockerRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Rule: Locker number must be unique within a single branch.
     */
    public function validate($branchId, $lockerNumber)
    {
        $existing = $this->repository->getByBranch($branchId)
            ->where('locker_number', $lockerNumber)
            ->first();

        if ($existing) {
            throw new Exception(__('Locker number :num already exists in this branch.', ['num' => $lockerNumber]));
        }
    }
}
