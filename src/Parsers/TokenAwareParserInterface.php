<?php

declare(strict_types=1);

namespace DartSass\Parsers;

interface TokenAwareParserInterface extends ParserInterface
{
    public function advanceToken(): void;

    public function consume(string $type): Token;

    public function currentToken(): ?Token;

    public function getTokenIndex(): int;

    public function getTokens(): array;

    public function peek(string $type): bool;

    public function setTokenIndex(int $index): void;
}
