<?php

declare(strict_types=1);

namespace DartSass\Parsers\Tokens;

trait TokenStreamHelper
{
    abstract protected function getStream(): TokenStreamInterface;

    public function advanceToken(): void
    {
        $this->getStream()->advance();
    }

    public function consume(string $type): Token
    {
        return $this->getStream()->consume($type);
    }

    public function consumeIf(string $type): ?Token
    {
        return $this->getStream()->consumeIf($type);
    }

    public function currentToken(): ?Token
    {
        return $this->getStream()->current();
    }

    public function expectAny(string ...$types): Token
    {
        return $this->getStream()->expectAny(...$types);
    }

    public function getTokenIndex(): int
    {
        return $this->getStream()->getPosition();
    }

    public function getTokens(): array
    {
        return $this->getStream()->getTokens();
    }

    public function peek(string $type): bool
    {
        return $this->getStream()->matches($type);
    }

    public function isEnd(): bool
    {
        return $this->getStream()->isEnd();
    }

    public function matchesAny(string ...$types): bool
    {
        return $this->getStream()->matchesAny(...$types);
    }

    public function peekValue(int $offset = 1): ?string
    {
        return $this->getStream()->peekValue($offset);
    }

    public function setTokenIndex(int $index): void
    {
        $this->getStream()->setPosition($index);
    }

    public function skipWhitespace(): void
    {
        $this->getStream()->skipWhitespace();
    }
}
