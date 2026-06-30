<?php
namespace Modules\ClubManager\Services;

use Modules\ClubManager\Repositories\ClubRepositoryInterface;

class ClubService
{
    protected $repository;

    public function __construct(ClubRepositoryInterface $repository) {
        $this->repository = $repository;
    }

    public function getAll() { return $this->repository->all(); }
    public function getById($id) { return $this->repository->find($id); }
    public function create(array $data) { return $this->repository->create($data); }
    public function update($id, array $data) { return $this->repository->update($id, $data); }
    public function delete($id) { return $this->repository->delete($id); }
}
