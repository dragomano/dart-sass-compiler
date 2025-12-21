<?php

declare(strict_types=1);

namespace DartSass\Evaluators;

use Closure;
use DartSass\Exceptions\CompilationException;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\OperationNode;
use DartSass\Utils\ValueFormatter;

use function array_map;
use function count;
use function implode;
use function is_array;
use function is_numeric;
use function is_string;
use function preg_match;
use function trim;

readonly class CalcFunctionEvaluator
{
    private const UNIT_REGEX = '(px|em|rem|%|vw|vh|vmin|vmax|pt|pc|in|cm|mm|deg|rad|turn|s|ms|Hz|kHz|dpi|dpcm|dppx)?';

    public function __construct(private ValueFormatter $valueFormatter) {}

    public function evaluate(array $args, Closure $evaluateExpression): mixed
    {
        $resolvedArgs = [];

        foreach ($args as $arg) {
            $resolvedArgs[] = $this->resolveNode($arg, $evaluateExpression);
        }

        if (count($resolvedArgs) === 1) {
            $result = $resolvedArgs[0];

            if ($this->isNumber($result)) {
                return $result;
            }
        }

        $argString = implode(', ', array_map(
            $this->valueFormatter->format(...),
            $resolvedArgs
        ));

        return 'calc(' . $argString . ')';
    }

    private function resolveNode(mixed $node, Closure $evaluateExpression): mixed
    {
        if ($node instanceof OperationNode) {
            $node = $this->ensurePrecedence($node);

            $left = $this->resolveNode($node->properties['left'], $evaluateExpression);
            $right = $this->resolveNode($node->properties['right'], $evaluateExpression);
            $operator = $node->properties['operator'];

            return $this->computeOperation($left, $operator, $right);
        }

        if ($node instanceof AstNode) {
            return $evaluateExpression($node);
        }

        return $node;
    }

    private function ensurePrecedence(OperationNode $node): OperationNode
    {
        $left = $node->properties['left'];
        $operator = $node->properties['operator'];
        $right = $node->properties['right'];

        if (($operator === '*' || $operator === '/') && $left instanceof OperationNode) {
            $subOp = $left->properties['operator'];

            if ($subOp === '+' || $subOp === '-') {
                $newLeft = $left->properties['left'];
                $mid = $left->properties['right'];

                $newRight = new OperationNode($mid, $operator, $right, $node->properties['line']);
                $node = new OperationNode($newLeft, $subOp, $newRight, $node->properties['line']);
            }
        }

        return $node;
    }

    private function computeOperation(mixed $left, string $operator, mixed $right): string|array
    {
        if ($this->isNumber($left) && $this->isNumber($right)) {
            $lVal = $this->normalizeNumber($left);
            $rVal = $this->normalizeNumber($right);

            $canCompute = false;
            $resultUnit = '';

            switch ($operator) {
                case '+':
                case '-':
                    if ($lVal['unit'] === $rVal['unit']) {
                        $canCompute = true;
                        $resultUnit = $lVal['unit'];
                    } elseif ($lVal['unit'] === '' || $rVal['unit'] === '') {
                        $resultUnit = $lVal['unit'] ?: $rVal['unit'];
                        $canCompute = true;
                    }

                    if ($canCompute) {
                        $value = match ($operator) {
                            '+' => $lVal['value'] + $rVal['value'],
                            '-' => $lVal['value'] - $rVal['value'],
                        };

                        return ['value' => $value, 'unit' => $resultUnit];
                    }

                    break;

                case '*':
                    if ($lVal['unit'] === '' || $rVal['unit'] === '') {
                        $value = $lVal['value'] * $rVal['value'];
                        $unit = $lVal['unit'] ?: $rVal['unit'];

                        return ['value' => $value, 'unit' => $unit];
                    }

                    break;

                case '/':
                    if ($rVal['value'] == 0) {
                        throw new CompilationException('Division by zero in calc');
                    }

                    if ($rVal['unit'] === '') {
                        return ['value' => $lVal['value'] / $rVal['value'], 'unit' => $lVal['unit']];
                    } elseif ($lVal['unit'] === $rVal['unit']) {
                        return ['value' => $lVal['value'] / $rVal['value'], 'unit' => ''];
                    }

                    break;
            }
        }

        $lStr = $this->formatResult($left);
        $rStr = $this->formatResult($right);

        return "$lStr $operator $rStr";
    }

    private function isNumber(mixed $val): bool
    {
        if (is_numeric($val) || (is_array($val) && isset($val['value'], $val['unit']))) {
            return true;
        }

        if (is_string($val)) {
            $val = trim($val);

            if (preg_match('/^(-?\d+(?:\.\d+)?)' . self::UNIT_REGEX . '$/', $val)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeNumber(mixed $val): array
    {
        if (is_array($val)) {
            return $val;
        }

        if (is_string($val)) {
            $val = trim($val);

            if (preg_match('/^(-?\d+(?:\.\d+)?)' . self::UNIT_REGEX . '$/', $val, $matches)) {
                return ['value' => (float) $matches[1], 'unit' => $matches[2] ?? ''];
            }
        }

        return ['value' => (float) $val, 'unit' => ''];
    }

    private function formatResult(mixed $val): string|int|float
    {
        if (is_array($val) && isset($val['value'])) {
            return $val['value'] . ($val['unit'] ?? '');
        }

        return $val;
    }
}
