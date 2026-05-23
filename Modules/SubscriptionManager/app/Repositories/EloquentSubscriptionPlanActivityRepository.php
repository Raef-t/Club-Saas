<?php
namespace Modules\SubscriptionManager\Repositories;

use Modules\SubscriptionManager\Models\SubscriptionPlanActivity;

class EloquentSubscriptionPlanActivityRepository implements SubscriptionPlanActivityRepositoryInterface
{
    public function all() { return SubscriptionPlanActivity::all(); }
    public function find($id) { return SubscriptionPlanActivity::findOrFail($id); }
    public function create(array $data) { return SubscriptionPlanActivity::create($data); }
    public function update($id, array $data) {
        $record = $this->find($id);
        $record->update($data);
        return $record;
    }
    public function delete($id) { return $this->find($id)->delete(); }
}
