<?php
namespace Modules\SubscriptionManager\Repositories;

interface SubscriptionPlanActivityRepositoryInterface
{
    public function all();
    public function find($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
}
