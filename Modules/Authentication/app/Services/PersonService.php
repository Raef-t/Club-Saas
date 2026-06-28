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

        if ($dto->mobile1) {
            $person->contacts()->create([
                'name'         => 'Personal',
                'relation'     => 'self',
                'phone_number' => $dto->mobile1,
                'country_code' => $dto->mobile1CountryCode,
            ]);
        }

        if ($dto->mobile2) {
            $person->contacts()->create([
                'name'         => 'Secondary Mobile',
                'relation'     => 'self',
                'phone_number' => $dto->mobile2,
                'country_code' => $dto->mobile2CountryCode,
            ]);
        }

        if ($dto->landline) {
            $person->contacts()->create([
                'name'         => 'Landline',
                'relation'     => 'self',
                'phone_number' => $dto->landline,
            ]);
        }

        if ($dto->emergencyContactPhone) {
            $person->contacts()->create([
                'name'         => $dto->emergencyContactName ?? 'Emergency Contact',
                'relation'     => 'emergency',
                'phone_number' => $dto->emergencyContactPhone,
                'country_code' => $dto->emergencyContactCountryCode,
            ]);
        }

        return $this->mapToDTO($person);
    }

    public function updatePerson(int $id, \Modules\Core\DTOs\UpdatePersonDTO $dto): ?PersonDTO
    {
        $person = $this->findPersonById($id);
        if ($person) {
            $person->update($dto->toArray());

            // Note: Currently not fully updating contacts on updatePerson to avoid duplicates. 
            // The frontend should ideally call a specific contacts API, or we overwrite them here.
            // For now, we will leave contacts update manual or via dedicated endpoints.

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
        return Person::whereHas('contacts', function ($query) use ($mobile) {
            $query->where('phone_number', $mobile);
        })->first();
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
        $primaryContact = $person->contacts()->where('name', 'Personal')->first() ?? $person->contacts()->first();
        $secondaryContact = $person->contacts()->where('name', 'Secondary Mobile')->first();
        $landline = $person->contacts()->where('name', 'Landline')->first();
        $emergency = $person->contacts()->where('relation', 'emergency')->first();

        return new PersonDTO(
            id: $person->id,
            fullName: $person->full_name,
            gender: \Modules\Core\Enums\Gender::tryFrom($person->gender),
            age: $person->age,
            mobile1: $primaryContact?->phone_number,
            mobile1CountryCode: $primaryContact?->country_code,
            email: $person->email,
            nationalId: $person->national_id,
            socialStatus: $person->social_status,
            address: $person->address,
            photoUrl: $person->photo_url,
            mobile2: $secondaryContact?->phone_number,
            mobile2CountryCode: $secondaryContact?->country_code,
            landline: $landline?->phone_number,
            emergencyContactName: $emergency?->name,
            emergencyContactPhone: $emergency?->phone_number,
            emergencyContactCountryCode: $emergency?->country_code,
            chronicDiseases: $person->chronic_diseases,
            childrenCount: $person->children_count,
            howDidYouHear: $person->how_did_you_hear,
            notes: $person->notes
        );
    }
}
