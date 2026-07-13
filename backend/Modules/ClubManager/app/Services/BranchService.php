<?php

namespace Modules\ClubManager\Services;

use Modules\ClubManager\Models\Branch;
use Modules\ClubManager\Repositories\BranchRepositoryInterface;
use Modules\Core\Exceptions\CannotDeleteException;

class BranchService
{
    protected $repository;

    public function __construct(BranchRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get all branches.
     */
    public function getAllBranches()
    {
        return $this->repository->all();
    }

    /**
     * Create a new branch.
     */
    public function createBranch(array $data)
    {
        return $this->repository->create($data);
    }

    /**
     * Get a specific branch.
     */
    public function getBranchById($id)
    {
        return $this->repository->find($id);
    }

    /**
     * Update a branch.
     */
    public function updateBranch($id, array $data)
    {
        return $this->repository->update($id, $data);
    }

    /**
     * Delete a branch only if it has no impact on the system.
     * A branch cannot be deleted if it has members, subscriptions, invoices, or leads.
     *
     * @throws CannotDeleteException
     */
    public function deleteBranch($id): void
    {
        $branch = Branch::findOrFail($id);

        // Count all related records that block deletion
        $membersCount       = \Modules\MemberManager\Models\Member::where('branch_id', $id)->count();
        $subscriptionsCount = \Modules\SubscriptionManager\Models\PlayerSubscription::where('branch_id', $id)->count();
        $invoicesCount      = \Modules\SubscriptionManager\Models\Invoice::where('branch_id', $id)->count();

        $blocked = [];

        if ($membersCount > 0) {
            $blocked[] = "يوجد {$membersCount} " . ($membersCount === 1 ? 'عضو' : 'أعضاء') . " مسجل في هذا الفرع";
        }
        if ($subscriptionsCount > 0) {
            $blocked[] = "يوجد {$subscriptionsCount} " . ($subscriptionsCount === 1 ? 'اشتراك' : 'اشتراكات') . " مرتبط بهذا الفرع";
        }
        if ($invoicesCount > 0) {
            $blocked[] = "يوجد {$invoicesCount} " . ($invoicesCount === 1 ? 'فاتورة' : 'فواتير') . " مرتبطة بهذا الفرع";
        }

        if (!empty($blocked)) {
            $reasons = implode('، ', $blocked);
            throw new CannotDeleteException(
                "لا يمكن حذف الفرع لأن: {$reasons}. يُنصح بتعطيل الفرع بدلاً من حذفه للحفاظ على سلامة البيانات.",
                [
                    'members_count'       => $membersCount,
                    'subscriptions_count' => $subscriptionsCount,
                    'invoices_count'      => $invoicesCount,
                ]
            );
        }

        $this->repository->delete($id);
    }

    /**
     * Toggle the active status of a branch.
     */
    public function toggleStatus($id)
    {
        $branch = $this->getBranchById($id);
        $branch->update(['is_active' => !$branch->is_active]);
        return $branch;
    }

    /**
     * Get statistics for branches.
     */
    public function getStats()
    {
        $query = \Modules\ClubManager\Models\Branch::query();

        return [
            'total_branches'  => (clone $query)->count(),
            'active_branches' => (clone $query)->where('is_active', true)->count(),
            'male_branches'   => (clone $query)->where('gender_restriction', 'male')->count(),
            'female_branches' => (clone $query)->where('gender_restriction', 'female')->count(),
            'mixed_branches'  => (clone $query)->where('gender_restriction', 'mixed')->count(),
        ];
    }
}
