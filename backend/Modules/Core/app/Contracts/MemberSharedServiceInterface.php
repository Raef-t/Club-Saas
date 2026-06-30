<?php

namespace Modules\Core\Contracts;

use Modules\Core\DTOs\MemberDTO;

interface MemberSharedServiceInterface
{
    public function getMemberById(int $id): ?MemberDTO;
    public function getMemberByBarcode(string $barcode): ?MemberDTO;
}
