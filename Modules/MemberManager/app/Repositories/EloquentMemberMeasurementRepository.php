<?php
namespace Modules\MemberManager\Repositories;

use Modules\MemberManager\Models\MemberMeasurement;

class EloquentMemberMeasurementRepository implements MemberMeasurementRepositoryInterface
{
    public function all() { return MemberMeasurement::all(); }
    public function find($id) { return MemberMeasurement::findOrFail($id); }
    public function create(array $data) { return MemberMeasurement::create($data); }
    public function update($id, array $data) {
        $record = $this->find($id);
        $record->update($data);
        return $record;
    }
    public function delete($id) { return $this->find($id)->delete(); }
}
