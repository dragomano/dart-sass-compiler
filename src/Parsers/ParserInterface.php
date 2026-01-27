<?php

declare(strict_types=1);

namespace DartSass\Parsers;

interface ParserInterface
{
    public function parse(): mixed;
}
