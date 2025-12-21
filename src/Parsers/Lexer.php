<?php

declare(strict_types=1);

namespace DartSass\Parsers;

use DartSass\Exceptions\SyntaxException;
use DartSass\Patterns\PatternManager;

use function preg_match;
use function sprintf;
use function str_contains;
use function str_starts_with;
use function strlen;
use function strrpos;
use function strtolower;
use function substr_count;

class Lexer implements LexerInterface
{
    protected static array $cachedRegexes = [];

    protected static array $cachedPatterns = [];

    private int $line = 1;

    private int $column = 1;

    private int $position = 0;

    private bool $inBlock = false;

    /**
     * @throws SyntaxException
     */
    public function tokenize(string $input, ?Syntax $syntax = null): TokenStreamInterface
    {
        $syntax ??= Syntax::SCSS;

        $this->resetState();

        [$regex, $patterns] = $this->getTokenizerData($syntax);

        $tokens = [];
        $inputLength = strlen($input);

        while ($this->position < $inputLength) {
            if (preg_match($regex, $input, $matches, 0, $this->position)) {
                foreach ($patterns as $type => $pattern) {
                    $type = strtolower((string) $type);

                    if (! isset($matches[$type]) || $matches[$type] === '') {
                        continue;
                    }

                    $matchValue  = $matches[$type];
                    $matchLength = strlen($matchValue);

                    if ($type === 'brace_open') {
                        $this->inBlock = true;
                    } elseif ($type === 'brace_close') {
                        $this->inBlock = false;
                    }

                    if ($type === 'comment'
                        || ($type === 'whitespace' && $syntax !== Syntax::SASS)
                        || ($type === 'newline' && $syntax !== Syntax::SASS)
                    ) {
                        $this->updatePosition($matchValue, $matchLength);
                        continue 2;
                    }

                    // Force identifiers when appropriate
                    $shouldForceIdentifier = $this->inBlock
                        && ($type === 'identifier' || $type === 'selector')
                        && ! $this->isPotentialSelector($matchValue);

                    $tokenType = $shouldForceIdentifier ? 'identifier' : $type;

                    $tokens[] = new Token($tokenType, $matchValue, $this->line, $this->column);
                    $this->updatePosition($matchValue, $matchLength);

                    continue 2;
                }
            }

            throw new SyntaxException(
                sprintf('Unexpected character: %s', $input[$this->position]),
                $this->line,
                $this->column
            );
        }

        return new TokenStream($tokens);
    }

    private function resetState(): void
    {
        $this->line     = 1;
        $this->column   = 1;
        $this->position = 0;
        $this->inBlock  = false;
    }

    private function updatePosition(string $content, int $length): void
    {
        $lines = substr_count($content, PHP_EOL);
        $this->line += $lines;

        if ($lines > 0) {
            $lastNewLinePos = strrpos($content, PHP_EOL);
            $this->column = $length - $lastNewLinePos;
        } else {
            $this->column += $length;
        }

        $this->position += $length;
    }

    protected function getTokenizerData(Syntax $syntax): array
    {
        $key = $syntax->value;

        if (! isset(self::$cachedRegexes[$key])) {
            self::$cachedPatterns[$key] = PatternManager::getPatterns($key);
            self::$cachedRegexes[$key]  = PatternManager::buildRegexFromPatterns($key);
        }

        return [
            self::$cachedRegexes[$key],
            self::$cachedPatterns[$key]
        ];
    }

    private function isPotentialSelector(string $value): bool
    {
        if ($value === '') {
            return false;
        }

        if (str_starts_with($value, '.') || str_starts_with($value, '#')) {
            return true;
        }

        foreach ([':', '[', '&', '>', '+', '~'] as $char) {
            if (str_contains($value, $char)) {
                return true;
            }
        }

        return false;
    }
}
