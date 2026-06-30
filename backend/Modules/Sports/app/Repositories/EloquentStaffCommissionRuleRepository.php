<?php
namespace Modules\Sports\Repositories;

use Modules\Sports\Models\StaffCommissionRule;

class EloquentStaffCommissionRuleRepository implements StaffCommissionRuleRepositoryInterface
{
    public function all() { return StaffCommissionRule::all(); }
    public function find($id) { return StaffCommissionRule::findOrFail($id); }
    public function create(array $data) { return StaffCommissionRule::create($data); }
    public function update($id, array $data) {
        $record = $this->find($id);
        $record->update($data);
        return $record;
    }
    public function delete($id) { return $this->find($id)->delete(); }
}
