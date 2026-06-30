<?php

namespace Modules\Core\Contracts;

use Modules\Core\DTOs\PersonDTO;

interface PersonSharedServiceInterface
{
    public function getPersonById(int $id): ?PersonDTO;
    public function createPerson(\Modules\Core\DTOs\CreatePersonDTO $dto): PersonDTO;
    public function updatePerson(int $id, \Modules\Core\DTOs\UpdatePersonDTO $dto): ?PersonDTO;
}
