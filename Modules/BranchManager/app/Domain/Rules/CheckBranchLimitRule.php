<?php

namespace Modules\BranchManager\Domain\Rules;

use Modules\BranchManager\Repositories\BranchRepositoryInterface;
use Exception;

class CheckBranchLimitRule
{
    protected $repository;

    public function __construct(BranchRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Rule: A tenant cannot exceed the branch limit allowed in their plan.
     * (Assume for now limit is 5, but this could come from Tenant settings)
     */
    public function validate($tenantId)
    {
        $currentCount = count($this->repository->all());
        $limit = 5; // This could be dynamic

        if ($currentCount >= $limit) {
            throw new Exception(__('Branch limit reached for this club.'));
        }
    }
}
