<?php
namespace Modules\ClubManager\Repositories;

use Modules\ClubManager\Models\Club;

class EloquentClubRepository implements ClubRepositoryInterface
{
    public function all(array $filters = [])
    {
        $query = Club::query();
        if ((isset($filters['per_page']) && $filters['per_page'] === 'all') || (isset($filters['paginate']) && filter_var($filters['paginate'], FILTER_VALIDATE_BOOLEAN) === false) || (isset($filters['all']) && filter_var($filters['all'], FILTER_VALIDATE_BOOLEAN) === true)) {
            return $query->get();
        }
        $perPage = isset($filters['per_page']) ? min(max((int)$filters['per_page'], 1), 100) : 15;
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
        if ((isset($filters['per_page']) && $filters['per_page'] === 'all') || (isset($filters['paginate']) && filter_var($filters['paginate'], FILTER_VALIDATE_BOOLEAN) === false) || (isset($filters['all']) && filter_var($filters['all'], FILTER_VALIDATE_BOOLEAN) === true)) {
            return $query->get();
        }
        $perPage = isset($filters['per_page']) ? min(max((int)$filters['per_page'], 1), 100) : 15;
        return $query->paginate($perPage);
    }
    public function restore($id) {
        $club = Club::onlyTrashed()->findOrFail($id);
        $club->restore();
        return $club;
    }
}
