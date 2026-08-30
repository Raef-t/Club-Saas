<?php

namespace Modules\ClubManager\Repositories;

use Modules\ClubManager\Models\Facility;

class EloquentFacilityRepository implements FacilityRepositoryInterface
{
    public function all(int $perPage = 15)
    {
        return Facility::with('branch')->paginate($perPage);
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

    public function getTrashed(int $perPage = 15)
    {
        return Facility::onlyTrashed()->with('branch')->paginate($perPage);
    }

    public function restore($id)
    {
        $facility = Facility::onlyTrashed()->findOrFail($id);
        $facility->restore();
        return $facility;
    }
}
