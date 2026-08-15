<?php

namespace Modules\Authentication\Services;

interface PersonServiceInterface
{
    public function createPerson(\Modules\Core\DTOs\CreatePersonDTO $dto): \Modules\Core\DTOs\PersonDTO;
    public function findPersonById(int $id);
    public function findPersonByMobile(string $mobile);
    public function updateUserProfile(\Modules\Authentication\Models\User $user, array $validated): array;
}



