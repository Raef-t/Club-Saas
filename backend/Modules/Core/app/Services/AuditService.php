<?php

namespace Modules\Core\Services;

use App\Models\Activity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AuditService
{
    /**
     * Map friendly module names to Fully Qualified Class Names (FQCN)
     */
    protected array $moduleMap = [
        'member'            => \Modules\MemberManager\Models\Member::class,
        'subscription'      => \Modules\SubscriptionManager\Models\PlayerSubscription::class,
        'subscription_plan' => \Modules\SubscriptionManager\Models\SubscriptionPlan::class,
        'invoice'           => \Modules\SubscriptionManager\Models\Invoice::class,
        'staff'             => \Modules\StaffManager\Models\Staff::class,
        'branch'            => \Modules\ClubManager\Models\Branch::class,
        'user'              => \Modules\Authentication\Models\User::class,
        'person'            => \Modules\Authentication\Models\Person::class,
    ];

    /**
     * Get paginated audit logs with flexible filtering
     */
    public function getPaginatedLogs(array $filters, ?int $perPage = null): LengthAwarePaginator|\Illuminate\Support\Collection
    {
        $query = Activity::query()
            ->with(['causer.person', 'user.person']);

        // 1. Filter by Branch ID
        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        // 2. Filter by User / Causer ID
        if (!empty($filters['user_id'])) {
            $userId = $filters['user_id'];
            $query->where(function (Builder $q) use ($userId) {
                $q->where('user_id', $userId)
                  ->orWhere(function (Builder $q2) use ($userId) {
                      $q2->where('causer_id', $userId);
                  });
            });
        }

        // 3. Filter by Event (created, updated, deleted)
        if (!empty($filters['event'])) {
            $query->where('event', $filters['event']);
        }

        // 4. Filter by Log Name (system_audit, finance, etc.)
        if (!empty($filters['log_name'])) {
            $query->where('log_name', $filters['log_name']);
        }

        // 5. Filter by Module / Subject Type
        if (!empty($filters['module'])) {
            $moduleKey = strtolower($filters['module']);
            $targetClass = $this->moduleMap[$moduleKey] ?? $filters['module'];

            if (str_contains($targetClass, '\\')) {
                $query->where('subject_type', $targetClass);
            } else {
                $query->where('subject_type', 'like', "%{$targetClass}%");
            }
        }

        // 6. Filter by Subject ID
        if (!empty($filters['subject_id'])) {
            $query->where('subject_id', $filters['subject_id']);
        }

        // 7. Date Range Filters
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        // 8. Search Keyword (in description or JSON properties)
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('properties', 'like', "%{$search}%")
                  ->orWhere('event', 'like', "%{$search}%");
            });
        }

        // 9. Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $allowedSorts = ['id', 'created_at', 'event', 'log_name', 'branch_id'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }

        $sortOrder = strtolower($filters['sort_order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortOrder);

        if ($perPage === null) {
            return $query->get();
        }

        return $query->paginate(min($perPage, 100));
    }

    /**
     * Return available modules & metadata for frontend filter options
     */
    public function getFilterMetadata(): array
    {
        $distinctEvents = Activity::select('event')->distinct()->pluck('event')->filter()->values();
        $distinctLogNames = Activity::select('log_name')->distinct()->pluck('log_name')->filter()->values();

        $modules = [];
        foreach ($this->moduleMap as $key => $class) {
            $modules[] = [
                'key'   => $key,
                'label' => class_basename($class),
                'class' => $class,
            ];
        }

        return [
            'modules'    => $modules,
            'events'     => $distinctEvents,
            'log_names'  => $distinctLogNames,
        ];
    }
}
