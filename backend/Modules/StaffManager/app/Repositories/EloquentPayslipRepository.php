<?php
namespace Modules\StaffManager\Repositories;

use Modules\StaffManager\Models\Payslip;

class EloquentPayslipRepository implements PayslipRepositoryInterface
{
    public function all() { return Payslip::all(); }
    public function find($id) { return Payslip::findOrFail($id); }
    public function create(array $data) { return Payslip::create($data); }
    public function update($id, array $data) {
        $record = $this->find($id);
        $record->update($data);
        return $record;
    }
    public function delete($id) { return $this->find($id)->delete(); }
}
