<?php

namespace Modules\Core\Contracts;

use Modules\Core\DTOs\StaffDTO;

interface StaffSharedServiceInterface
{
    public function getStaffById(int $id): ?StaffDTO;
}
