<?php
namespace Modules\Sports\Repositories;

use Modules\Sports\Models\StaffActivity;

class EloquentStaffActivityRepository implements StaffActivityRepositoryInterface
{
    public function all() { return StaffActivity::all(); }
    public function find($id) { return StaffActivity::findOrFail($id); }
    public function create(array $data) { return StaffActivity::create($data); }
    public function update($id, array $data) {
        $record = $this->find($id);
        $record->update($data);
        return $record;
    }
    public function delete($id) { return $this->find($id)->delete(); }
}
