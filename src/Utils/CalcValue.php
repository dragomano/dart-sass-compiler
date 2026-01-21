<?php

declare(strict_types=1);

namespace DartSass\Utils;

use DartSass\Values\SassNumber;
use Exception;
use Stringable;

use function is_array;
use function reset;

readonly class CalcValue implements LazyEvaluatable, Stringable
{
    private ?SassNumber $normalizedLeft;

    private ?SassNumber $normalizedRight;

    public function __construct(private mixed $left, private string $operator, private mixed $right)
    {
        $this->normalizedLeft  = SassNumber::tryFrom($this->left);
        $this->normalizedRight = SassNumber::tryFrom($this->right);
    }

    public function __toString(): string
    {
        return "calc({$this->format($this->left)} $this->operator {$this->format($this->right)})";
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    public function getArgs(): array
    {
        return [$this->left, $this->right];
    }

    public function evaluate(): string|array
    {
        if ($this->normalizedLeft === null || $this->normalizedRight === null) {
            return (string) $this;
        }

        try {
            $result = match ($this->operator) {
                '+'     => $this->normalizedLeft->add($this->normalizedRight),
                '-'     => $this->normalizedLeft->subtract($this->normalizedRight),
                '*'     => $this->normalizedLeft->multiply($this->normalizedRight),
                '/'     => $this->normalizedLeft->divide($this->normalizedRight),
                default => null,
            };

            return $result?->toArray() ?? (string) $this;
        } catch (Exception) {
            return (string) $this;
        }
    }

    private function format(mixed $value): string
    {
        $number = SassNumber::tryFrom($value);

        if ($number !== null) {
            return (string) $number;
        }

        return is_array($value) ? (string) reset($value) : (string) $value;
    }
}
