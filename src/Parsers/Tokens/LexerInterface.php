<?php

declare(strict_types=1);

namespace DartSass\Parsers\Tokens;

interface LexerInterface
{
    public function tokenize(string $input): TokenStreamInterface;
}
