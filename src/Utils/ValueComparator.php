<?php

declare(strict_types=1);

namespace DartSass\Utils;

use DartSass\Exceptions\CompilationException;
use DartSass\Values\SassList;
use DartSass\Values\SassMap;
use DartSass\Values\SassNumber;

use function array_keys;
use function count;
use function in_array;
use function is_array;
use function is_numeric;
use function is_string;
use function strlen;
use function substr;

final class ValueComparator
{
    private const COMPARISON_OPERATORS = ['==', '!=', '<', '>', '<=', '>='];

    private const LOGICAL_OPERATORS = ['and', 'or', 'not'];

    private const QUOTE_CHARS = ['"', "'"];

    public static function equals(mixed $left, mixed $right): bool
    {
        return self::areEqual($left, $right);
    }

    public static function notEquals(mixed $left, mixed $right): bool
    {
        return ! self::areEqual($left, $right);
    }

    public static function lessThan(mixed $left, mixed $right): bool
    {
        return self::compareNumeric($left, $right) < 0;
    }

    public static function greaterThan(mixed $left, mixed $right): bool
    {
        return self::compareNumeric($left, $right) > 0;
    }

    public static function lessThanOrEqual(mixed $left, mixed $right): bool
    {
        return self::compareNumeric($left, $right) <= 0;
    }

    public static function greaterThanOrEqual(mixed $left, mixed $right): bool
    {
        return self::compareNumeric($left, $right) >= 0;
    }

    public static function and(mixed $left, mixed $right): bool
    {
        return self::isTruthy($left) && self::isTruthy($right);
    }

    public static function or(mixed $left, mixed $right): bool
    {
        return self::isTruthy($left) || self::isTruthy($right);
    }

    public static function not(mixed $value): bool
    {
        return ! self::isTruthy($value);
    }

    public static function compare(string $operator, mixed $left, mixed $right): bool
    {
        return match ($operator) {
            '=='    => self::equals($left, $right),
            '!='    => self::notEquals($left, $right),
            '<'     => self::lessThan($left, $right),
            '>'     => self::greaterThan($left, $right),
            '<='    => self::lessThanOrEqual($left, $right),
            '>='    => self::greaterThanOrEqual($left, $right),
            'and'   => self::and($left, $right),
            'or'    => self::or($left, $right),
            'not'   => self::not($left),
            default => throw new CompilationException("Unknown comparison operator: $operator"),
        };
    }

    public static function isTruthy(mixed $value): bool
    {
        if ($value === null || $value === false) {
            return false;
        }

        if (is_string($value) && strtolower($value) === 'null') {
            return false;
        }

        return true;
    }

    public static function isComparisonOperator(string $operator): bool
    {
        return in_array($operator, self::COMPARISON_OPERATORS, true);
    }

    public static function isLogicalOperator(string $operator): bool
    {
        return in_array($operator, self::LOGICAL_OPERATORS, true);
    }

    public static function getComparisonOperators(): array
    {
        return self::COMPARISON_OPERATORS;
    }

    public static function getLogicalOperators(): array
    {
        return self::LOGICAL_OPERATORS;
    }

    private static function areEqual(mixed $left, mixed $right): bool
    {
        if ($left === $right) {
            return true;
        }

        if ($left === null || $right === null) {
            return false;
        }

        $leftNumber  = SassNumber::tryFrom($left);
        $rightNumber = SassNumber::tryFrom($right);

        if ($leftNumber !== null && $rightNumber !== null) {
            return $leftNumber->equals($rightNumber);
        }

        if ($left instanceof SassList && $right instanceof SassList) {
            return self::listsAreEqual($left, $right);
        }

        if ($left instanceof SassMap && $right instanceof SassMap) {
            return self::mapsAreEqual($left, $right);
        }

        if (is_array($left) && is_array($right)) {
            return self::arraysAreEqual($left, $right);
        }

        if (is_string($left) && is_string($right)) {
            return self::stringsAreEqual($left, $right);
        }

        return $left == $right;
    }

    private static function compareNumeric(mixed $left, mixed $right): int
    {
        $leftNumber  = SassNumber::tryFrom($left);
        $rightNumber = SassNumber::tryFrom($right);

        if ($leftNumber !== null && $rightNumber !== null) {
            if ($leftNumber->lessThan($rightNumber)) {
                return -1;
            }

            if ($leftNumber->greaterThan($rightNumber)) {
                return 1;
            }

            return 0;
        }

        $leftValue  = self::extractNumericValue($left);
        $rightValue = self::extractNumericValue($right);

        if ($leftValue === null || $rightValue === null) {
            $leftValue ??= $left === null ? 0 : null;
            $rightValue ??= $right === null ? 0 : null;

            if ($leftValue === null || $rightValue === null) {
                throw new CompilationException('Cannot compare non-numeric values');
            }
        }

        return $leftValue <=> $rightValue;
    }

    private static function extractNumericValue(mixed $value): ?float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        return null;
    }

    private static function stringsAreEqual(string $left, string $right): bool
    {
        return self::removeQuotes($left) === self::removeQuotes($right);
    }

    private static function removeQuotes(string $str): string
    {
        $length = strlen($str);

        if ($length >= 2) {
            $first = $str[0];
            $last  = $str[$length - 1];

            if (in_array($first, self::QUOTE_CHARS, true) && $first === $last) {
                return substr($str, 1, -1);
            }
        }

        return $str;
    }

    private static function listsAreEqual(SassList $left, SassList $right): bool
    {
        return self::collectionsAreEqual($left->value, $right->value);
    }

    private static function mapsAreEqual(SassMap $left, SassMap $right): bool
    {
        return self::collectionsAreEqual($left->value, $right->value);
    }

    private static function collectionsAreEqual(array $left, array $right): bool
    {
        if (count($left) !== count($right)) {
            return false;
        }

        foreach ($left as $key => $leftValue) {
            if (! isset($right[$key])) {
                return false;
            }

            if (! self::areEqual($leftValue, $right[$key])) {
                return false;
            }
        }

        return true;
    }

    private static function arraysAreEqual(array $left, array $right): bool
    {
        if (count($left) !== count($right)) {
            return false;
        }

        $leftKeys  = array_keys($left);
        $rightKeys = array_keys($right);

        if ($leftKeys !== $rightKeys) {
            return false;
        }

        foreach ($left as $key => $leftValue) {
            if (! self::areEqual($leftValue, $right[$key])) {
                return false;
            }
        }

        return true;
    }
}
