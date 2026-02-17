<?php

declare(strict_types=1);

namespace DartSass\Utils;

use function count;
use function explode;
use function ltrim;
use function strlen;

class PositionTracker
{
    private int $currentLine = 1;

    private int $currentColumn = 0;

    private string $sourceCode = '';

    private ?array $lines = null;

    private array $indentations = [];

    public function __construct(string $sourceCode = '')
    {
        if ($sourceCode !== '') {
            $this->setSourceCode($sourceCode);
        }
    }

    public function setSourceCode(string $sourceCode): void
    {
        $this->sourceCode = $sourceCode;
        $this->lines = null;
        $this->indentations = [];
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
        if ($line < 1) {
            return 0;
        }

        $this->initializeLines();

        if ($this->lines === null || $this->lines === []) {
            return 0;
        }

        if ($line > count($this->lines)) {
            return 0;
        }

        if (! isset($this->indentations[$line])) {
            $lineContent = $this->lines[$line - 1];
            $trimmed     = ltrim($lineContent, " \t");

            $this->indentations[$line] = strlen($lineContent) - strlen($trimmed);
        }

        return $this->indentations[$line];
    }

    private function initializeLines(): void
    {
        if ($this->lines !== null) {
            return;
        }

        $this->lines = explode("\n", $this->sourceCode);
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
