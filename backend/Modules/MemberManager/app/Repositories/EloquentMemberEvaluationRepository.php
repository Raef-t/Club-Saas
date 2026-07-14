<?php

namespace Modules\MemberManager\Repositories;

use Modules\MemberManager\Models\MemberEvaluation;

class EloquentMemberEvaluationRepository implements MemberEvaluationRepositoryInterface
{
    public function create(array $data): MemberEvaluation
    {
        return MemberEvaluation::create($data);
    }

    public function hasEvaluatedCoachInMonth(int $memberId, int $coachId, int $month, int $year): bool
    {
        return MemberEvaluation::where('member_id', $memberId)
            ->where('evaluatable_type', \Modules\StaffManager\Models\Staff::class)
            ->where('evaluatable_id', $coachId)
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->exists();
    }

    public function hasEvaluatedSession(int $memberId, int $sessionId): bool
    {
        return MemberEvaluation::where('member_id', $memberId)
            ->where('evaluatable_type', \Modules\Sports\Models\SportSessionTemplate::class)
            ->where('evaluatable_id', $sessionId)
            ->exists();
    }
}
