<?php

declare(strict_types=1);

namespace DartSass\Exceptions;

use UnexpectedValueException;

class LexicalException extends UnexpectedValueException
{
    public function __construct(string $message, protected int $line = 0, private readonly int $column = 0)
    {
        parent::__construct("$message at line $this->line, column $this->column");
    }
}
