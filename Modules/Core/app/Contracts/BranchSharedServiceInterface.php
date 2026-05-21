<?php

namespace Modules\Core\Contracts;

use Modules\Core\DTOs\BranchDTO;

interface BranchSharedServiceInterface
{
    public function getBranchById(int $id): ?BranchDTO;

    public function facilityExists(int $facilityId): bool;
}
