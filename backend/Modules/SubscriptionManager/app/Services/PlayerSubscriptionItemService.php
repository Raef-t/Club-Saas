<?php
namespace Modules\SubscriptionManager\Services;

use Modules\SubscriptionManager\Repositories\PlayerSubscriptionItemRepositoryInterface;

class PlayerSubscriptionItemService
{
    protected $repository;

    public function __construct(PlayerSubscriptionItemRepositoryInterface $repository) {
        $this->repository = $repository;
    }

    public function getAll() { return $this->repository->all(); }
    public function getById($id) { return $this->repository->find($id); }
    public function create(array $data) {
        $record = $this->repository->create($data);
        $this->syncParentStatus($record->player_subscription_id);
        return $record;
    }
    public function update($id, array $data) {
        $record = $this->repository->update($id, $data);
        $this->syncParentStatus($record->player_subscription_id);
        return $record;
    }
    public function delete($id) {
        $record = $this->repository->find($id);
        $subId = $record?->player_subscription_id;
        $result = $this->repository->delete($id);
        $this->syncParentStatus($subId);
        return $result;
    }

    private function syncParentStatus(?int $subscriptionId): void
    {
        if ($subscriptionId) {
            $parentSub = \Modules\SubscriptionManager\Models\PlayerSubscription::find($subscriptionId);
            if ($parentSub) {
                app(\Modules\SubscriptionManager\Services\SubscriptionService::class)->syncSingleSubscriptionStatus($parentSub);
            }
        }
    }
}
