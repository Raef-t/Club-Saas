<?php

namespace Modules\Authentication\Services;

use Modules\Authentication\Models\Person;
use Modules\Core\Contracts\PersonSharedServiceInterface;
use Modules\Core\DTOs\PersonDTO;

class PersonService implements PersonServiceInterface, PersonSharedServiceInterface
{
    public function createPerson(\Modules\Core\DTOs\CreatePersonDTO $dto): PersonDTO
    {
        $person = Person::create($dto->toArray());
        return new PersonDTO(
            id: $person->id,
            fullName: $person->full_name,
            gender: \Modules\Core\Enums\Gender::from($person->gender),
            mobile1: $person->mobile_1,
            email: $person->email
        );
    }

    public function updatePerson(int $id, \Modules\Core\DTOs\UpdatePersonDTO $dto): ?PersonDTO
    {
        $person = $this->findPersonById($id);
        if ($person) {
            $person->update($dto->toArray());
            return new PersonDTO(
                id: $person->id,
                fullName: $person->full_name,
                gender: \Modules\Core\Enums\Gender::from($person->gender),
                mobile1: $person->mobile_1,
                email: $person->email
            );
        }
        return null;
    }

    public function findPersonById(int $id)
    {
        return Person::find($id);
    }

    public function findPersonByMobile(string $mobile)
    {
        return Person::where('mobile_1', $mobile)->first();
    }

    public function getPersonById(int $id): ?PersonDTO
    {
        $person = Person::find($id);
        if (!$person) {
            return null;
        }

        return new PersonDTO(
            id: $person->id,
            fullName: $person->full_name,
            gender: \Modules\Core\Enums\Gender::from($person->gender),
            mobile1: $person->mobile_1,
            email: $person->email
        );
    }
}
