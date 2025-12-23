<?php

declare(strict_types=1);

namespace DartSass\Parsers\Rules;

use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Token;
use DartSass\Parsers\TokenAwareParserInterface;

abstract class AtRuleParser
{
    public function __construct(protected TokenAwareParserInterface $parser)
    {
    }

    abstract public function parse(): AstNode;

    protected function consume(string $type): Token
    {
        return $this->parser->consume($type);
    }

    protected function peek(string $type): bool
    {
        return $this->parser->peek($type);
    }

    protected function currentToken(): ?Token
    {
        return $this->parser->currentToken();
    }

    protected function getTokenIndex(): int
    {
        return $this->parser->getTokenIndex();
    }

    protected function incrementTokenIndex(): void
    {
        $this->parser->setTokenIndex($this->parser->getTokenIndex() + 1);
    }

    protected function setTokenIndex(int $index): void
    {
        $this->parser->setTokenIndex($index);
    }
}
