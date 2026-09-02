<?php
namespace Modules\StaffManager\Repositories;

interface StaffShiftRepositoryInterface
{
    public function all(array $filters = []);
    public function find($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
}
