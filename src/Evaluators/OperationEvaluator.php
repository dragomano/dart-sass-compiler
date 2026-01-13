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
use function str_starts_with;

readonly class OperationEvaluator
{
    public function __construct(private ValueFormatter $valueFormatter) {}

    public function evaluate(mixed $left, string $operator, mixed $right): mixed
    {
        if ($left instanceof LazyValue) {
            $left = $left->getValue();
        }

        if ($right instanceof LazyValue) {
            $right = $right->getValue();
        }

        if (in_array($operator, ['==', '!=', '<', '>', '<=', '>=', 'and', 'or'], true)) {
            return $this->evaluateComparison($left, $operator, $right);
        }

        // Handle string concatenation
        if ($operator === '+') {
            if (is_string($left) && is_string($right)) {
                if (is_numeric($left) && is_numeric($right)) {
                    return (float) $left + (float) $right;
                } else {
                    return $left . $right;
                }
            }

            if (is_string($left) && is_numeric($right)) {
                return $left . $right;
            }

            if (is_numeric($left) && is_string($right)) {
                return $left . $right;
            }

            if (is_array($left) && isset($left['value']) && is_string($right)) {
                return $left['value'] . ($left['unit'] ?? '') . $right;
            }

            if (is_string($left) && is_array($right) && isset($right['value'])) {
                return $left . $right['value'] . ($right['unit'] ?? '');
            }
        }

        // Handle CSS background-size separator
        if ($operator === '/' && is_string($left) && is_string($right)) {
            return $left . ' / ' . $right;
        }

        [$leftValue, $leftUnit, $rightValue, $rightUnit] = $this->extractNumericValues($left, $right);

        if ($leftValue !== null && $rightValue !== null) {
            // For multiplication and division, allow operations with empty units
            if ($leftUnit === $rightUnit) {
                return $this->performNumericOperation($leftValue, $rightValue, $operator, $leftUnit);
            }

            // For * and / operations, if one side has no unit, use the unit from the other side
            if (in_array($operator, ['*', '/'], true)) {
                $unit = $leftUnit !== '' ? $leftUnit : $rightUnit;
                if ($unit !== '') {
                    return $this->performNumericOperation($leftValue, $rightValue, $operator, $unit);
                }
            }
        }

        if ($operator === '*' || $operator === '/') {
            if ($leftValue === null || $rightValue === null) {
                $isLeftSimpleString  = is_string($left) && ! str_contains($left, '(');
                $isRightSimpleString = is_string($right) && ! str_contains($right, '(');

                if ($isLeftSimpleString || $isRightSimpleString) {
                    $leftStr  = $this->valueFormatter->format($left);
                    $rightStr = $this->valueFormatter->format($right);

                    throw new CompilationException("Undefined operation \"$leftStr $operator $rightStr\".");
                }
            }
        }

        return $this->buildCalcExpression($left, $operator, $right);
    }

    private function evaluateComparison(mixed $left, string $operator, mixed $right): bool
    {
        return match ($operator) {
            '=='    => $this->valuesAreEqual($left, $right),
            '!='    => ! $this->valuesAreEqual($left, $right),
            '<'     => $this->compareValues($left, $right) < 0,
            '>'     => $this->compareValues($left, $right) > 0,
            '<='    => $this->compareValues($left, $right) <= 0,
            '>='    => $this->compareValues($left, $right) >= 0,
            'and'   => $this->isTruthy($left) && $this->isTruthy($right),
            'or'    => $this->isTruthy($left) || $this->isTruthy($right),
            default => throw new CompilationException("Unknown comparison operator: $operator"),
        };
    }

    private function valuesAreEqual(mixed $left, mixed $right): bool
    {
        if ($left === null && $right === null) {
            return true;
        }

        if ($left === null || $right === null) {
            return false;
        }

        if (is_array($left) && isset($left['value']) && is_array($right) && isset($right['value'])) {
            $leftUnit  = $left['unit'] ?? '';
            $rightUnit = $right['unit'] ?? '';

            if ($leftUnit === $rightUnit) {
                return (float) $left['value'] === (float) $right['value'];
            }

            return false;
        }

        if (is_numeric($left) && is_numeric($right)) {
            return (float) $left === (float) $right;
        }

        if (is_string($left) && is_string($right)) {
            return $left === $right;
        }

        if (is_array($left) && isset($left['value']) && is_numeric($right)) {
            $leftUnit = $left['unit'] ?? '';

            return $leftUnit === '' && (float) $left['value'] === (float) $right;
        }

        if (is_numeric($left) && is_array($right) && isset($right['value'])) {
            $rightUnit = $right['unit'] ?? '';

            return $rightUnit === '' && (float) $left === (float) $right['value'];
        }

        return $left == $right;
    }

    private function compareValues(mixed $left, mixed $right): int
    {
        [$leftValue, , $rightValue, ] = $this->extractNumericValues($left, $right);

        if ($leftValue === null || $rightValue === null) {
            return 0;
        }

        if ($leftValue < $rightValue) {
            return -1;
        } elseif ($leftValue > $rightValue) {
            return 1;
        }

        return 0;
    }

    private function isTruthy(mixed $value): bool
    {
        return ! in_array($value, [false, null, 0, ''], true);
    }

    private function extractNumericValues(mixed $left, mixed $right): array
    {
        if ($left instanceof LazyValue) {
            $left = $left->getValue();
        }

        if ($right instanceof LazyValue) {
            $right = $right->getValue();
        }

        if (is_string($left) && is_numeric($left) && is_array($right) && isset($right['value'])) {
            return [(float) $left, '', $right['value'], $right['unit'] ?? ''];
        }

        if (is_array($left) && isset($left['value']) && is_string($right) && is_numeric($right)) {
            return [$left['value'], $left['unit'] ?? '', (float) $right, $left['unit'] ?? ''];
        }

        if (is_numeric($left) && is_array($right) && isset($right['value'])) {
            return [$left, $right['unit'] ?? '', $right['value'], $right['unit'] ?? ''];
        }

        if (is_array($left) && isset($left['value']) && is_numeric($right)) {
            return [$left['value'], $left['unit'] ?? '', $right, $left['unit'] ?? ''];
        }

        $leftValue = is_array($left) && isset($left['value'])
            ? $left['value']
            : (is_numeric($left) ? (float) $left : null);

        $rightValue = is_array($right) && isset($right['value'])
            ? $right['value']
            : (is_numeric($right) ? (float) $right : null);

        $leftUnit  = is_array($left) && isset($left['unit']) ? $left['unit'] : '';
        $rightUnit = is_array($right) && isset($right['unit']) ? $right['unit'] : '';

        return [$leftValue, $leftUnit, $rightValue, $rightUnit];
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
        if ($left instanceof LazyValue) {
            $left = $left->getValue();
        }

        if ($right instanceof LazyValue) {
            $right = $right->getValue();
        }

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
}
