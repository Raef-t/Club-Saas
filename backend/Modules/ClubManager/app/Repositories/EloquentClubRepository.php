<?php
namespace Modules\ClubManager\Repositories;

use Modules\ClubManager\Models\Club;

class EloquentClubRepository implements ClubRepositoryInterface
{
    public function all(array $filters = [])
    {
        $query = Club::query();
        if (!isset($filters['per_page']) || $filters['per_page'] === 'all') {
            return $query->get();
        }
        $perPage = min(max((int)$filters['per_page'], 1), 100);
        return $query->paginate($perPage);

    }
    public function find($id) { return Club::findOrFail($id); }
    public function create(array $data) { return Club::create($data); }
    public function update($id, array $data) {
        $record = $this->find($id);
        $record->update($data);
        return $record;
    }
    public function delete($id) { return $this->find($id)->delete(); }
    public function getTrashed(array $filters = [])
    {
        $query = Club::onlyTrashed();
        if (!isset($filters['per_page']) || $filters['per_page'] === 'all') {
            return $query->get();
        }
        $perPage = min(max((int)$filters['per_page'], 1), 100);
        return $query->paginate($perPage);

    }
    public function restore($id) {
        $club = Club::onlyTrashed()->findOrFail($id);
        $club->restore();
        return $club;
    }
}
