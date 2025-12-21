<?php

declare(strict_types=1);

namespace DartSass\Exceptions;

use Exception;

class SyntaxException extends Exception
{
    public function __construct(string $message, int $line, int $column)
    {
        parent::__construct("$message at line $line, column $column");
    }
}
