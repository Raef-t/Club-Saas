<?php

namespace Modules\ClubManager\Repositories;

use Modules\ClubManager\Models\Branch;

class EloquentBranchRepository implements BranchRepositoryInterface
{
    public function all(array $filters = [])
    {
        $query = Branch::query();
        if (!isset($filters['per_page']) || $filters['per_page'] === 'all' || (isset($filters['paginate']) && filter_var($filters['paginate'], FILTER_VALIDATE_BOOLEAN) === false) || (isset($filters['all']) && filter_var($filters['all'], FILTER_VALIDATE_BOOLEAN) === true)) {
            return $query->get();
        }
        $perPage = min(max((int)$filters['per_page'], 1), 100);
        return $query->paginate($perPage);
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

    public function getTrashed(array $filters = [])
    {
        $query = Branch::onlyTrashed();
        if (!isset($filters['per_page']) || $filters['per_page'] === 'all' || (isset($filters['paginate']) && filter_var($filters['paginate'], FILTER_VALIDATE_BOOLEAN) === false) || (isset($filters['all']) && filter_var($filters['all'], FILTER_VALIDATE_BOOLEAN) === true)) {
            return $query->get();
        }
        $perPage = min(max((int)$filters['per_page'], 1), 100);
        return $query->paginate($perPage);
    }

    public function restore($id)
    {
        $branch = Branch::onlyTrashed()->findOrFail($id);
        $branch->restore();
        return $branch;
    }
}
