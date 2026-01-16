<?php

declare(strict_types=1);

namespace DartSass\Evaluators;

use DartSass\Compilers\CompilerContext;
use DartSass\Exceptions\CompilationException;
use DartSass\Modules\SassList;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\ListNode;
use DartSass\Parsers\Nodes\OperationNode;
use DartSass\Parsers\Nodes\VariableNode;

use function end;
use function explode;
use function is_array;
use function is_numeric;
use function is_string;
use function preg_match;
use function str_contains;
use function str_starts_with;
use function strtolower;

readonly class ExpressionEvaluator
{
    public function __construct(private CompilerContext $context) {}

    public function evaluate(mixed $expr)
    {
        if ($expr instanceof OperationNode) {
            return $this->context->engine->evaluateExpression($expr);
        }

        if ($expr instanceof AstNode) {
            $type  = $expr->type;
            $props = $expr->properties;

            return match ($type) {
                'color',
                'selector',
                'hex_color',
                'operator'            => $props['value'],
                'function'            => $this->evaluateFunctionExpression($expr),
                'number'              => $this->evaluateNumberExpression($expr),
                'string'              => $this->evaluateStringExpression($expr),
                'list'                => $this->evaluateListExpression($props),
                'map'                 => $this->evaluateMapExpression($props),
                'identifier'          => $this->evaluateIdentifierExpression($expr),
                'variable'            => $this->evaluate($this->context->variableHandler->get($props['name'])),
                'condition',
                'interpolation'       => $this->evaluate($props['expression']),
                'css_custom_property' => $props['name'],
                'property_access'     => $this->evaluatePropertyAccessExpression($expr),
                'css_property'        => $this->evaluateCssPropertyExpression($expr),
                'unary'               => $this->evaluateUnaryExpression($expr),
                default               => throw new CompilationException(
                    "Unknown expression type: $type at line " . ($props['line'] ?? 0)
                ),
            };
        }

        if (is_string($expr) && str_starts_with($expr, '$')) {
            return $this->evaluateVariableString($expr);
        }

        if (is_string($expr) && preg_match('/^(\d+\.?\d*)\s*(px|em|rem|%)?$/', $expr, $matches)) {
            $value = (float) $matches[1];
            $unit  = $matches[2] ?? '';

            return $unit === '' ? $value : ['value' => $value, 'unit' => $unit];
        }

        return $expr;
    }

    public function evaluateNumberExpression(AstNode $expr): string|array|int|float
    {
        $value = $expr->properties['value'];
        $unit  = $expr->properties['unit'] ?? '';

        if (is_array($value)) {
            $value = $value['value'];
        }

        if ($unit === '' && is_string($value)) {
            if (preg_match('/^(-?\d+(?:\.\d+)?)\s*(\S+)?$/', $value, $matches)) {
                $unit  = $matches[2] ?? '';
                $value = (float) $matches[1];
            }
        }

        $numericValue = is_numeric($value) ? $value : (float) $value;

        return $unit === '' ? $numericValue : ['value' => $numericValue, 'unit' => $unit];
    }

    public function evaluateStringExpression(AstNode $expr): string
    {
        $value = $expr->properties['value'];
        $value = $this->context->interpolationEvaluator->evaluate($value, $this->evaluate(...));

        if (str_starts_with($value, 'calc(')) {
            return $value;
        }

        if (preg_match('/^[a-zA-Z_-][a-zA-Z0-9_-]*$/', $value)) {
            return $value;
        }

        return '"' . $value . '"';
    }

    public function evaluateListExpression(array $props): SassList
    {
        return new SassList(
            $this->evaluateArguments($props['values']),
            $props['separator'] ?? 'comma',
            $props['bracketed'] ?? false
        );
    }

    public function evaluateMapExpression(array $props): array
    {
        $pairs = $props['pairs'] ?? [];
        $map   = [];

        foreach ($pairs as [$key, $value]) {
            $evaluatedKey   = $this->evaluate($key);
            $evaluatedValue = $this->evaluate($value);

            // Convert key to string representation
            $keyString = $this->convertKeyToString($evaluatedKey);

            if ($keyString !== null) {
                $map[$keyString] = $evaluatedValue;
            }
        }

        return $map;
    }

    private function convertKeyToString(mixed $key): ?string
    {
        if (is_string($key)) {
            return trim($key, "'\"");
        }

        if ($key instanceof AstNode) {
            switch ($key->type) {
                case 'identifier':
                    return $key->properties['value'];
                case 'string':
                    return trim($key->properties['value'], "'\"");
                case 'number':
                    return (string) $key->properties['value'];
            }
        }

        if (is_numeric($key)) {
            return (string) $key;
        }

        return null;
    }

    public function evaluateIdentifierExpression(AstNode $expr): mixed
    {
        $value = $expr->properties['value'];

        if (is_string($value)) {
            return match (strtolower($value)) {
                'true'  => true,
                'false' => false,
                'null'  => null,
                default => $value,
            };
        }

        return $value;
    }

    private function evaluateFunctionExpression(AstNode $expr)
    {
        $name = $expr->properties['name'];
        $args = $expr->properties['args'] ?? [];

        if ($name === 'if') {
            $result = $this->context->functionHandler->call('if', $args);

            return $this->evaluate($result);
        }

        if ($name === 'calc') {
            return $this->context->calcEvaluator->evaluate($args, $this->evaluate(...));
        }

        if ($this->hasSlashSeparator($args)) {
            $args = $this->evaluateArgumentsWithSlashSeparator($args);

            return $this->context->functionHandler->call($name, $args);
        }

        if ($name === 'url') {
            $args = $this->evaluateUrlArguments($args);

            return $this->context->functionHandler->call('url', $args);
        }

        $args = $this->evaluateArguments($args);

        if ($this->hasSpreadArguments($args)) {
            $args = $this->expandSpreadArguments($args);
        }

        return $this->context->functionHandler->call($name, $args);
    }

    private function isSpreadArgument($arg): bool
    {
        return is_array($arg) && isset($arg['type']) && $arg['type'] === 'spread';
    }

    private function hasSpreadArguments(array $args): bool
    {
        foreach ($args as $arg) {
            if ($this->isSpreadArgument($arg)) {
                return true;
            }
        }

        return false;
    }

    private function expandSpreadArguments(array $args): array
    {
        $processedArgs = [];

        foreach ($args as $arg) {
            if ($this->isSpreadArgument($arg)) {
                $spreadValue = $this->evaluate($arg['value']);

                // If value is a SassList, unpack its elements
                if ($spreadValue instanceof SassList) {
                    foreach ($spreadValue->value as $item) {
                        $processedArgs[] = $item;
                    }
                } elseif ($spreadValue instanceof ListNode) {
                    foreach ($spreadValue->values as $item) {
                        $processedArgs[] = $this->evaluate($item);
                    }
                } elseif (is_array($spreadValue)) {
                    foreach ($spreadValue as $item) {
                        $processedArgs[] = $this->evaluate($item);
                    }
                } else {
                    // If not a list, add as regular argument
                    $processedArgs[] = $spreadValue;
                }
            } else {
                $processedArgs[] = $arg;
            }
        }

        return $processedArgs;
    }

    private function hasSlashSeparator(array $args): bool
    {
        if (empty($args)) {
            return false;
        }

        $lastArg = end($args);

        return $this->containsDivisionOperation($lastArg);
    }

    private function evaluateUrlArguments(array $args): array
    {
        $processedArgs = [];

        foreach ($args as $arg) {
            if ($arg instanceof AstNode && $arg->type === 'string') {
                $processedArgs[] = $this->evaluateUrlString($arg);
            } else {
                $processedArgs[] = $this->evaluate($arg);
            }
        }

        return $processedArgs;
    }

    private function evaluateUrlString(AstNode $arg): array
    {
        $originalContent = $arg->properties['value'];

        if (str_contains($originalContent, '#{$')) {
            $wasQuoted = preg_match('/^["\'](.*)["\']$/', $originalContent);
            $evaluated = $this->evaluate($arg);

            return [
                'value'  => $evaluated,
                'quoted' => $wasQuoted,
            ];
        }

        $wasQuoted = preg_match('/^["\'](.*)["\']$/', $originalContent, $matches);

        if ($wasQuoted) {
            return [
                'value'  => $matches[1],
                'quoted' => true,
            ];
        }

        return [
            'value'  => $originalContent,
            'quoted' => false,
        ];
    }

    private function containsDivisionOperation($arg): bool
    {
        if (! $arg instanceof AstNode) {
            return false;
        }

        if ($arg->type === 'operation' && isset($arg->properties['operator'])) {
            return $arg->properties['operator'] === '/';
        }

        return false;
    }

    private function evaluateArgumentsWithSlashSeparator(array $args): array
    {
        $processedArgs = [];

        foreach ($args as $arg) {
            if ($this->containsDivisionOperation($arg)) {
                $hueArg   = $this->evaluate($arg->properties['left'] ?? $arg);
                $alphaArg = $this->evaluate($arg->properties['right'] ?? null);

                $processedArgs[] = $hueArg;
                $processedArgs[] = $alphaArg;
            } else {
                $processedArgs[] = $this->evaluate($arg);
            }
        }

        return $processedArgs;
    }

    private function evaluatePropertyAccessExpression(AstNode $expr): mixed
    {
        $namespace = $this->evaluate($expr->properties['namespace']);

        $propertyNode = $expr->properties['property'];
        if ($propertyNode instanceof VariableNode) {
            $propertyName = $propertyNode->properties['name'];
        } elseif (is_string($propertyNode)) {
            $propertyName = $propertyNode;
        } else {
            $propertyName = $this->evaluate($propertyNode);
        }

        if (is_string($namespace) && is_string($propertyName) && str_starts_with($propertyName, '$')) {
            return $this->context->moduleHandler->getProperty($namespace, $propertyName, $this->evaluate(...));
        }

        if (is_string($namespace) && ! str_starts_with($namespace, '$')) {
            return $namespace;
        }

        throw new CompilationException("Invalid property access: $namespace.$propertyName");
    }

    private function evaluateCssPropertyExpression(AstNode $expr): string
    {
        $property = $expr->properties['property'];
        $value    = $this->evaluate($expr->properties['value']);

        return $property . ': ' . $this->context->valueFormatter->format($value);
    }

    private function evaluateUnaryExpression(AstNode $expr): mixed
    {
        $operand  = $this->evaluate($expr->properties['operand']);
        $operator = $expr->properties['operator'];

        if (is_numeric($operand)) {
            return match ($operator) {
                '+'     => +$operand,
                '-'     => -$operand,
                default => throw new CompilationException("Unknown unary operator: $operator"),
            };
        }

        if (is_array($operand) && isset($operand['value']) && is_numeric($operand['value'])) {
            return match ($operator) {
                '+'     => $operand,
                '-'     => ['value' => -$operand['value'], 'unit' => $operand['unit']],
                'not'   => ! $operand['value'],
                default => throw new CompilationException("Unknown unary operator: $operator"),
            };
        }

        return match ($operator) {
            'not'   => ! $operand,
            default => $operator . $this->context->valueFormatter->format($operand),
        };
    }

    private function evaluateVariableString(string $expr): mixed
    {
        if (str_contains($expr, '.')) {
            [$namespace, $name] = explode('.', $expr, 2);
            $propertyName = '$' . $name;

            try {
                return $this->context->moduleHandler->getProperty($namespace, $propertyName, $this->evaluate(...));
            } catch (CompilationException) {
                try {
                    return $this->context->variableHandler->get($expr);
                } catch (CompilationException) {
                    throw new CompilationException("Undefined property: $propertyName in module $namespace");
                }
            }
        }

        return $this->context->variableHandler->get($expr);
    }

    private function evaluateArguments(array $args): array
    {
        foreach ($args as $key => $arg) {
            $args[$key] = $this->evaluate($arg);
        }

        return $args;
    }
}
