<?php

namespace Modules\FormulaEngine\Services;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Modules\FormulaEngine\Exceptions\InvalidFormulaException;
use Modules\FormulaEngine\Exceptions\UnsafeFunctionException;

/**
 * ExpressionEvaluator
 *
 * Wraps Symfony ExpressionLanguage and registers only whitelisted
 * mathematical functions. Never uses eval().
 */
class ExpressionEvaluator
{
    /**
     * Whitelisted mathematical functions allowed in formulas.
     */
    private const ALLOWED_FUNCTIONS = [
        'abs', 'ceil', 'floor', 'round', 'sqrt', 'pow',
        'log', 'log10', 'exp', 'max', 'min', 'fmod', 'pi',
    ];

    private ExpressionLanguage $engine;

    public function __construct()
    {
        $this->engine = new ExpressionLanguage();
        $this->registerWhitelistedFunctions();
    }

    /**
     * Evaluate a formula expression with given variable values.
     *
     * @param  string  $expression  e.g. "weight / pow(height / 100, 2)"
     * @param  array   $variables   e.g. ['weight' => 80, 'height' => 180]
     * @return float|int|bool|string
     *
     * @throws InvalidFormulaException
     */
    public function evaluate(string $expression, array $variables): mixed
    {
        // Validate that no non-whitelisted functions are being used
        $this->guardAgainstUnsafeFunctions($expression);

        try {
            return $this->engine->evaluate($expression, $variables);
        } catch (\Throwable $e) {
            throw new InvalidFormulaException($expression, $e->getMessage());
        }
    }

    /**
     * Validate expression syntax using a dry run with dummy numeric values.
     * Used during formula creation/update validation.
     *
     * @param  string  $expression
     * @param  array   $variableNames  List of variable names the formula uses
     * @throws InvalidFormulaException|UnsafeFunctionException
     */
    public function validateSyntax(string $expression, array $variableNames): void
    {
        $this->guardAgainstUnsafeFunctions($expression);

        // Dry run: assign dummy value 1 to all variables
        $dummyVars = array_fill_keys($variableNames, 1.0);

        try {
            $this->engine->evaluate($expression, $dummyVars);
        } catch (\Throwable $e) {
            throw new InvalidFormulaException($expression, 'Syntax error: ' . $e->getMessage());
        }
    }

    /**
     * Register all whitelisted PHP math functions into the Symfony expression engine.
     */
    private function registerWhitelistedFunctions(): void
    {
        // Symfony ExpressionLanguage::register() requires 3 args:
        // (string $name, callable $compiler, callable $evaluator)
        // compiler: returns PHP source string (for compiled expressions)
        // evaluator: executes the actual computation at runtime

        $this->engine->register('abs',
            fn($arg) => "abs({$arg})",
            fn($args, $x) => abs($x)
        );
        $this->engine->register('ceil',
            fn($arg) => "ceil({$arg})",
            fn($args, $x) => ceil($x)
        );
        $this->engine->register('floor',
            fn($arg) => "floor({$arg})",
            fn($args, $x) => floor($x)
        );
        $this->engine->register('round',
            fn($arg, $p = 2) => "round({$arg}, {$p})",
            fn($args, $x, $p = 2) => round($x, (int) $p)
        );
        $this->engine->register('sqrt',
            fn($arg) => "sqrt({$arg})",
            fn($args, $x) => sqrt($x)
        );
        $this->engine->register('pow',
            fn($base, $exp) => "pow({$base}, {$exp})",
            fn($args, $b, $e) => pow($b, $e)
        );
        $this->engine->register('log',
            fn($arg) => "log({$arg})",
            fn($args, $x) => log($x)
        );
        $this->engine->register('log10',
            fn($arg) => "log10({$arg})",
            fn($args, $x) => log10($x)
        );
        $this->engine->register('exp',
            fn($arg) => "exp({$arg})",
            fn($args, $x) => exp($x)
        );
        $this->engine->register('max',
            fn($a, $b) => "max({$a}, {$b})",
            fn($args, $a, $b) => max($a, $b)
        );
        $this->engine->register('min',
            fn($a, $b) => "min({$a}, {$b})",
            fn($args, $a, $b) => min($a, $b)
        );
        $this->engine->register('fmod',
            fn($a, $b) => "fmod({$a}, {$b})",
            fn($args, $a, $b) => fmod($a, $b)
        );
        $this->engine->register('pi',
            fn() => 'M_PI',
            fn($args) => M_PI
        );
    }

    /**
     * Scan expression for any function calls not in the whitelist.
     *
     * @throws UnsafeFunctionException
     */
    private function guardAgainstUnsafeFunctions(string $expression): void
    {
        // Extract all function-call patterns: word followed by (
        preg_match_all('/\b([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/', $expression, $matches);

        foreach ($matches[1] as $functionName) {
            if (!in_array(strtolower($functionName), self::ALLOWED_FUNCTIONS, true)) {
                throw new UnsafeFunctionException($functionName);
            }
        }
    }
}
