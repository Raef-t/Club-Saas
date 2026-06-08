<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Event;
use Spatie\Activitylog\Facades\Activity;

class GlobalAuditSubscriber
{
    /**
     * Models that should not be tracked to prevent infinite loops and noise.
     */
    protected $excludedModels = [
        \App\Models\Activity::class,
        \Spatie\Activitylog\Models\Activity::class,
        \Illuminate\Notifications\DatabaseNotification::class,
        \Laravel\Sanctum\PersonalAccessToken::class,
    ];

    /**
     * Register the listeners for the subscriber.
     *
     * @param  \Illuminate\Events\Dispatcher  $events
     * @return void
     */
    public function subscribe($events)
    {
        $events->listen(
            ['eloquent.created: *', 'eloquent.updated: *', 'eloquent.deleted: *'],
            [$this, 'handleEloquentEvent']
        );
    }

    /**
     * Handle the event.
     *
     * @param string $eventName
     * @param array $data
     * @return void
     */
    public function handleEloquentEvent(string $eventName, array $data)
    {
        $model = $data[0] ?? null;

        if (!$model instanceof \Illuminate\Database\Eloquent\Model) {
            return;
        }

        // Prevent recursion and noise
        if (in_array(get_class($model), $this->excludedModels)) {
            return;
        }

        // Parse event type (created, updated, deleted)
        preg_match('/eloquent\.(created|updated|deleted):/', $eventName, $matches);
        $action = $matches[1] ?? 'unknown';

        if ($action === 'unknown') {
            return;
        }

        // For updates, ensure there are actual changes (No-op check)
        if ($action === 'updated') {
            if (empty($model->getChanges())) {
                return;
            }
        }

        // Determine branch_id based on strict priority
        $branchId = $this->resolveBranchId($model);

        // Determine user_id
        $user = \Illuminate\Support\Facades\Auth::user();
        $userId = $user ? $user->id : null;

        // Prepare properties
        $properties = [];
        if ($action === 'created') {
            $properties = ['attributes' => $model->getAttributes()];
        } elseif ($action === 'updated') {
            $properties = [
                'attributes' => $model->getChanges(),
                'old' => array_intersect_key($model->getOriginal(), $model->getChanges()),
            ];
        } elseif ($action === 'deleted') {
            $properties = ['old' => $model->getAttributes()];
        }

        // Log the activity manually
        activity('system_audit')
            ->performedOn($model)
            ->causedBy($user)
            ->withProperties($properties)
            ->tap(function (\App\Models\Activity $activity) use ($branchId, $userId, $action) {
                $activity->branch_id = $branchId;
                $activity->user_id = $userId;
                $activity->event = $action;
            })
            ->log("Model {$action}");
    }

    /**
     * Resolve the branch_id following strict priority order.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return int|null
     */
    protected function resolveBranchId($model)
    {
        $user = \Illuminate\Support\Facades\Auth::user();

        // 1. Check if auth user has a direct branch_id property
        if ($user && isset($user->branch_id)) {
            return $user->branch_id;
        }

        // 2. Check if the model being modified has a branch_id property
        if (isset($model->branch_id)) {
            return $model->branch_id;
        }

        // 3. Null if no context exists
        return null;
    }
}
