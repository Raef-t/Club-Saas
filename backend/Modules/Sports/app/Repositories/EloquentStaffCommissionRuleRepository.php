<?php
namespace Modules\Sports\Repositories;

use Modules\Sports\Models\StaffCommissionRule;

class EloquentStaffCommissionRuleRepository implements StaffCommissionRuleRepositoryInterface
{
    public function all(array $filters = [])
    {
        $query = StaffCommissionRule::query();
        if (!isset($filters['per_page']) || $filters['per_page'] === 'all' || (isset($filters['paginate']) && filter_var($filters['paginate'], FILTER_VALIDATE_BOOLEAN) === false) || (isset($filters['all']) && filter_var($filters['all'], FILTER_VALIDATE_BOOLEAN) === true)) {
            return $query->get();
        }
        $perPage = min(max((int)$filters['per_page'], 1), 100);
        return $query->paginate($perPage);
    }
    public function find($id) { return StaffCommissionRule::findOrFail($id); }
    public function create(array $data) { return StaffCommissionRule::create($data); }
    public function update($id, array $data) {
        $record = $this->find($id);
        $record->update($data);
        return $record;
    }
    public function delete($id) { return $this->find($id)->delete(); }
}
