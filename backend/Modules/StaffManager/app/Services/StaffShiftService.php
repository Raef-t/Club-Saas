<?php
namespace Modules\StaffManager\Services;

use Modules\StaffManager\Repositories\StaffShiftRepositoryInterface;

class StaffShiftService
{
    protected $repository;

    public function __construct(StaffShiftRepositoryInterface $repository) {
        $this->repository = $repository;
    }

    public function getAll() { return $this->repository->all(); }
    public function getById($id) { return $this->repository->find($id); }
    public function create(array $data) { return $this->repository->create($data); }
    public function update($id, array $data) { return $this->repository->update($id, $data); }
    public function delete($id) { return $this->repository->delete($id); }
}
