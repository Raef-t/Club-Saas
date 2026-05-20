<?php

namespace Modules\BranchManager\Services;

use Modules\Core\Contracts\BranchSharedServiceInterface;
use Modules\Core\DTOs\BranchDTO;
use Modules\BranchManager\Repositories\BranchRepositoryInterface;

class BranchSharedService implements BranchSharedServiceInterface
{
    protected $repository;

    public function __construct(BranchRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function getBranchById(int $id): ?BranchDTO
    {
        $branch = $this->repository->find($id);
        if (!$branch) {
            return null;
        }

        // Handle possible array/JSON decode for name attribute
        $name = $branch->name;
        if (is_string($name)) {
            $decoded = json_decode($name, true);
            $name = is_array($decoded) ? $decoded : [$name];
        } else {
            $name = (array)$name;
        }

        return new BranchDTO(
            id: $branch->id,
            name: $name,
            genderRestriction: \Modules\Core\Enums\Gender::from($branch->gender_restriction),
            isActive: (bool)$branch->is_active
        );
    }
}
