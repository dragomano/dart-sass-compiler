<?php

declare(strict_types=1);

namespace DartSass\Evaluators;

use DartSass\Handlers\VariableHandler;
use DartSass\Parsers\Nodes\EachNode;
use DartSass\Parsers\Nodes\ForNode;
use DartSass\Parsers\Nodes\ListNode;
use DartSass\Parsers\Nodes\NodeType;
use DartSass\Parsers\Nodes\OperationNode;
use DartSass\Values\SassList;

use function array_slice;
use function count;
use function is_array;
use function is_int;
use function is_object;
use function key;
use function next;

readonly class UserFunctionEvaluator
{
    public function evaluate(array $func, array $args, callable $expression): mixed
    {
        $body    = $func['body'];
        $handler = $func['handler'];

        $handler->enterScope();

        try {
            $this->processArguments($func['args'], $args, $handler, $expression);

            return $this->executeBody($body, $args, $handler, $expression);
        } finally {
            $handler->exitScope();
        }
    }

    private function processArguments(
        array $funcArgs,
        array $callArgs,
        VariableHandler $variableHandler,
        callable $expression
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
                $variableHandler->define($paramName, new ListNode($remainingArgs));

                break; // No more args after arbitrary
            } else {
                $value = $callArgs[$argIndex] ?? null;
                if ($value === null && $default !== null) {
                    $value = $expression($default);
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
        callable $expression
    ): mixed {
        foreach ($body as $statement) {
            if ($statement->type === NodeType::VARIABLE) {
                $valueNode = $statement->value;
                $value     = $expression($valueNode);

                $variableHandler->define(
                    $statement->name,
                    $value,
                    $statement->global ?? false,
                    $statement->default ?? false,
                );
            } elseif ($statement->type === NodeType::FOR) {
                $this->executeFor($statement, $args, $variableHandler, $expression);
            } elseif ($statement->type === NodeType::EACH) {
                $this->executeEach($statement, $args, $variableHandler, $expression);
            } elseif ($statement->type === NodeType::RETURN) {
                $returnValue = $statement->value;

                if ($this->isMultiplicationOperation($returnValue)) {
                    return $this->handleMultiplication($returnValue, $args);
                }

                return $expression($returnValue);
            }
        }

        return null;
    }

    private function isMultiplicationOperation(mixed $returnValue): bool
    {
        if (! is_object($returnValue) || $returnValue->type !== NodeType::OPERATION) {
            return false;
        }

        if (($returnValue->operator ?? null) !== '*') {
            return false;
        }

        return ($returnValue->left->type ?? null) === NodeType::VARIABLE
            && ($returnValue->right->type ?? null) === NodeType::NUMBER;
    }

    private function handleMultiplication(OperationNode $returnValue, array $args): array|int|float
    {
        $argValue   = $args[0] ?? 0;
        $multiplier = $returnValue->right->value ?? '';

        if (is_array($argValue) && isset($argValue['value'])) {
            $result = $argValue['value'] * $multiplier;
            $unit   = $argValue['unit'] ?? '';

            return ['value' => $result, 'unit' => $unit];
        }

        return $argValue * $multiplier;
    }

    private function executeFor(
        ForNode $node,
        array $args,
        VariableHandler $variableHandler,
        callable $expression
    ): void {
        $from = (int) $expression($node->from);
        $to   = (int) $expression($node->to);

        $varName   = $node->variable;
        $inclusive = $node->inclusive ?? false;
        $body      = $node->body;

        $end  = $inclusive ? $to : $to - 1;
        $step = $from <= $end ? 1 : -1;

        if ($step > 0) {
            for ($i = $from; $i <= $end; $i += $step) {
                $variableHandler->define($varName, $i);
                $this->executeBody($body, $args, $variableHandler, $expression);
            }
        } else {
            for ($i = $from; $i >= $end; $i += $step) {
                $variableHandler->define($varName, $i);
                $this->executeBody($body, $args, $variableHandler, $expression);
            }
        }
    }

    private function executeEach(
        EachNode $node,
        array $args,
        VariableHandler $variableHandler,
        callable $expression
    ): void {
        $variables = $node->variables;
        $condition = $expression($node->condition);
        $body      = $node->body;

        $items = $this->extractItems($condition);

        foreach ($items as $item) {
            $this->assignVariables($variables, $item, $variableHandler);
            $this->executeBody($body, $args, $variableHandler, $expression);
        }
    }

    private function extractItems(mixed $condition): array
    {
        if ($condition instanceof SassList) {
            return $condition->value;
        }

        if ($condition instanceof ListNode) {
            return $condition->values;
        }

        if (is_array($condition)) {
            return $condition;
        }

        return [$condition];
    }

    private function assignVariables(array $variables, mixed $item, VariableHandler $variableHandler): void
    {
        if (count($variables) === 1) {
            $variableHandler->define($variables[0], $item);

            return;
        }

        $values = $this->extractItems($item);

        foreach ($variables as $i => $varName) {
            $variableHandler->define($varName, $values[$i] ?? null);
        }
    }
}
