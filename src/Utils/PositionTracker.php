<?php

declare(strict_types=1);

namespace DartSass\Utils;

use function count;
use function explode;
use function strlen;
use function ltrim;

class PositionTracker
{
    private int $currentLine = 1;

    private int $currentColumn = 0;

    public function __construct(private string $sourceCode = '')
    {
    }

    public function setSourceCode(string $sourceCode): void
    {
        $this->sourceCode = $sourceCode;
        $this->reset();
    }

    public function reset(): void
    {
        $this->currentLine   = 1;
        $this->currentColumn = 0;
    }

    public function updatePosition(string $text): void
    {
        $lines     = explode("\n", $text);
        $lineCount = count($lines);

        if ($lineCount > 1) {
            $this->currentLine += $lineCount - 1;
            $this->currentColumn = strlen($lines[$lineCount - 1]);
        } else {
            $this->currentColumn += strlen($text);
        }
    }

    public function getCurrentPosition(): array
    {
        return [
            'line'   => $this->currentLine,
            'column' => $this->currentColumn,
        ];
    }

    public function getLine(): int
    {
        return $this->currentLine;
    }

    public function getColumn(): int
    {
        return $this->currentColumn;
    }

    public function calculateIndentation(int $line): int
    {
        if ($line < 1 || $this->sourceCode === '') {
            return 0;
        }

        $lines = explode("\n", $this->sourceCode);
        if ($line > count($lines)) {
            return 0;
        }

        $lineContent = $lines[$line - 1]; // line is 1-based
        $trimmed = ltrim($lineContent, " \t");

        return strlen($lineContent) - strlen($trimmed);
    }

    public function getState(): array
    {
        return [
            'line'   => $this->currentLine,
            'column' => $this->currentColumn,
        ];
    }

    public function setState(array $state): void
    {
        $this->currentLine   = $state['line'];
        $this->currentColumn = $state['column'];
    }
}
