<?php
namespace Modules\MemberManager\Repositories;

use Modules\MemberManager\Models\MemberHealthProfile;

class EloquentMemberHealthProfileRepository implements MemberHealthProfileRepositoryInterface
{
    public function all() { return MemberHealthProfile::all(); }
    public function find($id) { return MemberHealthProfile::findOrFail($id); }
    public function create(array $data) { return MemberHealthProfile::create($data); }
    public function update($id, array $data) {
        $record = $this->find($id);
        $record->update($data);
        return $record;
    }
    public function delete($id) { return $this->find($id)->delete(); }
}
