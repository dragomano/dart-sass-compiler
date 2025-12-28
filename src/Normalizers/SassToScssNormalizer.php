<?php

declare(strict_types=1);

namespace DartSass\Normalizers;

use DartSass\Parsers\Syntax;

use function array_pop;
use function end;
use function implode;
use function intdiv;
use function ltrim;
use function preg_match;
use function preg_split;
use function rtrim;
use function sort;
use function str_contains;
use function str_ends_with;
use function str_repeat;
use function str_starts_with;
use function strlen;
use function substr;
use function trim;

readonly class SassToScssNormalizer implements SourceNormalizer
{
    public function supports(Syntax $syntax): bool
    {
        return $syntax === Syntax::SASS;
    }

    public function normalize(string $source): string
    {
        $eol = $this->detectLineEnding($source);
        $lines = preg_split('/\R/', $source) ?: [];
        $indentSize = $this->detectIndentSize($lines);
        $result = [];
        $stack = [];
        $pendingEmptyLines = [];
        $inMultilineComment = false;

        foreach ($lines as $rawLine) {
            $line = rtrim($rawLine, "\r\n");
            $trimmed = ltrim($line);

            if ($trimmed === '') {
                $pendingEmptyLines[] = '';
                continue;
            }

            if ($inMultilineComment) {
                $result[] = $line;

                if (str_contains($trimmed, '*/')) {
                    $inMultilineComment = false;
                }

                continue;
            }

            if (str_starts_with($trimmed, '/*')) {
                foreach ($pendingEmptyLines as $empty) {
                    $result[] = $empty;
                }

                $pendingEmptyLines = [];
                $result[] = $line;

                if (! str_contains($trimmed, '*/')) {
                    $inMultilineComment = true;
                }

                continue;
            }

            if (str_starts_with($trimmed, '//')) {
                foreach ($pendingEmptyLines as $empty) {
                    $result[] = $empty;
                }

                $pendingEmptyLines = [];
                $result[] = $line;

                continue;
            }

            $leadingSpaces = strlen($line) - strlen($trimmed);
            $level = intdiv($leadingSpaces, $indentSize);

            while (! empty($stack) && end($stack)['level'] >= $level) {
                $block = array_pop($stack);
                $result[] = str_repeat(' ', $block['level'] * $indentSize) . '}';
            }

            if ($level === 0) {
                foreach ($pendingEmptyLines as $empty) {
                    $result[] = $empty;
                }
            }

            $pendingEmptyLines = [];

            if (preg_match('/,\s*$/', $trimmed)) {
                $lineToAdd = rtrim($trimmed);
                $result[] = str_repeat(' ', $level * $indentSize) . $lineToAdd;
                continue;
            }

            if (str_starts_with($trimmed, '=')) {
                $mixinDeclaration = substr($trimmed, 1);
                $result[] = str_repeat(' ', $level * $indentSize) . '@mixin ' . $mixinDeclaration . ' {';
                $stack[] = ['level' => $level];
            } elseif (str_starts_with($trimmed, '+')) {
                $includeCall = substr($trimmed, 1);
                $result[] = str_repeat(' ', $level * $indentSize) . '@include ' . $includeCall . ';';
            } elseif ($this->isSingleLineDirective($trimmed)) {
                $result[] = str_repeat(' ', $level * $indentSize) . $trimmed . ';';
            } elseif (preg_match('/^@media\b/', $trimmed)) {
                $result[] = str_repeat(' ', $level * $indentSize) . $trimmed . ' {';
                $stack[] = ['level' => $level];
            } elseif ($this->isBlockHeader($trimmed)) {
                $result[] = str_repeat(' ', $level * $indentSize) . $trimmed . ' {';
                $stack[] = ['level' => $level];
            } else {
                $result[] = str_repeat(' ', $level * $indentSize) . rtrim($trimmed, ';') . ';';
            }
        }

        while (! empty($stack)) {
            $block = array_pop($stack);
            $result[] = str_repeat(' ', $block['level'] * $indentSize) . '}';
        }

        return implode($eol, $result);
    }

    private function detectLineEnding(string $source): string
    {
        $patterns = [
            "\r\n" => "\r\n",
            "\r"   => "\r",
        ];

        foreach ($patterns as $search => $replace) {
            if (str_contains($source, $search)) {
                return $replace;
            }
        }

        return "\n";
    }

    private function detectIndentSize(array $lines): int
    {
        $sizes = [2];

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }

            if (preg_match('/^( +)/', $line, $m)) {
                $sizes[] = strlen($m[1]);
            }
        }

        sort($sizes);

        return $sizes[0];
    }

    private function isSingleLineDirective(string $line): bool
    {
        return preg_match('/^@(import|use|forward|charset|extend|return)\b/', $line) === 1;
    }

    private function isBlockHeader(string $line): bool
    {
        if (preg_match('/^@(if|else|for|each|while|media|supports|keyframes|function|mixin|include)\b/', $line)) {
            return true;
        }

        if (preg_match('/^[.#&%\[]/', $line)) {
            return true;
        }

        if (str_ends_with($line, ':')) {
            return true;
        }

        return ! str_contains($line, ':') || preg_match('/:(hover|active|focus|has|first-child)\b/', $line);
    }
}
