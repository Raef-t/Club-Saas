<?php

namespace Modules\Sports\Repositories;

use Modules\Sports\Models\SportSession;
use Illuminate\Database\Eloquent\Collection;

class EloquentSessionRepository implements SessionRepositoryInterface
{
    public function all(): Collection
    {
        return SportSession::with('activity')->latest('start_time')->get();
    }

    public function find(int $id): ?SportSession
    {
        return SportSession::with('activity')->findOrFail($id);
    }

    public function create(array $data): SportSession
    {
        return SportSession::create($data);
    }

    public function update(int $id, array $data): SportSession
    {
        $session = SportSession::findOrFail($id);
        $session->update($data);
        return $session->fresh('activity');
    }

    public function delete(int $id): bool
    {
        $session = SportSession::findOrFail($id);
        return (bool) $session->delete();
    }

    public function getByBranchAndDate(int $branchId, string $date): Collection
    {
        return SportSession::with('activity')
            ->forBranch($branchId)
            ->forDate($date)
            ->orderBy('start_time')
            ->get();
    }

    public function getWeeklySchedule(int $branchId, string $startDate, string $endDate): Collection
    {
        return SportSession::with('activity')
            ->forBranch($branchId)
            ->forWeek($startDate, $endDate)
            ->orderBy('start_time')
            ->get();
    }
}
