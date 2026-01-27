<?php

declare(strict_types=1);

namespace DartSass\Parsers\Tokens;

use ArrayIterator;
use DartSass\Exceptions\SyntaxException;
use IteratorAggregate;
use Traversable;

use function count;
use function implode;
use function in_array;
use function sprintf;

class TokenStream implements TokenStreamInterface, IteratorAggregate
{
    private readonly int $count;

    private int $position = 0;

    private ?Token $cachedToken = null;

    public function __construct(private readonly array $tokens)
    {
        $this->count = count($tokens);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->getTokens());
    }

    public function advance(int $amount = 1): void
    {
        $this->position += $amount;
        $this->cachedToken = null;
    }

    /**
     * @throws SyntaxException
     */
    public function consume(string $expectedType): Token
    {
        $token = $this->current();

        if ($token === null) {
            throw new SyntaxException(
                sprintf('Expected %s, but reached end of input', $expectedType),
                0,
                0
            );
        }

        if ($token->type !== $expectedType) {
            throw new SyntaxException(
                sprintf(
                    'Expected %s, got %s at line %d, column %d',
                    $expectedType,
                    $token->type,
                    $token->line,
                    $token->column
                ),
                $token->line,
                $token->column
            );
        }

        $this->advance();

        return $token;
    }

    public function consumeIf(string $type): ?Token
    {
        if ($this->matches($type)) {
            $token = $this->current();

            $this->advance();

            return $token;
        }

        return null;
    }

    public function count(): int
    {
        return $this->count;
    }

    public function current(): ?Token
    {
        if ($this->cachedToken === null || $this->position !== ($this->cachedToken->position ?? -1)) {
            $this->cachedToken = $this->tokens[$this->position] ?? null;
        }

        return $this->cachedToken;
    }

    /**
     * @throws SyntaxException
     */
    public function expectAny(string ...$types): Token
    {
        $token = $this->current();

        if ($token === null) {
            throw new SyntaxException(
                sprintf('Expected one of [%s], but reached end of input', implode(', ', $types)),
                0,
                0
            );
        }

        if (! in_array($token->type, $types, true)) {
            throw new SyntaxException(
                sprintf(
                    'Expected one of [%s], got %s at line %d, column %d',
                    implode(', ', $types),
                    $token->type,
                    $token->line,
                    $token->column
                ),
                $token->line,
                $token->column
            );
        }

        $this->advance();

        return $token;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getToken(int $index): ?Token
    {
        return $this->tokens[$index] ?? null;
    }

    public function getTokens(): array
    {
        return $this->tokens;
    }

    public function isEnd(): bool
    {
        return $this->position >= $this->count;
    }

    public function matches(string $type): bool
    {
        $token = $this->current();

        return $token !== null && $token->type === $type;
    }

    public function matchesAny(string ...$types): bool
    {
        $token = $this->current();

        return $token !== null && in_array($token->type, $types, true);
    }

    public function peek(int $offset = 1): ?Token
    {
        $index = $this->position + $offset;

        return $this->tokens[$index] ?? null;
    }

    public function peekType(int $offset = 1): ?string
    {
        $token = $this->peek($offset);

        return $token?->type;
    }

    public function peekValue(int $offset = 1): ?string
    {
        $token = $this->peek($offset);

        return $token?->value;
    }

    public function setPosition(int $position): void
    {
        $this->position    = $position;
        $this->cachedToken = null;
    }

    public function skipTokens(string ...$types): void
    {
        while (($token = $this->current()) && in_array($token->type, $types, true)) {
            $this->advance();
        }
    }

    public function skipWhitespace(): void
    {
        while ($this->current()?->type === 'whitespace') {
            $this->advance();
        }
    }
}
