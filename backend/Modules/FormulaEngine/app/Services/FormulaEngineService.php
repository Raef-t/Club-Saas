<?php

namespace Modules\FormulaEngine\Services;

use Modules\FormulaEngine\Models\Formula;
use Modules\FormulaEngine\Exceptions\CircularDependencyException;
use Modules\FormulaEngine\Exceptions\InvalidFormulaException;

/**
 * FormulaEngineService
 *
 * The main orchestrator. Coordinates VariableResolver and ExpressionEvaluator
 * to evaluate a formula at runtime in a fully Stateless manner.
 *
 * Golden rule: "If no API request is made, nothing is calculated."
 */
class FormulaEngineService
{
    public function __construct(
        private readonly ExpressionEvaluator $evaluator,
        private readonly VariableResolver    $resolver,
    ) {}

    /**
     * Evaluate a single formula by its key for a given member.
     *
     * @param  string    $formulaKey   e.g. 'bmi'
     * @param  int|null  $memberId     The member to fetch measurements for
     * @param  array     $inputData    Direct values from the request
     * @param  array     &$resolving   Circular dependency tracking stack
     * @return array{formula: string, name: string, result: mixed, unit: string|null, inputs_used: array}
     *
     * @throws CircularDependencyException
     * @throws InvalidFormulaException
     */
    public function evaluate(
        string $formulaKey,
        ?int $memberId,
        array $inputData = [],
        array &$resolving = []
    ): array {
        // ── Circular dependency guard ──────────────────────────────────────
        if (in_array($formulaKey, $resolving, true)) {
            throw new CircularDependencyException($formulaKey, $resolving);
        }
        $resolving[] = $formulaKey;

        // ── Fetch formula with its variables ───────────────────────────────
        $formula = Formula::with('variables')
            ->where('key', $formulaKey)
            ->where('is_active', true)
            ->firstOrFail();

        // ── Resolve all variable values ────────────────────────────────────
        $variables = $this->resolver->resolve(
            $formula->variables,
            $memberId,
            $inputData,
            $resolving
        );

        // ── Evaluate the expression ────────────────────────────────────────
        $rawResult = $this->evaluator->evaluate($formula->expression, $variables);

        // ── Cast result to the declared return type ────────────────────────
        $result = $this->cast($rawResult, $formula->return_type);

        // Remove from resolving stack after successful computation
        $resolving = array_filter($resolving, fn($k) => $k !== $formulaKey);
        $resolving = array_values($resolving);

        return [
            'formula'     => $formula->key,
            'name'        => $formula->name,
            'result'      => $result,
            'unit'        => $formula->unit,
            'category'    => $formula->category,
            'inputs_used' => $variables,
        ];
    }

    /**
     * Evaluate multiple formulas in a single request.
     * Each formula is evaluated independently, but if a formula depends on
     * another (via computed source_type), it is computed automatically.
     *
     * @param  string[]  $formulaKeys
     * @param  int|null  $memberId
     * @param  array     $inputData
     * @return array
     */
    public function evaluateBatch(array $formulaKeys, ?int $memberId, array $inputData = []): array
    {
        $results = [];
        // Shared resolving stack across batch to prevent double computation
        $resolving = [];

        foreach ($formulaKeys as $key) {
            // If this formula was already resolved as a dependency of a previous one, skip
            if (isset($results[$key])) {
                continue;
            }

            try {
                $results[$key] = $this->evaluate($key, $memberId, $inputData, $resolving);
            } catch (\Throwable $e) {
                $results[$key] = [
                    'formula' => $key,
                    'error'   => $e->getMessage(),
                    'result'  => null,
                ];
            }
        }

        return array_values($results);
    }

    /**
     * Validate a formula expression before saving it to the database.
     * Performs a dry run with dummy values.
     *
     * @param  string    $expression
     * @param  string[]  $variableNames
     * @throws InvalidFormulaException|\Modules\FormulaEngine\Exceptions\UnsafeFunctionException
     */
    public function validateExpression(string $expression, array $variableNames): void
    {
        $this->evaluator->validateSyntax($expression, $variableNames);
    }

    /**
     * Cast the raw evaluation result to the formula's declared return type.
     */
    private function cast(mixed $value, string $returnType): mixed
    {
        return match ($returnType) {
            'float'  => round((float) $value, 4),
            'int'    => (int) $value,
            'bool'   => (bool) $value,
            'string' => (string) $value,
            default  => $value,
        };
    }
}
