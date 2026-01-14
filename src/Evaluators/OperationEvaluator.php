<?php

declare(strict_types=1);

namespace DartSass\Evaluators;

use DartSass\Exceptions\CompilationException;
use DartSass\Utils\LazyValue;
use DartSass\Utils\ValueFormatter;

use function in_array;
use function is_array;
use function is_numeric;
use function is_string;
use function str_contains;
use function str_starts_with;
use function strlen;
use function substr;

readonly class OperationEvaluator
{
    private const COMPARISON_OPERATORS = ['==', '!=', '<', '>', '<=', '>=', 'and', 'or'];

    private const MULTIPLICATION_DIVISION_OPERATORS = ['*', '/'];

    private const QUOTE_CHARS = ['"', "'"];

    public function __construct(private ValueFormatter $valueFormatter) {}

    public function evaluate(mixed $left, string $operator, mixed $right): mixed
    {
        $left  = $this->resolveValue($left);
        $right = $this->resolveValue($right);

        if (in_array($operator, self::COMPARISON_OPERATORS, true)) {
            return $this->evaluateComparison($left, $operator, $right);
        }

        if ($operator === '+') {
            return $this->handleAddition($left, $right);
        }

        if ($operator === '/' && is_string($left) && is_string($right)) {
            return "$left / $right";
        }

        return $this->evaluateArithmetic($left, $operator, $right);
    }

    private function resolveValue(mixed $value): mixed
    {
        return $value instanceof LazyValue ? $value->getValue() : $value;
    }

    private function evaluateComparison(mixed $left, string $operator, mixed $right): bool
    {
        return match ($operator) {
            '=='    => $this->valuesAreEqual($left, $right),
            '!='    => ! $this->valuesAreEqual($left, $right),
            '<'     => $this->compareNumericValues($left, $right) < 0,
            '>'     => $this->compareNumericValues($left, $right) > 0,
            '<='    => $this->compareNumericValues($left, $right) <= 0,
            '>='    => $this->compareNumericValues($left, $right) >= 0,
            'and'   => $this->isTruthy($left) && $this->isTruthy($right),
            'or'    => $this->isTruthy($left) || $this->isTruthy($right),
            default => throw new CompilationException("Unknown comparison operator: $operator"),
        };
    }

    private function handleAddition(mixed $left, mixed $right): mixed
    {
        if (is_string($left) && is_string($right)) {
            return $this->concatenateStrings($left, $right);
        }

        if ((is_string($left) && is_numeric($right)) || (is_numeric($left) && is_string($right))) {
            return $left . $right;
        }

        if ($this->isStructuredValue($left) && is_string($right)) {
            return $this->formatStructuredValue($left) . $right;
        }

        if (is_string($left) && $this->isStructuredValue($right)) {
            return $left . $this->formatStructuredValue($right);
        }

        return $this->evaluateArithmetic($left, '+', $right);
    }

    private function concatenateStrings(string $left, string $right): string|float
    {
        if (is_numeric($left) && is_numeric($right)) {
            return (float) $left + (float) $right;
        }

        $leftUnquoted  = $this->removeQuotes($left);
        $rightUnquoted = $this->removeQuotes($right);
        $shouldQuote   = $this->isQuotedString($left) || $this->isQuotedString($right);

        return $shouldQuote ? "\"$leftUnquoted$rightUnquoted\"" : $leftUnquoted . $rightUnquoted;
    }

    private function evaluateArithmetic(mixed $left, string $operator, mixed $right): mixed
    {
        [$leftValue, $leftUnit, $rightValue, $rightUnit] = $this->extractNumericValues($left, $right);

        if ($leftValue !== null && $rightValue !== null) {
            if ($leftUnit === $rightUnit) {
                return $this->performNumericOperation($leftValue, $rightValue, $operator, $leftUnit);
            }

            if (in_array($operator, self::MULTIPLICATION_DIVISION_OPERATORS, true)) {
                $unit = $leftUnit !== '' ? $leftUnit : $rightUnit;
                if ($unit !== '') {
                    return $this->performNumericOperation($leftValue, $rightValue, $operator, $unit);
                }
            }
        }

        if (in_array($operator, self::MULTIPLICATION_DIVISION_OPERATORS, true)
            && ($leftValue === null || $rightValue === null)) {
            $this->throwIfUndefinedOperation($left, $operator, $right);
        }

        return $this->buildCalcExpression($left, $operator, $right);
    }

    private function throwIfUndefinedOperation(mixed $left, string $operator, mixed $right): void
    {
        $isLeftSimple  = is_string($left) && ! str_contains($left, '(');
        $isRightSimple = is_string($right) && ! str_contains($right, '(');

        if ($isLeftSimple || $isRightSimple) {
            $leftStr  = $this->valueFormatter->format($left);
            $rightStr = $this->valueFormatter->format($right);

            throw new CompilationException("Undefined operation \"$leftStr $operator $rightStr\".");
        }
    }

    private function valuesAreEqual(mixed $left, mixed $right): bool
    {
        if ($left === $right) {
            return true;
        }

        if ($left === null || $right === null) {
            return false;
        }

        if ($this->isStructuredValue($left) && $this->isStructuredValue($right)) {
            return $this->structuredValuesEqual($left, $right);
        }

        if (is_numeric($left) && is_numeric($right)) {
            return (float) $left === (float) $right;
        }

        if ($this->isStructuredValue($left) && is_numeric($right)) {
            return $this->structuredEqualsNumeric($left, $right);
        }

        if (is_numeric($left) && $this->isStructuredValue($right)) {
            return $this->structuredEqualsNumeric($right, $left);
        }

        return $left == $right;
    }

    private function structuredValuesEqual(array $left, array $right): bool
    {
        $leftUnit  = $left['unit'] ?? '';
        $rightUnit = $right['unit'] ?? '';

        return $leftUnit === $rightUnit && (float) $left['value'] === (float) $right['value'];
    }

    private function structuredEqualsNumeric(array $structured, mixed $numeric): bool
    {
        $unit = $structured['unit'] ?? '';

        return $unit === '' && (float) $structured['value'] === (float) $numeric;
    }

    private function compareNumericValues(mixed $left, mixed $right): int
    {
        [$leftValue, , $rightValue,] = $this->extractNumericValues($left, $right);

        if ($leftValue === null || $rightValue === null) {
            return 0;
        }

        return $leftValue <=> $rightValue;
    }

    private function isTruthy(mixed $value): bool
    {
        return ! in_array($value, [false, null, 0, ''], true);
    }

    private function extractNumericValues(mixed $left, mixed $right): array
    {
        $left  = $this->resolveValue($left);
        $right = $this->resolveValue($right);

        $leftValue  = $this->getNumericValue($left);
        $rightValue = $this->getNumericValue($right);
        $leftUnit   = $this->getUnit($left);
        $rightUnit  = $this->getUnit($right);

        // Handle special cases for unit inheritance
        if (is_string($left) && is_numeric($left) && $this->isStructuredValue($right)) {
            return [(float) $left, '', $rightValue, $rightUnit];
        }

        if ($this->isStructuredValue($left) && is_string($right) && is_numeric($right)) {
            return [$leftValue, $leftUnit, (float) $right, $leftUnit];
        }

        if (is_numeric($left) && $this->isStructuredValue($right)) {
            return [$left, $rightUnit, $rightValue, $rightUnit];
        }

        if ($this->isStructuredValue($left) && is_numeric($right)) {
            return [$leftValue, $leftUnit, $right, $leftUnit];
        }

        return [$leftValue, $leftUnit, $rightValue, $rightUnit];
    }

    private function getNumericValue(mixed $value): ?float
    {
        if ($this->isStructuredValue($value)) {
            return $value['value'];
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private function getUnit(mixed $value): string
    {
        return is_array($value) && isset($value['unit']) ? $value['unit'] : '';
    }

    private function performNumericOperation(float $left, float $right, string $operator, string $unit): mixed
    {
        $result = match ($operator) {
            '+'     => $left + $right,
            '-'     => $left - $right,
            '*'     => $left * $right,
            '/'     => $right == 0 ? throw new CompilationException('Division by zero') : $left / $right,
            '%'     => $right == 0 ? throw new CompilationException('Modulo by zero') : $left % (int) $right,
            default => throw new CompilationException("Unknown operator: $operator"),
        };

        return $unit === '' ? $result : ['value' => $result, 'unit' => $unit];
    }

    private function buildCalcExpression(mixed $left, string $operator, mixed $right): string
    {
        $left  = $this->resolveValue($left);
        $right = $this->resolveValue($right);

        $leftString  = $this->valueFormatter->format($left);
        $rightString = $this->valueFormatter->format($right);

        if ($operator === ':') {
            return "$leftString: $rightString";
        }

        if (str_starts_with($leftString, 'calc(') || str_starts_with($rightString, 'calc(')) {
            return "$leftString $operator $rightString";
        }

        return "calc($leftString $operator $rightString)";
    }

    private function isStructuredValue(mixed $value): bool
    {
        return is_array($value) && isset($value['value']);
    }

    private function formatStructuredValue(array $value): string
    {
        return $value['value'] . ($value['unit'] ?? '');
    }

    private function removeQuotes(string $str): string
    {
        $length = strlen($str);

        if ($length >= 2 && $this->hasMatchingQuotes($str)) {
            return substr($str, 1, -1);
        }

        return $str;
    }

    private function isQuotedString(string $str): bool
    {
        return strlen($str) >= 2 && $this->hasMatchingQuotes($str);
    }

    private function hasMatchingQuotes(string $str): bool
    {
        $length = strlen($str);
        $first  = $str[0];
        $last   = $str[$length - 1];

        return in_array($first, self::QUOTE_CHARS, true) && $first === $last;
    }
}
