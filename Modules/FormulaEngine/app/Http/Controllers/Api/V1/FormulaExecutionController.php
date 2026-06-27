<?php

namespace Modules\FormulaEngine\Http\Controllers\Api\V1;

use Modules\Core\Http\Controllers\Api\BaseController;
use Modules\FormulaEngine\Services\FormulaEngineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * FormulaExecutionController — Runtime formula evaluation endpoints.
 *
 * Golden rule: nothing is computed until an API request arrives.
 * All results are returned on-demand and never stored.
 */
class FormulaExecutionController extends BaseController
{
    public function __construct(
        private readonly FormulaEngineService $engine
    ) {}

    /**
     * POST /v1/formulas/{key}/evaluate
     *
     * Evaluate a single formula for a member at request time.
     *
     * Body:
     * {
     *   "member_id": 5,           // optional if all variables are input-based
     *   "input": {                // direct values for 'input' source_type variables
     *     "weight": 80,
     *     "activity_factor": 1.55
     *   }
     * }
     */
    public function evaluate(Request $request, string $key): JsonResponse
    {
        $request->validate([
            'member_id' => 'nullable|integer|exists:members,id',
            'input'     => 'nullable|array',
        ]);

        try {
            $result = $this->engine->evaluate(
                formulaKey: $key,
                memberId:   $request->integer('member_id') ?: null,
                inputData:  $request->input('input', []),
            );

            return $this->successResponse($result, 'Formula evaluated successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorResponse("Formula [{$key}] not found or is inactive.", 404);
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * POST /v1/formulas/evaluate-batch
     *
     * Evaluate multiple formulas in one request.
     * Formulas that depend on each other are resolved automatically (computed source_type).
     *
     * Body:
     * {
     *   "member_id": 5,
     *   "keys": ["bmi", "bmr", "tdee"],
     *   "input": {
     *     "activity_factor": 1.55
     *   }
     * }
     */
    public function evaluateBatch(Request $request): JsonResponse
    {
        $request->validate([
            'member_id' => 'nullable|integer|exists:members,id',
            'keys'      => 'required|array|min:1',
            'keys.*'    => 'required|string',
            'input'     => 'nullable|array',
        ]);

        $results = $this->engine->evaluateBatch(
            formulaKeys: $request->input('keys'),
            memberId:    $request->integer('member_id') ?: null,
            inputData:   $request->input('input', []),
        );

        return $this->successResponse($results, 'Batch evaluation completed');
    }
}
