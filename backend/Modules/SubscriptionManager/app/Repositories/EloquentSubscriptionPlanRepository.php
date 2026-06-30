<?php

namespace Modules\SubscriptionManager\Repositories;

use Modules\SubscriptionManager\Models\SubscriptionPlan;

class EloquentSubscriptionPlanRepository implements SubscriptionPlanRepositoryInterface
{
    public function all()
    {
        return SubscriptionPlan::active()->get();
    }

    public function find(int $id)
    {
        return SubscriptionPlan::findOrFail($id);
    }

    public function create(array $data)
    {
        return SubscriptionPlan::create($data);
    }

    public function update(int $id, array $data)
    {
        $plan = $this->find($id);
        $plan->update($data);
        return $plan;
    }

    public function delete(int $id)
    {
        $plan = $this->find($id);
        return $plan->delete();
    }
}
