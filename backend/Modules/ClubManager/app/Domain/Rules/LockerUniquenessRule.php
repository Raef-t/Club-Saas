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
     * Rule: Locker number and key number must be unique within a single branch.
     */
    public function validate($branchId, $lockerNumber, $keyNumber = null, $ignoreLockerId = null)
    {
        $branchLockers = $this->repository->getByBranch($branchId);

        // 1. Check locker_number uniqueness
        if ($lockerNumber) {
            $existingLocker = $branchLockers->first(function ($locker) use ($lockerNumber, $ignoreLockerId) {
                return (string) $locker->locker_number === (string) $lockerNumber && $locker->id != $ignoreLockerId;
            });

            if ($existingLocker) {
                throw new Exception(__('Locker number :num already exists in this branch.', ['num' => $lockerNumber]));
            }
        }

        // 2. Check key_number uniqueness (if provided)
        if (!empty($keyNumber)) {
            $existingKey = $branchLockers->first(function ($locker) use ($keyNumber, $ignoreLockerId) {
                return (string) $locker->key_number === (string) $keyNumber && $locker->id != $ignoreLockerId;
            });

            if ($existingKey) {
                throw new Exception(__('Key number :num already exists in this branch.', ['num' => $keyNumber]));
            }
        }
    }
}
