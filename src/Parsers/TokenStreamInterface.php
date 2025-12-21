<?php

namespace DartSass\Parsers;

interface TokenStreamInterface
{
    public function advance(int $amount = 1): void;

    public function consume(string $expectedType): Token;

    public function consumeIf(string $type): ?Token;

    public function count(): int;

    public function current(): ?Token;

    public function expectAny(string ...$types): Token;

    public function getPosition(): int;

    public function getToken(int $index): ?Token;

    public function getTokens(): array;

    public function isEnd(): bool;

    public function matches(string $type): bool;

    public function matchesAny(string ...$types): bool;

    public function peek(int $offset = 1): ?Token;

    public function peekType(int $offset = 1): ?string;

    public function peekValue(int $offset = 1): ?string;

    public function setPosition(int $position): void;

    public function skipTokens(string ...$types): void;

    public function skipWhitespace(): void;
}
