<?php

namespace Modules\SubscriptionManager\Repositories;

use Modules\SubscriptionManager\Models\PlayerSubscription;

class EloquentPlayerSubscriptionRepository implements PlayerSubscriptionRepositoryInterface
{
    public function all()
    {
        return PlayerSubscription::with(['member.person', 'plan'])->get();
    }

    public function find(int $id)
    {
        return PlayerSubscription::with(['member.person', 'plan', 'freezes'])->findOrFail($id);
    }

    public function create(array $data)
    {
        return PlayerSubscription::create($data);
    }

    public function findActiveByMember(int $memberId)
    {
        return PlayerSubscription::where('member_id', $memberId)
            ->where('status', 'active')
            ->first();
    }
}
