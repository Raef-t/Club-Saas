<?php

namespace Modules\Core\DTOs;

use Modules\Core\Enums\Gender;

readonly class PersonDTO
{
    public function __construct(
        public int $id,
        public string $fullName,
        public Gender $gender,
        public string $mobile1,
        public ?string $email = null
    ) {}
}
