<?php

namespace Modules\SubscriptionManager\Repositories;

interface PlayerSubscriptionRepositoryInterface
{
    public function all();
    public function find(int $id);
    public function create(array $data);
    public function findActiveByMember(int $memberId);
    public function findActiveByMemberAndPlan(int $memberId, int $planId);
}
