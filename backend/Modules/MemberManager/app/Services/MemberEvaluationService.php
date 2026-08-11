<?php

namespace Modules\MemberManager\Services;

use Exception;
use Illuminate\Support\Carbon;
use Modules\MemberManager\Models\MemberEvaluation;
use Modules\MemberManager\Repositories\MemberEvaluationRepositoryInterface;

class MemberEvaluationService
{
    protected $repository;

    public function __construct(MemberEvaluationRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Create a new evaluation.
     *
     * @param int $memberId
     * @param string $type
     * @param int $evaluatableId
     * @param int $rating
     * @param string|null $comment
     * @return MemberEvaluation
     * @throws Exception
     */
    public function createEvaluation(int $memberId, string $type, int $evaluatableId, int $rating, ?string $comment): MemberEvaluation
    {
        $evaluatableType = $type === 'session' 
            ? \Modules\Sports\Models\SportSessionTemplate::class 
            : \Modules\StaffManager\Models\Staff::class;

        if ($type === 'coach') {
            $month = Carbon::now()->month;
            $year = Carbon::now()->year;

            if ($this->repository->hasEvaluatedCoachInMonth($memberId, $evaluatableId, $month, $year)) {
                throw new Exception('You have already evaluated this coach this month.');
            }
        } elseif ($type === 'session') {
            if ($this->repository->hasEvaluatedSession($memberId, $evaluatableId)) {
                throw new Exception('You have already evaluated this session.');
            }
        }

        $data = [
            'member_id' => $memberId,
            'evaluatable_type' => $evaluatableType,
            'evaluatable_id' => $evaluatableId,
            'rating' => $rating,
            'comment' => $comment,
        ];

        return $this->repository->create($data);
    }
}
