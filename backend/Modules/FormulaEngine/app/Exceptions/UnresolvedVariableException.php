<?php

namespace Modules\FormulaEngine\Exceptions;

use RuntimeException;

class UnresolvedVariableException extends RuntimeException
{
    public function __construct(string $variableName, string $formulaKey = '')
    {
        $context = $formulaKey ? " in formula [{$formulaKey}]" : '';
        parent::__construct(
            "Required variable [{$variableName}]{$context} could not be resolved. " .
            "No value found in request input or member measurements."
        );
    }
}
