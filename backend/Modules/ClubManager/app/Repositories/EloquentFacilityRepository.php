<?php

namespace Modules\ClubManager\Repositories;

use Modules\ClubManager\Models\Facility;

class EloquentFacilityRepository implements FacilityRepositoryInterface
{
    public function all(array $filters = [])
    {
        $query = Facility::with('branch');
        if (!isset($filters['per_page']) || $filters['per_page'] === 'all' || (isset($filters['paginate']) && filter_var($filters['paginate'], FILTER_VALIDATE_BOOLEAN) === false) || (isset($filters['all']) && filter_var($filters['all'], FILTER_VALIDATE_BOOLEAN) === true)) {
            return $query->get();
        }
        $perPage = min(max((int)$filters['per_page'], 1), 100);
        return $query->paginate($perPage);
    }

    public function find($id)
    {
        return Facility::findOrFail($id);
    }

    public function create(array $data)
    {
        return Facility::create($data);
    }

    public function update($id, array $data)
    {
        $facility = $this->find($id);
        $facility->update($data);
        return $facility;
    }

    public function delete($id)
    {
        $facility = $this->find($id);
        return $facility->delete();
    }

    public function getTrashed(array $filters = [])
    {
        $query = Facility::onlyTrashed();
        if (!isset($filters['per_page']) || $filters['per_page'] === 'all' || (isset($filters['paginate']) && filter_var($filters['paginate'], FILTER_VALIDATE_BOOLEAN) === false) || (isset($filters['all']) && filter_var($filters['all'], FILTER_VALIDATE_BOOLEAN) === true)) {
            return $query->get();
        }
        $perPage = min(max((int)$filters['per_page'], 1), 100);
        return $query->paginate($perPage);
    }

    public function restore($id)
    {
        $facility = Facility::onlyTrashed()->findOrFail($id);
        $facility->restore();
        return $facility;
    }
}
