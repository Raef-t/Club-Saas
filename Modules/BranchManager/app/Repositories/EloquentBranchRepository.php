<?php

namespace Modules\BranchManager\Repositories;

use Modules\BranchManager\Models\Branch;

class EloquentBranchRepository implements BranchRepositoryInterface
{
    public function all()
    {
        return Branch::all();
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
}
