<?php

declare(strict_types=1);

namespace DartSass\Exceptions;

final class InvalidColorException extends LexicalException
{
    public function __construct(string $color, int $line, int $column)
    {
        parent::__construct(sprintf('Invalid color value (%s)', $color), $line, $column);
    }
}
