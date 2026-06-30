<?php
namespace Modules\SubscriptionManager\Services;

use Modules\SubscriptionManager\Repositories\SubscriptionPlanActivityRepositoryInterface;

class SubscriptionPlanActivityService
{
    protected $repository;

    public function __construct(SubscriptionPlanActivityRepositoryInterface $repository) {
        $this->repository = $repository;
    }

    public function getAll(array $filters = []) { 
        if (!empty($filters['branch_id'])) {
            return \Modules\SubscriptionManager\Models\SubscriptionPlanActivity::whereHas('plan', function($q) use ($filters) {
                $q->where('branch_id', $filters['branch_id']);
            })->get();
        }
        return $this->repository->all(); 
    }
    public function getById($id) { return $this->repository->find($id); }
    public function create(array $data) { return $this->repository->create($data); }
    public function update($id, array $data) { return $this->repository->update($id, $data); }
    public function delete($id) { return $this->repository->delete($id); }
}
