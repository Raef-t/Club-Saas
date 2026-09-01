<?php

namespace Modules\ClubManager\Repositories;

interface BranchRepositoryInterface
{
    public function all(array $filters = []);
    public function find($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function getTrashed(array $filters = []);
    public function restore($id);
}
