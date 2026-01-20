<?php

declare(strict_types=1);

namespace DartSass\Values;

use DartSass\Exceptions\CompilationException;
use Stringable;

use function abs;
use function array_key_exists;
use function is_array;
use function is_numeric;
use function is_string;
use function preg_match;
use function trim;

use const M_PI;

readonly class SassNumber implements Stringable
{
    public const UNIT_REGEX
        = 'px|em|rem|ch|ex|cap|ic|lh|rlh|'
        . '%|vw|vh|vmin|vmax|lvw|lvh|svw|svh|dvw|dvh|'
        . 'cqw|cqh|cqi|cqb|cqmin|cqmax|'
        . 'pt|pc|in|cm|mm|Q|'
        . 'deg|rad|grad|turn|'
        . 's|ms|'
        . 'Hz|kHz|'
        . 'dpi|dpcm|dppx|x';

    private const LENGTH_CONVERSIONS = [
        'px' => 1.0,
        'in' => 96.0,
        'cm' => 96.0 / 2.54,
        'mm' => 96.0 / 25.4,
        'pt' => 96.0 / 72.0,
        'pc' => 96.0 / 6.0,
    ];

    private const ANGLE_CONVERSIONS = [
        'deg'  => 1.0,
        'rad'  => 180.0 / M_PI,
        'grad' => 0.9,
        'turn' => 360.0,
    ];

    private const TIME_CONVERSIONS = [
        's'  => 1.0,
        'ms' => 0.001,
    ];

    private const UNIT_GROUPS = [
        'length' => self::LENGTH_CONVERSIONS,
        'angle'  => self::ANGLE_CONVERSIONS,
        'time'   => self::TIME_CONVERSIONS,
    ];

    private ?string $unit;

    public function __construct(private float $value, ?string $unit = null)
    {
        $this->unit = ($unit === '' || $unit === null) ? null : $unit;
    }

    public function __toString(): string
    {
        // Return raw value for formatting by external formatters
        $valueStr = (string) $this->value;

        return $this->unit !== null ? $valueStr . $this->unit : $valueStr;
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function hasUnit(): bool
    {
        return $this->unit !== null;
    }

    public function isCompatibleWith(SassNumber $other): bool
    {
        if ($this->unit === null || $other->unit === null) {
            return true;
        }

        if ($this->unit === $other->unit) {
            return true;
        }

        $thisGroup  = $this->getUnitGroup($this->unit);
        $otherGroup = $this->getUnitGroup($other->unit);

        return $thisGroup !== null && $thisGroup === $otherGroup;
    }

    public function convertTo(string $targetUnit): SassNumber
    {
        if ($this->unit === $targetUnit) {
            return new self($this->value, $this->unit);
        }

        if ($this->unit === null) {
            return new self($this->value, $targetUnit);
        }

        $sourceGroup = $this->getUnitGroup($this->unit);
        $targetGroup = $this->getUnitGroup($targetUnit);

        if ($sourceGroup === null || $targetGroup === null || $sourceGroup !== $targetGroup) {
            throw new CompilationException(
                "Cannot convert $this->unit to $targetUnit: incompatible units"
            );
        }

        $conversions    = self::UNIT_GROUPS[$sourceGroup];
        $convertedValue = $this->value * ($conversions[$this->unit] / $conversions[$targetUnit]);

        return new self($convertedValue, $targetUnit);
    }

    public function add(SassNumber $other): SassNumber
    {
        $this->assertCompatibleUnits($other, '+');

        $otherValue = $this->normalizeForOperation($other);
        $resultUnit = $this->resolveResultUnit($other);

        return new self($this->value + $otherValue, $resultUnit);
    }

    public function subtract(SassNumber $other): SassNumber
    {
        $this->assertCompatibleUnits($other, '-');

        $otherValue = $this->normalizeForOperation($other);
        $resultUnit = $this->resolveResultUnit($other);

        return new self($this->value - $otherValue, $resultUnit);
    }

    public function multiply(SassNumber $other): SassNumber
    {
        $resultUnit = $this->unit ?? $other->unit;

        return new self($this->value * $other->value, $resultUnit);
    }

    public function divide(SassNumber $other): SassNumber
    {
        if ($other->value == 0) {
            throw new CompilationException('Division by zero');
        }

        if ($other->unit === null) {
            return new self($this->value / $other->value, $this->unit);
        }

        if ($this->unit === $other->unit) {
            return new self($this->value / $other->value, null);
        }

        if ($this->unit === null) {
            throw new CompilationException(
                "Cannot divide unitless number by $other->unit"
            );
        }

        if ($this->isCompatibleWith($other)) {
            $convertedOther = $other->convertTo($this->unit);

            return new self($this->value / $convertedOther->value, null);
        }

        throw new CompilationException(
            "Cannot divide $this->unit by $other->unit: incompatible units"
        );
    }

    public function equals(SassNumber $other, float $epsilon = 1e-10): bool
    {
        if (! $this->isCompatibleWith($other)) {
            return false;
        }

        $otherValue = $this->normalizeForComparison($other);

        return abs($this->value - $otherValue) < $epsilon;
    }

    public function lessThan(SassNumber $other): bool
    {
        $this->assertCompatibleUnits($other, '<');

        $otherValue = $this->normalizeForComparison($other);

        return $this->value < $otherValue;
    }

    public function greaterThan(SassNumber $other): bool
    {
        $this->assertCompatibleUnits($other, '>');

        $otherValue = $this->normalizeForComparison($other);

        return $this->value > $otherValue;
    }

    public function lessThanOrEqual(SassNumber $other): bool
    {
        return $this->lessThan($other) || $this->equals($other);
    }

    public function greaterThanOrEqual(SassNumber $other): bool
    {
        return $this->greaterThan($other) || $this->equals($other);
    }

    public function negate(): SassNumber
    {
        return new self(-$this->value, $this->unit);
    }

    public function abs(): SassNumber
    {
        return new self(abs($this->value), $this->unit);
    }

    public static function fromString(string $value): SassNumber
    {
        $value = trim($value);

        if (preg_match('/^(-?\d+(?:\.\d+)?)(' . self::UNIT_REGEX . ')?$/', $value, $matches)) {
            $numericValue = (float) $matches[1];
            $unit = $matches[2] ?? null;

            return new self($numericValue, $unit === '' ? null : $unit);
        }

        if (is_numeric($value)) {
            return new self((float) $value, null);
        }

        throw new CompilationException("Cannot parse '$value' as a number");
    }

    public static function tryFrom(mixed $value): ?SassNumber
    {
        if ($value instanceof self) {
            return $value;
        }

        if (is_numeric($value)) {
            return new self((float) $value, null);
        }

        if (is_string($value)) {
            try {
                return self::fromString($value);
            } catch (CompilationException) {
                return null;
            }
        }

        if (is_array($value) && array_key_exists('value', $value)) {
            return new self(
                (float) $value['value'],
                isset($value['unit']) && $value['unit'] !== '' ? $value['unit'] : null
            );
        }

        return null;
    }

    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'unit'  => $this->unit ?? '',
        ];
    }

    private function getUnitGroup(string $unit): ?string
    {
        foreach (self::UNIT_GROUPS as $groupName => $conversions) {
            if (array_key_exists($unit, $conversions)) {
                return $groupName;
            }
        }

        return null;
    }

    private function assertCompatibleUnits(SassNumber $other, string $operator): void
    {
        if (! $this->isCompatibleWith($other)) {
            throw new CompilationException(
                "Incompatible units for '$operator': $this->unit and $other->unit"
            );
        }
    }

    private function normalizeForOperation(SassNumber $other): float
    {
        if ($this->unit === null || $this->unit === $other->unit || $other->unit === null) {
            return $other->value;
        }

        $converted = $other->convertTo($this->unit);

        return $converted->value;
    }

    private function normalizeForComparison(SassNumber $other): float
    {
        if (($this->unit === null && $other->unit === null) || $this->unit === $other->unit) {
            return $other->value;
        }

        if ($this->unit === null || $other->unit === null) {
            return $other->value;
        }

        $converted = $other->convertTo($this->unit);

        return $converted->value;
    }

    private function resolveResultUnit(SassNumber $other): ?string
    {
        return $this->unit ?? $other->unit;
    }
}
