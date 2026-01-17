<?php

declare(strict_types=1);

namespace DartSass\Utils;

use Stringable;

use function is_array;
use function is_numeric;

readonly class CalcValue implements LazyEvaluatable, Stringable
{
    private ?array $normalizedLeft;

    private ?array $normalizedRight;

    public function __construct(
        public mixed $left,
        public string $operator,
        public mixed $right
    ) {
        $this->normalizedLeft  = $this->normalizeValue($this->left);
        $this->normalizedRight = $this->normalizeValue($this->right);
    }

    public function __toString(): string
    {
        $formatter = new ValueFormatter();

        return "calc({$formatter->format($this->left)} $this->operator {$formatter->format($this->right)})";
    }

    public function evaluate(): string|array
    {
        if ($this->normalizedLeft === null || $this->normalizedRight === null) {
            return (string) $this;
        }

        if ($this->normalizedLeft['unit'] !== $this->normalizedRight['unit']) {
            return (string) $this;
        }

        return $this->compute();
    }

    private function compute(): string|array
    {
        $result = match ($this->operator) {
            '+' => $this->normalizedLeft['value'] + $this->normalizedRight['value'],
            '-' => $this->normalizedLeft['value'] - $this->normalizedRight['value'],
            '*' => $this->normalizedLeft['value'] * $this->normalizedRight['value'],
            '/' => $this->normalizedRight['value'] != 0
                ? $this->normalizedLeft['value'] / $this->normalizedRight['value']
                : null,
            default => null,
        };

        return $result !== null
            ? ['value' => $result, 'unit' => $this->normalizedLeft['unit']]
            : (string) $this;
    }

    private function normalizeValue(mixed $value): ?array
    {
        if (is_array($value) && isset($value['value'])) {
            return $value;
        }

        return is_numeric($value)
            ? ['value' => (float) $value, 'unit' => '']
            : null;
    }
}
