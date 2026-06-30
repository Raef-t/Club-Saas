<?php

namespace Modules\MemberManager\Repositories;

use Modules\MemberManager\Models\MemberEvaluation;

interface MemberEvaluationRepositoryInterface
{
    public function create(array $data): MemberEvaluation;

    public function hasEvaluatedCoachInMonth(int $memberId, int $coachId, int $month, int $year): bool;

    public function hasEvaluatedSession(int $memberId, int $sessionId): bool;
}
