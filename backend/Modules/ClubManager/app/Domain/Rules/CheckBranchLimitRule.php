<?php

namespace Modules\ClubManager\Domain\Rules;

use Modules\ClubManager\Repositories\BranchRepositoryInterface;
use Exception;

class CheckBranchLimitRule
{
    protected $repository;

    public function __construct(BranchRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Rule: The club cannot exceed the branch limit.
     * (Assume for now limit is 5, but this could come from a config or database setting)
     */
    public function validate()
    {
        $currentCount = count($this->repository->all());
        $limit = 5; // This could be dynamic

        if ($currentCount >= $limit) {
            throw new Exception(__('Branch limit reached for this club.'));
        }
    }
}
