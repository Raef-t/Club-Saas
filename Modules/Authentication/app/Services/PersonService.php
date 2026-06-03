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
        return $this->mapToDTO($person);
    }

    public function updatePerson(int $id, \Modules\Core\DTOs\UpdatePersonDTO $dto): ?PersonDTO
    {
        $person = $this->findPersonById($id);
        if ($person) {
            $person->update($dto->toArray());
            return $this->mapToDTO($person);
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
        return $this->mapToDTO($person);
    }

    private function mapToDTO(Person $person): PersonDTO
    {
        return new PersonDTO(
            id: $person->id,
            fullName: $person->full_name,
            gender: \Modules\Core\Enums\Gender::tryFrom($person->gender),
            mobile1: $person->mobile_1,
            email: $person->email,
            nationalId: $person->national_id,
            socialStatus: $person->social_status,
            address: $person->address,
            photoUrl: $person->photo_url,
            mobile2: $person->mobile_2,
            landline: $person->landline,
            emergencyContactName: $person->emergency_contact_name,
            emergencyContactPhone: $person->emergency_contact_phone,
            chronicDiseases: $person->chronic_diseases,
            childrenCount: $person->children_count,
            howDidYouHear: $person->how_did_you_hear,
            notes: $person->notes
        );
    }
}
