<?php

namespace Modules\Authentication\Services;

use Modules\Authentication\Models\Person;

class PersonService implements PersonServiceInterface
{
    public function createPerson(array $data)
    {
        return Person::create($data);
    }

    public function updatePerson(int $id, array $data)
    {
        $person = $this->findPersonById($id);
        if ($person) {
            $person->update($data);
        }
        return $person;
    }

    public function findPersonById(int $id)
    {
        return Person::find($id);
    }

    public function findPersonByMobile(string $mobile)
    {
        return Person::where('mobile_1', $mobile)->first();
    }
}
