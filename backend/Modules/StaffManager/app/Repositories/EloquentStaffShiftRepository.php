<?php
namespace Modules\StaffManager\Repositories;

use Modules\StaffManager\Models\StaffShift;

class EloquentStaffShiftRepository implements StaffShiftRepositoryInterface
{
    public function all() { return StaffShift::all(); }
    public function find($id) { return StaffShift::findOrFail($id); }
    public function create(array $data) { return StaffShift::create($data); }
    public function update($id, array $data) {
        $record = $this->find($id);
        $record->update($data);
        return $record;
    }
    public function delete($id) { return $this->find($id)->delete(); }
}
