<?php

namespace Modules\FormulaEngine\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Modules\FormulaEngine\Models\FormulaVariable;
use Modules\FormulaEngine\Exceptions\UnresolvedVariableException;

/**
 * VariableResolver
 *
 * Resolves each formula variable to its actual numeric value from one of three sources:
 *   - 'input':       From the API request input data
 *   - 'measurement': From the latest member_measurements row for this member
 *   - 'computed':    From the result of another formula (recursive via FormulaEngineService)
 */
class VariableResolver
{
    /**
     * Resolve all variables for a formula to their runtime values.
     *
     * @param  Collection<FormulaVariable>  $variables
     * @param  int|null  $memberId
     * @param  array  $inputData       Direct values from the API request
     * @param  array  &$resolving      Circular dependency tracking stack (passed by reference)
     * @return array  ['variable_name' => value]
     */
    public function resolve(
        Collection $variables,
        ?int $memberId,
        array $inputData,
        array &$resolving = []
    ): array {
        $resolved = [];

        // Cache the latest measurement row to avoid N+1 queries
        $latestMeasurement = null;
        if ($memberId && $variables->where('source_type', 'measurement')->isNotEmpty()) {
            $latestMeasurement = DB::table('member_measurements')
                ->where('member_id', $memberId)
                ->orderByDesc('measurement_date')
                ->first();
        }

        foreach ($variables as $variable) {
            $value = match ($variable->source_type) {
                'input'       => $this->resolveFromInput($variable, $inputData),
                'measurement' => $this->resolveFromMeasurement($variable, $latestMeasurement),
                'computed'    => $this->resolveFromComputed($variable, $memberId, $inputData, $resolving),
                default       => null,
            };

            // If value is still null and required, throw
            if (is_null($value) && $variable->is_required) {
                throw new UnresolvedVariableException($variable->variable_name);
            }

            $resolved[$variable->variable_name] = $value ?? (float)($variable->default_value ?? 0);
        }

        return $resolved;
    }

    /**
     * Resolve variable from direct request input.
     */
    private function resolveFromInput(FormulaVariable $variable, array $inputData): mixed
    {
        $value = $inputData[$variable->variable_name] ?? null;

        if (is_null($value) && !is_null($variable->default_value)) {
            return (float) $variable->default_value;
        }

        return is_null($value) ? null : (float) $value;
    }

    /**
     * Resolve variable from the member's latest measurement record.
     */
    private function resolveFromMeasurement(FormulaVariable $variable, ?object $measurement): mixed
    {
        if (!$measurement) {
            return is_null($variable->default_value) ? null : (float) $variable->default_value;
        }

        $column = $variable->db_column ?? $variable->variable_name;
        $value = $measurement->{$column} ?? null;

        if (is_null($value) && !is_null($variable->default_value)) {
            return (float) $variable->default_value;
        }

        return is_null($value) ? null : (float) $value;
    }

    /**
     * Resolve variable by evaluating a dependent formula (recursive).
     * Circular dependency protection is handled by FormulaEngineService via $resolving stack.
     */
    private function resolveFromComputed(
        FormulaVariable $variable,
        ?int $memberId,
        array $inputData,
        array &$resolving
    ): mixed {
        // This calls back into FormulaEngineService — the circular check happens there
        $engine = app(FormulaEngineService::class);

        $result = $engine->evaluate(
            $variable->computed_formula_key,
            $memberId,
            $inputData,
            $resolving
        );

        return $result['result'] ?? null;
    }
}
