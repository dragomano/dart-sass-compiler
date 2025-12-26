<?php

declare(strict_types=1);

namespace DartSass\Exceptions;

use UnexpectedValueException;

class LexicalException extends UnexpectedValueException
{
    public function __construct(string $message, protected int $line = 0, private readonly int $column = 0)
    {
        $formattedMessage = $this->formatMessage($message);

        parent::__construct($formattedMessage);
    }

    private function formatMessage(string $message): string
    {
        if ($this->line === 0) {
            return $message;
        }

        return "$message at line $this->line, column $this->column";
    }
}
