<?php

namespace Modules\FormulaEngine\Exceptions;

use RuntimeException;

class UnsafeFunctionException extends RuntimeException
{
    public function __construct(string $functionName)
    {
        parent::__construct(
            "Unsafe or unknown function detected: [{$functionName}]. " .
            "Only whitelisted mathematical functions are allowed."
        );
    }
}
