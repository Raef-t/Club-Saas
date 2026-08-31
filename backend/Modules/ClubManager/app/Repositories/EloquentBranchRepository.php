<?php

namespace Modules\ClubManager\Repositories;

use Modules\ClubManager\Models\Branch;

class EloquentBranchRepository implements BranchRepositoryInterface
{
    public function all(int $perPage = 15)
    {
        return Branch::paginate($perPage);
    }

    public function find($id)
    {
        return Branch::findOrFail($id);
    }

    public function create(array $data)
    {
        return Branch::create($data);
    }

    public function update($id, array $data)
    {
        $branch = $this->find($id);
        $branch->update($data);
        return $branch;
    }

    public function delete($id)
    {
        $branch = $this->find($id);
        return $branch->delete();
    }

    public function getTrashed(int $perPage = 15)
    {
        return Branch::onlyTrashed()->paginate($perPage);
    }

    public function restore($id)
    {
        $branch = Branch::onlyTrashed()->findOrFail($id);
        $branch->restore();
        return $branch;
    }
}
