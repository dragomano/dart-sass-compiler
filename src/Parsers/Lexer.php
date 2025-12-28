<?php

declare(strict_types=1);

namespace DartSass\Parsers;

use DartSass\Exceptions\InvalidColorException;

use function ctype_xdigit;
use function in_array;
use function preg_match;
use function str_contains;
use function str_starts_with;
use function strlen;
use function strrpos;
use function strtolower;
use function substr;
use function substr_count;

class Lexer implements LexerInterface
{
    protected static array $cachedPatterns;

    protected static string $cachedRegexes;

    private int $line = 1;

    private int $column = 1;

    private int $position = 0;

    private bool $inBlock = false;

    private bool $expectingPropertyValue = false;

    public function tokenize(string $input): TokenStreamInterface
    {
        $this->resetState();

        [$regex, $patterns] = $this->getTokenizerData();

        $tokens = [];
        $inputLength = strlen($input);

        while ($this->position < $inputLength) {
            if (preg_match($regex, $input, $matches, 0, $this->position)) {
                foreach ($patterns as $type => $pattern) {
                    $type = strtolower((string) $type);

                    if (! isset($matches[$type]) || $matches[$type] === '') {
                        continue;
                    }

                    $matchValue = $matches[$type];
                    $matchLength = strlen($matchValue);

                    $this->updateBlockState($type);
                    $this->updatePropertyValueState($type);

                    if (in_array($type, ['comment', 'whitespace', 'newline'], true)) {
                        $this->updatePosition($matchValue, $matchLength);
                        continue 2;
                    }

                    if ($this->expectingPropertyValue && $type === 'operator' && $matchValue === '#') {
                        $this->validatePotentialHexColor($input, $this->position);
                    }

                    $tokenType = $this->resolveTokenType($type, $matchValue);
                    $tokens[] = new Token($tokenType, $matchValue, $this->line, $this->column);
                    $this->updatePosition($matchValue, $matchLength);

                    continue 2;
                }
            }
        }

        return new TokenStream($tokens);
    }

    private function resetState(): void
    {
        $this->line = 1;
        $this->column = 1;
        $this->position = 0;
        $this->inBlock = false;
        $this->expectingPropertyValue = false;
    }

    private function updateBlockState(string $type): void
    {
        if ($type === 'brace_open') {
            $this->inBlock = true;
        } elseif ($type === 'brace_close') {
            $this->inBlock = false;
            $this->expectingPropertyValue = false;
        }
    }

    private function updatePropertyValueState(string $type): void
    {
        if ($type === 'colon') {
            $this->expectingPropertyValue = true;
        } elseif (in_array($type, ['semicolon', 'brace_close'], true)) {
            $this->expectingPropertyValue = false;
        }
    }

    private function resolveTokenType(string $type, string $value): string
    {
        $shouldForceIdentifier = $this->inBlock
            && ($type === 'identifier' || $type === 'selector')
            && ! $this->isPotentialSelector($value);

        return $shouldForceIdentifier ? 'identifier' : $type;
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

    protected function getTokenizerData(): array
    {
        if (! isset(self::$cachedRegexes)) {
            self::$cachedPatterns = TokenPattern::getPatterns();
            self::$cachedRegexes = TokenPattern::buildRegexFromPatterns();
        }

        return [self::$cachedRegexes, self::$cachedPatterns];
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

    private function validatePotentialHexColor(string $input, int $position): void
    {
        $remaining = substr($input, $position + 1);

        if (! preg_match('/^([a-zA-Z0-9]{3,8})/', $remaining, $matches)) {
            return;
        }

        $hexPart  = $matches[1];
        $length   = strlen($hexPart);
        $nextChar = substr($remaining, $length, 1);

        $validLength = in_array($length, [3, 4, 6, 8], true);
        $validTerminator = $nextChar === '' || in_array($nextChar, [' ', ';', '}', ')'], true);

        if ($validLength && ! ctype_xdigit($hexPart) && $validTerminator) {
            throw new InvalidColorException('#' . $hexPart, $this->line, $this->column);
        }
    }
}
