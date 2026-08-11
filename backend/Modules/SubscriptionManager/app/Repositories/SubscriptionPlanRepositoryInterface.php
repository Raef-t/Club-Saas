<?php

namespace Modules\SubscriptionManager\Repositories;

interface SubscriptionPlanRepositoryInterface
{
    public function all();
    public function find(int $id);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id, array $options = []);
    public function getDeleteCheckInfo(int $id): array;
    public function getTrashed();
    public function restore(int $id);
}
