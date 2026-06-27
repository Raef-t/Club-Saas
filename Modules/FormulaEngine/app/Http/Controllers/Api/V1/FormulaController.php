<?php

namespace Modules\FormulaEngine\Http\Controllers\Api\V1;

use Modules\Core\Http\Controllers\Api\BaseController;
use Modules\FormulaEngine\Http\Requests\StoreFormulaRequest;
use Modules\FormulaEngine\Http\Requests\UpdateFormulaRequest;
use Modules\FormulaEngine\Models\Formula;
use Modules\FormulaEngine\Services\FormulaEngineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * FormulaController — Admin CRUD for formula definitions.
 */
class FormulaController extends BaseController
{
    public function __construct(
        private readonly FormulaEngineService $engine
    ) {}

    /**
     * GET /v1/admin/formulas
     * List all formulas, optionally filtered by category.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Formula::with('variables');

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }
        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        return $this->successResponse($query->orderBy('name')->get(), 'Formulas retrieved successfully');
    }

    /**
     * POST /v1/admin/formulas
     * Create a new formula with its variables.
     */
    public function store(StoreFormulaRequest $request): JsonResponse
    {
        $formula = Formula::create([
            'name'        => $request->name,
            'key'         => $request->key,
            'expression'  => $request->expression,
            'description' => $request->description,
            'category'    => $request->category ?? 'measurement',
            'return_type' => $request->return_type ?? 'float',
            'unit'        => $request->unit,
            'is_active'   => $request->is_active ?? true,
            'is_system'   => false, // Only seeder can set this
        ]);

        if ($request->has('variables')) {
            foreach ($request->variables as $var) {
                $formula->variables()->create([
                    'variable_name'        => $var['variable_name'],
                    'source_type'          => $var['source_type'],
                    'db_column'            => $var['db_column'] ?? null,
                    'computed_formula_key' => $var['computed_formula_key'] ?? null,
                    'is_required'          => $var['is_required'] ?? true,
                    'default_value'        => $var['default_value'] ?? null,
                ]);
            }
        }

        return $this->successResponse($formula->load('variables'), 'Formula created successfully', 201);
    }

    /**
     * GET /v1/admin/formulas/{formula}
     * Get a single formula with its variables.
     */
    public function show(int $id): JsonResponse
    {
        $formula = Formula::with('variables')->findOrFail($id);
        return $this->successResponse($formula, 'Formula retrieved successfully');
    }

    /**
     * PUT /v1/admin/formulas/{formula}
     * Update a formula. Protected system formulas cannot be modified.
     */
    public function update(UpdateFormulaRequest $request, int $id): JsonResponse
    {
        $formula = Formula::findOrFail($id);

        if ($formula->is_system) {
            return $this->errorResponse('System formulas cannot be modified.', 403);
        }

        $formula->update($request->only([
            'name', 'key', 'expression', 'description',
            'category', 'return_type', 'unit', 'is_active',
        ]));

        // Replace variables if provided
        if ($request->has('variables')) {
            $formula->variables()->delete();
            foreach ($request->variables as $var) {
                $formula->variables()->create([
                    'variable_name'        => $var['variable_name'],
                    'source_type'          => $var['source_type'],
                    'db_column'            => $var['db_column'] ?? null,
                    'computed_formula_key' => $var['computed_formula_key'] ?? null,
                    'is_required'          => $var['is_required'] ?? true,
                    'default_value'        => $var['default_value'] ?? null,
                ]);
            }
        }

        return $this->successResponse($formula->load('variables'), 'Formula updated successfully');
    }

    /**
     * DELETE /v1/admin/formulas/{formula}
     * Delete a formula. System formulas are protected.
     */
    public function destroy(int $id): JsonResponse
    {
        $formula = Formula::findOrFail($id);

        if ($formula->is_system) {
            return $this->errorResponse('System formulas cannot be deleted.', 403);
        }

        $formula->delete();
        return $this->successResponse(null, 'Formula deleted successfully');
    }

    /**
     * POST /v1/admin/formulas/validate
     * Validate a formula expression without saving (dry run).
     */
    public function validateFormula(Request $request): JsonResponse
    {
        $request->validate([
            'expression'     => 'required|string',
            'variable_names' => 'nullable|array',
        ]);

        try {
            $this->engine->validateExpression(
                $request->expression,
                $request->variable_names ?? []
            );
            return $this->successResponse(['valid' => true], 'Expression is valid');
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }
}
