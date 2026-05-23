<?php
namespace Modules\SubscriptionManager\Repositories;

use Modules\SubscriptionManager\Models\SubscriptionFreeze;

class EloquentSubscriptionFreezeRepository implements SubscriptionFreezeRepositoryInterface
{
    public function all() { return SubscriptionFreeze::all(); }
    public function find($id) { return SubscriptionFreeze::findOrFail($id); }
    public function create(array $data) { return SubscriptionFreeze::create($data); }
    public function update($id, array $data) {
        $record = $this->find($id);
        $record->update($data);
        return $record;
    }
    public function delete($id) { return $this->find($id)->delete(); }
}
