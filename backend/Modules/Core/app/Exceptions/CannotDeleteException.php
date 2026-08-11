<?php

namespace Modules\Core\Exceptions;

use Exception;

/**
 * Thrown when a deletion is blocked due to existing linked records.
 * Used across all modules to enforce referential integrity at the service layer.
 */
class CannotDeleteException extends Exception
{
    protected array $details;

    public function __construct(string $message, array $details = [])
    {
        parent::__construct($message);
        $this->details = $details;
    }

    public function getDetails(): array
    {
        return $this->details;
    }
}
