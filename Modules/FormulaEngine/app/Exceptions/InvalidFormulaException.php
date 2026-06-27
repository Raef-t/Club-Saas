<?php

namespace Modules\FormulaEngine\Exceptions;

use RuntimeException;

class InvalidFormulaException extends RuntimeException
{
    public function __construct(string $expression, string $reason = '')
    {
        $message = "Invalid formula expression: [{$expression}]";
        if ($reason) {
            $message .= " — {$reason}";
        }
        parent::__construct($message);
    }
}
