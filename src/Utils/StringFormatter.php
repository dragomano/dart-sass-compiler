<?php

declare(strict_types=1);

namespace DartSass\Utils;

use DartSass\Values\SassList;

use function array_map;
use function count;
use function implode;
use function in_array;
use function is_array;
use function is_bool;
use function is_numeric;
use function is_object;
use function is_string;
use function json_encode;
use function method_exists;
use function preg_match;
use function strlen;
use function substr;

class StringFormatter
{
    private const QUOTE_CHARS = ['"', "'"];

    public static function concat(mixed $left, mixed $right): string
    {
        $leftStr  = self::toString($left);
        $rightStr = self::toString($right);

        return self::concatenateStrings($leftStr, $rightStr);
    }

    public static function concatWithSpace(mixed $left, mixed $right): string
    {
        $leftStr  = self::toString($left);
        $rightStr = self::toString($right);

        return $leftStr . ' ' . $rightStr;
    }

    public static function concatMultiple(array $values, string $separator = ''): string
    {
        $stringValues = array_map(self::toString(...), $values);

        return implode($separator, $stringValues);
    }

    public static function toString(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value === null) {
            return 'null';
        }

        if (is_array($value)) {
            if (isset($value['value']) && isset($value['unit'])) {
                return $value['value'] . $value['unit'];
            }

            if (count($value) === 1 && isset($value['unit'])) {
                return $value['unit'];
            }

            return json_encode($value);
        }

        if ($value instanceof SassList) {
            return implode($value->separator === 'comma' ? ', ' : ' ', $value->value);
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return (string) $value;
        }

        if (is_object($value)) {
            return $value::class;
        }

        return (string) $value;
    }

    public static function isStringCompatible(mixed $value): bool
    {
        return is_string($value)
            || is_numeric($value)
            || is_bool($value)
            || $value === null
            || is_array($value)
            || $value instanceof SassList
            || (is_object($value) && method_exists($value, '__toString'));
    }

    public static function quoteString(string $value): string
    {
        if (self::isQuoted($value)) {
            return $value;
        }

        if (preg_match('/\s|[^a-zA-Z0-9_-]/', $value)) {
            return '"' . $value . '"';
        }

        return $value;
    }

    public static function forceQuoteString(string $value): string
    {
        if (self::isQuoted($value)) {
            return $value;
        }

        return '"' . $value . '"';
    }

    public static function unquoteString(string $value): string
    {
        $length = strlen($value);

        if ($length >= 2 && self::hasMatchingQuotes($value)) {
            return substr($value, 1, -1);
        }

        return $value;
    }

    public static function isQuoted(string $value): bool
    {
        return strlen($value) >= 2 && self::hasMatchingQuotes($value);
    }

    private static function concatenateStrings(string $left, string $right): string
    {
        $leftUnquoted  = self::unquoteString($left);
        $rightUnquoted = self::unquoteString($right);
        $shouldQuote   = self::isQuoted($left) || self::isQuoted($right);

        $result = $leftUnquoted . $rightUnquoted;

        return $shouldQuote ? '"' . $result . '"' : $result;
    }

    private static function hasMatchingQuotes(string $str): bool
    {
        $length = strlen($str);
        $first  = $str[0];
        $last   = $str[$length - 1];

        return in_array($first, self::QUOTE_CHARS, true) && $first === $last;
    }
}
