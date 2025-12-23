<?php

declare(strict_types=1);

namespace DartSass\Parsers;

interface LexerInterface
{
    public function tokenize(string $input): TokenStreamInterface;
}
