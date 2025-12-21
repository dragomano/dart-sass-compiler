<?php

namespace DartSass\Parsers;

interface LexerInterface
{
    public function tokenize(string $input): TokenStreamInterface;
}
