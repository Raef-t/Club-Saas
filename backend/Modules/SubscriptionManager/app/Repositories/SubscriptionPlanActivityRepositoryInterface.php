<?php
namespace Modules\SubscriptionManager\Repositories;

interface SubscriptionPlanActivityRepositoryInterface
{
    public function all(int $perPage = 15);
    public function find($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function getTrashed();
    public function restore($id);
}
