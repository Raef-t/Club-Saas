<?php

namespace Modules\Sports\Repositories;

use Modules\Sports\Models\SportSession;
use Illuminate\Database\Eloquent\Collection;

interface SessionRepositoryInterface
{
    public function all(): Collection;

    public function find(int $id): ?SportSession;

    public function create(array $data): SportSession;

    public function update(int $id, array $data): SportSession;

    public function delete(int $id): bool;

    public function getByBranchAndDate(int $branchId, string $date): Collection;

    public function getWeeklySchedule(int $branchId, string $startDate, string $endDate): Collection;
}
