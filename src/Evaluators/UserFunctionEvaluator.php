<?php

declare(strict_types=1);

namespace DartSass\Evaluators;

use DartSass\Handlers\VariableHandler;
use DartSass\Modules\SassList;
use DartSass\Parsers\Nodes\ListNode;

use function array_slice;
use function count;
use function is_array;
use function is_int;
use function key;
use function next;

readonly class UserFunctionEvaluator
{
    public function evaluate(array $func, array $args, callable $evaluateExpression): mixed
    {
        $body    = $func['body'];
        $handler = $func['handler'];

        $handler->enterScope();

        try {
            $this->processArguments($func['args'], $args, $handler, $evaluateExpression);

            return $this->executeBody($body, $args, $handler, $evaluateExpression);
        } finally {
            $handler->exitScope();
        }
    }

    private function processArguments(
        array $funcArgs,
        array $callArgs,
        VariableHandler $variableHandler,
        callable $evaluateExpression
    ): void {
        $argIndex = 0;

        foreach ($funcArgs as $arg) {
            if (is_array($arg)) {
                // New structure: ['name' => string, 'arbitrary' => bool, ?'default' => AstNode]
                $paramName = $arg['name'];
                $arbitrary = $arg['arbitrary'];
                $default   = $arg['default'] ?? null;
            } elseif (is_int(key($funcArgs))) {
                // Old structure: array of strings
                $paramName = $arg;
                $arbitrary = false;
                $default   = null;
            } else {
                // Old associative structure: name => default
                $paramName = key($funcArgs);
                $arbitrary = false;
                $default   = $arg;

                next($funcArgs);
            }

            if ($arbitrary) {
                // Collect remaining args as list
                $remainingArgs = array_slice($callArgs, $argIndex);
                $variableHandler->define($paramName, new ListNode($remainingArgs, 0, 'comma'));

                break; // No more args after arbitrary
            } else {
                $value = $callArgs[$argIndex] ?? $default;
                if ($value === null && $default !== null) {
                    $value = $evaluateExpression($default);
                }

                $variableHandler->define($paramName, $value);
                $argIndex++;
            }
        }
    }

    private function executeBody(
        array $body,
        array $args,
        VariableHandler $variableHandler,
        callable $evaluateExpression
    ): mixed {
        foreach ($body as $statement) {
            if ($statement->type === 'variable') {
                $valueNode = $statement->properties['value'];
                $value     = $evaluateExpression($valueNode);

                $variableHandler->define(
                    $statement->properties['name'],
                    $value,
                    $statement->global ?? false,
                    $statement->default ?? false,
                );
            } elseif ($statement->type === 'for') {
                $this->executeFor($statement, $args, $variableHandler, $evaluateExpression);
            } elseif ($statement->type === 'each') {
                $this->executeEach($statement, $args, $variableHandler, $evaluateExpression);
            } elseif ($statement->type === 'return') {
                $returnValue = $statement->properties['value'];

                if ($this->isMultiplicationOperation($returnValue)) {
                    return $this->handleMultiplication($returnValue, $args);
                }

                return $evaluateExpression($returnValue);
            }
        }

        return null;
    }

    private function isMultiplicationOperation($returnValue): bool
    {
        return $returnValue->type === 'operation'
            && $returnValue->properties['left']->type === 'variable'
            && $returnValue->properties['operator'] === '*'
            && $returnValue->properties['right']->type === 'number';
    }

    private function handleMultiplication($returnValue, array $args): array|int|float
    {
        $argValue   = $args[0] ?? 0;
        $multiplier = $returnValue->properties['right']->properties['value'];

        if (is_array($argValue) && isset($argValue['value'])) {
            $result = $argValue['value'] * $multiplier;
            $unit   = $argValue['unit'] ?? '';

            return ['value' => $result, 'unit' => $unit];
        }

        return $argValue * $multiplier;
    }

    private function executeFor(
        $node,
        array $args,
        VariableHandler $variableHandler,
        callable $evaluateExpression
    ): void {
        $from = (int) $evaluateExpression($node->properties['from']);
        $to   = (int) $evaluateExpression($node->properties['to']);

        $varName   = $node->properties['variable'];
        $inclusive = $node->properties['inclusive'] ?? false;
        $body      = $node->properties['body'];

        $end  = $inclusive ? $to : $to - 1;
        $step = $from <= $end ? 1 : -1;

        if ($step > 0) {
            for ($i = $from; $i <= $end; $i += $step) {
                $variableHandler->define($varName, $i);
                $this->executeBody($body, $args, $variableHandler, $evaluateExpression);
            }
        } else {
            for ($i = $from; $i >= $end; $i += $step) {
                $variableHandler->define($varName, $i);
                $this->executeBody($body, $args, $variableHandler, $evaluateExpression);
            }
        }
    }

    private function executeEach(
        $node,
        array $args,
        VariableHandler $variableHandler,
        callable $evaluateExpression
    ): void {
        $variables = $node->properties['variables'];
        $condition = $evaluateExpression($node->properties['condition']);
        $body      = $node->properties['body'];

        if ($condition instanceof SassList) {
            $items = $condition->value;
        } elseif ($condition instanceof ListNode) {
            $items = $condition->value;
        } elseif (is_array($condition)) {
            $items = $condition;
        } else {
            $items = [$condition];
        }

        foreach ($items as $item) {
            if (count($variables) === 1) {
                $variableHandler->define($variables[0], $item);
            } else {
                // For multiple variables, assume $item is array or ListNode
                if ($item instanceof ListNode) {
                    $values = $item->values;
                } elseif (is_array($item)) {
                    $values = $item;
                } else {
                    $values = [$item];
                }

                foreach ($variables as $i => $varName) {
                    $variableHandler->define($varName, $values[$i] ?? null);
                }
            }

            $this->executeBody($body, $args, $variableHandler, $evaluateExpression);
        }
    }
}
