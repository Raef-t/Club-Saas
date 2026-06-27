<?php

namespace Modules\FormulaEngine\Exceptions;

use RuntimeException;

class CircularDependencyException extends RuntimeException
{
    public function __construct(string $formulaKey, array $chain = [])
    {
        $chainStr = !empty($chain) ? implode(' → ', $chain) . ' → ' . $formulaKey : $formulaKey;
        parent::__construct(
            "Circular dependency detected while evaluating formula [{$formulaKey}]. " .
            "Dependency chain: {$chainStr}"
        );
    }
}
