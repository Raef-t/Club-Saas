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

    public function updateUserProfile(\Modules\Authentication\Models\User $user, array $validated): array
    {
        $person = $user->person;
        if (!$person) {
            $person = Person::create([
                'type' => $user->role ?? 'player',
            ]);
            $user->update(['person_id' => $person->id]);
        }

        $personData = [];

        if (array_key_exists('first_name', $validated) || array_key_exists('last_name', $validated)) {
            $existingParts = explode(' ', $person->full_name ?? '', 2);
            $firstName = $validated['first_name'] ?? ($existingParts[0] ?? '');
            $lastName  = $validated['last_name']  ?? ($existingParts[1] ?? '');
            $personData['full_name'] = trim($firstName . ' ' . $lastName);
        }

        if (array_key_exists('dob', $validated)) {
            $personData['dob'] = $validated['dob'];
        }

        if (array_key_exists('gender', $validated)) {
            $personData['gender'] = $validated['gender'];
        }

        if (array_key_exists('address', $validated)) {
            $personData['address'] = $validated['address'];
        }

        if (array_key_exists('how_did_you_hear', $validated)) {
            $personData['how_did_you_hear'] = $validated['how_did_you_hear'];
        }

        if (!empty($personData)) {
            $person->update($personData);
        }

        if (array_key_exists('phone_number', $validated)) {
            $phoneNumber = $validated['phone_number'];
            $primaryContact = $person->contacts()
                ->where(function ($q) {
                    $q->where('relation', 'self')
                      ->orWhere('name', 'Personal');
                })
                ->first() ?? $person->contacts()->first();

            if ($primaryContact) {
                if ($phoneNumber !== null && $phoneNumber !== '') {
                    $primaryContact->update(['phone_number' => $phoneNumber]);
                } else {
                    $primaryContact->delete();
                }
            } elseif ($phoneNumber !== null && $phoneNumber !== '') {
                $person->contacts()->create([
                    'name'         => 'Personal',
                    'relation'     => 'self',
                    'phone_number' => $phoneNumber,
                ]);
            }
        }

        $person->refresh();
        $person->load('contacts');

        $nameParts = explode(' ', $person->full_name ?? '', 2);
        $primaryPhone = $person->contacts->first(fn($c) => $c->relation === 'self' || $c->name === 'Personal')?->phone_number 
            ?? $person->contacts->first()?->phone_number;

        return [
            'id'               => $user->id,
            'username'         => $user->username,
            'custom_username'  => $user->custom_username,
            'person_id'        => $person->id,
            'first_name'       => $nameParts[0] ?? null,
            'last_name'        => $nameParts[1] ?? null,
            'full_name'        => $person->full_name,
            'phone_number'     => $primaryPhone,
            'dob'              => $person->dob ? \Carbon\Carbon::parse($person->dob)->format('Y-m-d') : null,
            'age'              => $person->age,
            'gender'           => $person->gender,
            'address'          => $person->address,
            'how_did_you_hear' => $person->how_did_you_hear,
        ];
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

    public function mapToDTO(Person $person): PersonDTO
    {
        $contacts = $person->relationLoaded('contacts') ? $person->contacts : $person->contacts()->get();

        $primaryContact = $contacts->first(fn($c) => $c->name === 'Personal' || $c->relation === 'self') ?? $contacts->first();
        $secondaryContact = $contacts->first(fn($c) => $c->name === 'Secondary Mobile');
        $landline = $contacts->first(fn($c) => $c->name === 'Landline');
        $emergency = $contacts->first(fn($c) => $c->relation === 'emergency');

        return new PersonDTO(
            id: $person->id,
            fullName: $person->full_name,
            gender: \Modules\Core\Enums\Gender::tryFrom($person->gender ?? ''),
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
            notes: $person->notes,
            dob: $person->dob
        );
    }
}

