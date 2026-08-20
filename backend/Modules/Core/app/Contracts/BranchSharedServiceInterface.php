<?php

namespace Modules\Core\Contracts;

use Modules\Core\DTOs\BranchDTO;

interface BranchSharedServiceInterface
{
    public function getBranchById(int $id): ?BranchDTO;

    public function mapToDTO(\Modules\ClubManager\Models\Branch $branch): BranchDTO;

    public function facilityExists(int $facilityId): bool;
}
