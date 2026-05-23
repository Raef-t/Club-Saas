<?php
namespace Modules\ClubManager\Repositories;

use Modules\ClubManager\Models\Club;

class EloquentClubRepository implements ClubRepositoryInterface
{
    public function all() { return Club::all(); }
    public function find($id) { return Club::findOrFail($id); }
    public function create(array $data) { return Club::create($data); }
    public function update($id, array $data) {
        $record = $this->find($id);
        $record->update($data);
        return $record;
    }
    public function delete($id) { return $this->find($id)->delete(); }
}
