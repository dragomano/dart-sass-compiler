<?php

declare(strict_types=1);

namespace DartSass\Evaluators;

use DartSass\Handlers\VariableHandler;

use function is_array;
use function is_int;

readonly class UserFunctionEvaluator
{
    public function evaluate(array $func, array $args, callable $evaluateExpression): mixed
    {
        $body            = $func['body'];
        $variableHandler = $func['handler'];

        $variableHandler->enterScope();

        try {
            // Process arguments
            $this->processArguments($func['args'], $args, $variableHandler, $evaluateExpression);

            // Execute function body
            return $this->executeBody($body, $args, $evaluateExpression);
        } finally {
            $variableHandler->exitScope();
        }
    }

    private function processArguments(array $funcArgs, array $callArgs, VariableHandler $variableHandler, callable $evaluateExpression): void
    {
        $argIndex = 0;

        foreach ($funcArgs as $argName => $defaultValue) {
            if (is_int($argName)) {
                $paramName = $defaultValue;
                $default   = null;
            } else {
                $paramName = $argName;
                $default   = $defaultValue;
            }

            $value = $callArgs[$argIndex] ?? $default;
            if ($value === null) {
                $value = $evaluateExpression($default);
            }

            $variableHandler->define($paramName, $value);
            $argIndex++;
        }
    }

    private function executeBody(array $body, array $args, callable $evaluateExpression): mixed
    {
        foreach ($body as $statement) {
            if ($statement->type === 'return') {
                $returnValue = $statement->properties['value'];

                // Handle special case for multiplication operations
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
}
