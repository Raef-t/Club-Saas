<?php
namespace Modules\SubscriptionManager\Repositories;

use Modules\SubscriptionManager\Models\PlayerSubscriptionItem;

class EloquentPlayerSubscriptionItemRepository implements PlayerSubscriptionItemRepositoryInterface
{
    public function all() { return PlayerSubscriptionItem::all(); }
    public function find($id) { return PlayerSubscriptionItem::findOrFail($id); }
    public function create(array $data) { return PlayerSubscriptionItem::create($data); }
    public function update($id, array $data) {
        $record = $this->find($id);
        $record->update($data);
        return $record;
    }
    public function delete($id) { return $this->find($id)->delete(); }
}
