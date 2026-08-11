<?php

namespace Modules\StaffManager\Repositories;

interface StaffRepositoryInterface
{
    public function all();
    public function find($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function getCoaches();
    public function getTrashed(array $filters = []);
    public function restore(int $id);
}
