<?php

namespace Modules\MemberManager\Http\Controllers\Api\V1\Me;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\MemberManager\Http\Requests\StoreEvaluationRequest;
use Modules\MemberManager\Services\MemberEvaluationService;

class EvaluationController extends Controller
{
    protected $evaluationService;

    public function __construct(MemberEvaluationService $evaluationService)
    {
        $this->evaluationService = $evaluationService;
    }

    /**
     * Store a newly created evaluation in storage.
     *
     * @param StoreEvaluationRequest $request
     * @return JsonResponse
     */
    public function store(StoreEvaluationRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        
        $memberId = $user->member->id ?? null;

        if (!$memberId) {
            return response()->json(['message' => 'User is not associated with a member profile.'], 403);
        }

        try {
            $evaluation = $this->evaluationService->createEvaluation(
                $memberId,
                $request->input('evaluatable_type'),
                $request->input('evaluatable_id'),
                $request->input('rating'),
                $request->input('comment')
            );

            return response()->json([
                'message' => 'Evaluation submitted successfully.',
                'data' => $evaluation
            ], 201);
            
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }
}
