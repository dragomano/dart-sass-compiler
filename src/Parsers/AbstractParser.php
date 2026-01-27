<?php

declare(strict_types=1);

namespace DartSass\Parsers;

use DartSass\Parsers\Tokens\TokenAwareParserInterface;
use DartSass\Parsers\Tokens\TokenStreamHelper;
use DartSass\Parsers\Tokens\TokenStreamInterface;

abstract class AbstractParser implements TokenAwareParserInterface, ParserInterface
{
    use TokenStreamHelper;

    public function __construct(private readonly TokenStreamInterface $stream) {}

    abstract public function parse(): mixed;

    protected function getStream(): TokenStreamInterface
    {
        return $this->stream;
    }
}
