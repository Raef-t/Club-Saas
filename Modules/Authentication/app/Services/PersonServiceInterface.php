<?php

namespace Modules\Authentication\Services;

interface PersonServiceInterface
{
    public function createPerson(array $data);
    public function updatePerson(int $id, array $data);
    public function findPersonById(int $id);
    public function findPersonByMobile(string $mobile);
}
