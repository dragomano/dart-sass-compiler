<?php

declare(strict_types=1);

namespace DartSass\Evaluators;

use DartSass\Compilers\Environment;
use DartSass\Parsers\Nodes\EachNode;
use DartSass\Parsers\Nodes\ForNode;
use DartSass\Parsers\Nodes\ListNode;
use DartSass\Parsers\Nodes\NodeType;
use DartSass\Parsers\Nodes\OperationNode;
use DartSass\Values\SassList;

use function array_slice;
use function count;
use function is_array;
use function is_object;

readonly class UserFunctionEvaluator
{
    public function __construct(private Environment $environment) {}

    public function evaluate(array $func, array $args, callable $expression): mixed
    {
        $body = $func['body'];

        $this->environment->enterScope();

        try {
            $this->processArguments($func['args'], $args, $expression);

            return $this->executeBody($body, $args, $expression);
        } finally {
            $this->environment->exitScope();
        }
    }

    private function processArguments(
        array $funcArgs,
        array $callArgs,
        callable $expression
    ): void {
        $argIndex = 0;

        foreach ($funcArgs as $arg) {
            $paramName = $arg['name'];
            $arbitrary = $arg['arbitrary'];
            $default   = $arg['default'] ?? null;

            if ($arbitrary) {
                $remainingArgs = array_slice($callArgs, $argIndex);
                $this->environment->getCurrentScope()->setVariable($paramName, new ListNode($remainingArgs));

                break;
            } else {
                $value = $callArgs[$argIndex] ?? null;
                if ($value === null && $default !== null) {
                    $value = $expression($default);
                }

                $this->environment->getCurrentScope()->setVariable($paramName, $value);
                $argIndex++;
            }
        }
    }

    private function executeBody(
        array $body,
        array $args,
        callable $expression
    ): mixed {
        foreach ($body as $statement) {
            if ($statement->type === NodeType::VARIABLE) {
                $valueNode = $statement->value;
                $value     = $expression($valueNode);

                $this->environment->getCurrentScope()->setVariable(
                    $statement->name,
                    $value,
                    $statement->global ?? false,
                    $statement->default ?? false,
                );
            } elseif ($statement->type === NodeType::FOR) {
                $this->executeFor($statement, $args, $expression);
            } elseif ($statement->type === NodeType::EACH) {
                $this->executeEach($statement, $args, $expression);
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
                $this->environment->getCurrentScope()->setVariable($varName, $i);
                $this->executeBody($body, $args, $expression);
            }
        } else {
            for ($i = $from; $i >= $end; $i += $step) {
                $this->environment->getCurrentScope()->setVariable($varName, $i);
                $this->executeBody($body, $args, $expression);
            }
        }
    }

    private function executeEach(
        EachNode $node,
        array $args,
        callable $expression
    ): void {
        $variables = $node->variables;
        $condition = $expression($node->condition);
        $body      = $node->body;

        $items = $this->extractItems($condition);

        foreach ($items as $item) {
            $this->assignVariables($variables, $item);
            $this->executeBody($body, $args, $expression);
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

    private function assignVariables(array $variables, mixed $item): void
    {
        if (count($variables) === 1) {
            $this->environment->getCurrentScope()->setVariable($variables[0], $item);

            return;
        }

        $values = $this->extractItems($item);

        foreach ($variables as $i => $varName) {
            $this->environment->getCurrentScope()->setVariable($varName, $values[$i] ?? null);
        }
    }
}
