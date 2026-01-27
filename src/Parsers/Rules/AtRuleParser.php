<?php

declare(strict_types=1);

namespace DartSass\Parsers\Rules;

use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Tokens\TokenStreamHelper;
use DartSass\Parsers\Tokens\TokenStreamInterface;

abstract class AtRuleParser
{
    use TokenStreamHelper;

    public function __construct(protected readonly TokenStreamInterface $stream) {}

    abstract public function parse(): AstNode;

    protected function getStream(): TokenStreamInterface
    {
        return $this->stream;
    }

    protected function incrementTokenIndex(): void
    {
        $this->setTokenIndex($this->getTokenIndex() + 1);
    }
}
