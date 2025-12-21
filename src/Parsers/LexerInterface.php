<?php

namespace DartSass\Parsers;

interface LexerInterface
{
    public function tokenize(string $input, Syntax $syntax): TokenStreamInterface;
}
