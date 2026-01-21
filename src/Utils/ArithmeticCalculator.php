<?php

declare(strict_types=1);

namespace DartSass\Utils;

use DartSass\Exceptions\CompilationException;
use DartSass\Values\SassNumber;

use function fmod;
use function is_array;
use function is_bool;
use function is_object;
use function is_string;

final class ArithmeticCalculator
{
    public static function add(SassNumber|float|int $left, SassNumber|float|int $right): SassNumber
    {
        $leftNumber  = self::toSassNumber($left);
        $rightNumber = self::toSassNumber($right);

        return $leftNumber->add($rightNumber);
    }

    public static function subtract(SassNumber|float|int $left, SassNumber|float|int $right): SassNumber
    {
        $leftNumber  = self::toSassNumber($left);
        $rightNumber = self::toSassNumber($right);

        return $leftNumber->subtract($rightNumber);
    }

    public static function multiply(SassNumber|float|int $left, SassNumber|float|int $right): SassNumber
    {
        $leftNumber  = self::toSassNumber($left);
        $rightNumber = self::toSassNumber($right);

        return $leftNumber->multiply($rightNumber);
    }

    public static function divide(SassNumber|float|int $left, SassNumber|float|int $right): SassNumber
    {
        $leftNumber  = self::toSassNumber($left);
        $rightNumber = self::toSassNumber($right);

        if ($rightNumber->getValue() == 0) {
            throw new CompilationException('Division by zero');
        }

        $leftValue  = $leftNumber->getValue();
        $rightValue = $rightNumber->getValue();
        $leftUnit   = $leftNumber->getUnit();
        $rightUnit  = $rightNumber->getUnit();

        // Handle division cases
        if ($rightUnit === null) {
            // Number divided by unitless number
            return new SassNumber($leftValue / $rightValue, $leftUnit);
        }

        if ($leftUnit === $rightUnit) {
            // Same units cancel out
            return new SassNumber($leftValue / $rightValue, null);
        }

        if ($leftUnit === null) {
            // Unitless number divided by number with unit
            return new SassNumber($leftValue / $rightValue, $rightUnit);
        }

        // Check unit compatibility
        if ($leftNumber->isCompatibleWith($rightNumber)) {
            $convertedRight = $rightNumber->convertTo($leftUnit);

            return new SassNumber($leftValue / $convertedRight->getValue(), null);
        }

        throw new CompilationException(
            "Cannot divide $leftUnit by $rightUnit: incompatible units"
        );
    }

    public static function modulo(SassNumber|float|int $left, SassNumber|float|int $right): SassNumber
    {
        $leftNumber  = self::toSassNumber($left);
        $rightNumber = self::toSassNumber($right);

        if ($rightNumber->getValue() == 0) {
            throw new CompilationException('Modulo by zero');
        }

        $leftUnit  = $leftNumber->getUnit();
        $rightUnit = $rightNumber->getUnit();

        if ($leftUnit !== null && $rightUnit !== null && $leftUnit !== $rightUnit) {
            if (! $leftNumber->isCompatibleWith($rightNumber)) {
                throw new CompilationException(
                    "Incompatible units for '%': $leftUnit and $rightUnit"
                );
            }

            $rightNumber = $rightNumber->convertTo($leftUnit);
        }

        $resultUnit = $leftUnit ?? $rightUnit;
        $result     = fmod($leftNumber->getValue(), $rightNumber->getValue());

        return new SassNumber($result, $resultUnit);
    }

    public static function negate(SassNumber|float|int $value): SassNumber
    {
        $number = self::toSassNumber($value);

        return $number->negate();
    }

    public static function calculate(string $operator, mixed $left, mixed $right): ?SassNumber
    {
        $leftNumber  = self::tryToSassNumber($left);
        $rightNumber = self::tryToSassNumber($right);

        if ($leftNumber === null || $rightNumber === null) {
            return null;
        }

        return match ($operator) {
            '+'     => self::add($leftNumber, $rightNumber),
            '-'     => self::subtract($leftNumber, $rightNumber),
            '*'     => self::multiply($leftNumber, $rightNumber),
            '/'     => self::divide($leftNumber, $rightNumber),
            '%'     => self::modulo($leftNumber, $rightNumber),
            default => throw new CompilationException("Unknown arithmetic operator: $operator"),
        };
    }

    public static function normalizeUnits(SassNumber $left, SassNumber $right): array
    {
        $leftUnit  = $left->getUnit();
        $rightUnit = $right->getUnit();

        if ($leftUnit === null && $rightUnit === null) {
            return [$left->getValue(), $right->getValue(), null];
        }

        if ($leftUnit === null) {
            return [$left->getValue(), $right->getValue(), $rightUnit];
        }

        if ($rightUnit === null) {
            return [$left->getValue(), $right->getValue(), $leftUnit];
        }

        if ($leftUnit === $rightUnit) {
            return [$left->getValue(), $right->getValue(), $leftUnit];
        }

        if ($left->isCompatibleWith($right)) {
            $convertedRight = $right->convertTo($leftUnit);

            return [$left->getValue(), $convertedRight->getValue(), $leftUnit];
        }

        throw new CompilationException(
            "Incompatible units: $leftUnit and $rightUnit"
        );
    }

    public static function resolveResultUnit(string $operator, ?string $leftUnit, ?string $rightUnit): ?string
    {
        return match ($operator) {
            '/'      => $leftUnit === $rightUnit ? null : $leftUnit,
            default  => $leftUnit ?? $rightUnit,
        };
    }

    public static function toSassNumber(mixed $value): SassNumber
    {
        $sassNumber = SassNumber::tryFrom($value);

        if ($sassNumber === null) {
            throw new CompilationException(
                'Cannot convert value to SassNumber: ' . self::formatValueForError($value)
            );
        }

        return $sassNumber;
    }

    public static function tryToSassNumber(mixed $value): ?SassNumber
    {
        try {
            return self::toSassNumber($value);
        } catch (CompilationException) {
            return null;
        }
    }

    public static function areUnitsCompatible(string $unit1, string $unit2): bool
    {
        if ($unit1 === $unit2) {
            return true;
        }

        $num1 = new SassNumber(1, $unit1);
        $num2 = new SassNumber(1, $unit2);

        return $num1->isCompatibleWith($num2);
    }

    public static function convertUnit(float $value, string $fromUnit, string $toUnit): float
    {
        if ($fromUnit === $toUnit) {
            return $value;
        }

        $number    = new SassNumber($value, $fromUnit);
        $converted = $number->convertTo($toUnit);

        return $converted->getValue();
    }

    private static function formatValueForError(mixed $value): string
    {
        if ($value === null) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_string($value)) {
            return StringFormatter::forceQuoteString($value);
        }

        if (is_array($value)) {
            return 'array';
        }

        if (is_object($value)) {
            return $value::class;
        }

        return (string) $value;
    }
}
