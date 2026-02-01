<?php

declare(strict_types=1);

namespace DartSass\Parsers\Tokens;

interface TokenAwareParserInterface
{
    public function advanceToken(): void;

    public function consume(string $type): Token;

    public function consumeIf(string $type): ?Token;

    public function currentToken(): ?Token;

    public function expectAny(string ...$types): Token;

    public function getTokenIndex(): int;

    public function getTokens(): array;

    public function peek(string $type): bool;

    public function isEnd(): bool;

    public function matchesAny(string ...$types): bool;

    public function peekValue(int $offset = 1): ?string;

    public function previousToken(): Token;

    public function setTokenIndex(int $index): void;

    public function skipWhitespace(): void;
}
