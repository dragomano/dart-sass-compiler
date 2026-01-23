<?php

declare(strict_types=1);

namespace DartSass\Evaluators;

use DartSass\Exceptions\CompilationException;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\ListNode;
use DartSass\Parsers\Nodes\OperationNode;
use DartSass\Parsers\Nodes\VariableNode;
use DartSass\Utils\ArithmeticCalculator;
use DartSass\Utils\StringFormatter;
use DartSass\Utils\ValueComparator;
use DartSass\Values\SassList;
use DartSass\Values\SassMap;
use DartSass\Values\SassNumber;

use function end;
use function explode;
use function is_array;
use function is_bool;
use function is_numeric;
use function is_string;
use function preg_match;
use function str_contains;
use function str_starts_with;
use function strtolower;
use function trim;

class ExpressionEvaluator extends AbstractEvaluator
{
    private const DIRECT_VALUE_TYPES = ['selector', 'hex_color', 'operator'];

    private const RECURSIVE_TYPES = ['condition', 'interpolation'];

    public function supports(mixed $expression): bool
    {
        if ($expression instanceof AstNode) {
            return true;
        }

        if (is_string($expression)) {
            return true;
        }

        return is_numeric($expression) || is_array($expression) || $expression === null || is_bool($expression);
    }

    public function evaluate(mixed $expression): mixed
    {
        if ($expression instanceof OperationNode) {
            return $this->context->engine->evaluateExpression($expression);
        }

        if ($expression instanceof AstNode) {
            return $this->evaluateAstNode($expression);
        }

        if (is_string($expression) && str_starts_with($expression, '$')) {
            return $this->evaluateVariableString($expression);
        }

        if (is_string($expression)) {
            return $this->tryParseNumericString($expression);
        }

        return $expression;
    }

    private function evaluateAstNode(AstNode $expr): mixed
    {
        $type  = $expr->type;
        $props = $expr->properties;

        if (in_array($type, self::DIRECT_VALUE_TYPES, true)) {
            return $props['value'];
        }

        if (in_array($type, self::RECURSIVE_TYPES, true)) {
            return $this->evaluate($props['expression']);
        }

        $values = ['Unknown expression type: ', $type, ' at line ', $props['line'] ?? 0];

        return match ($type) {
            'function'            => $this->evaluateFunctionExpression($expr),
            'number'              => $this->evaluateNumberExpression($expr),
            'string'              => $this->evaluateStringExpression($expr),
            'list'                => $this->evaluateListExpression($props),
            'map'                 => $this->evaluateMapExpression($props),
            'identifier'          => $this->evaluateIdentifierExpression($expr),
            'variable'            => $this->evaluate($this->context->variableHandler->get($props['name'])),
            'css_custom_property' => $props['name'],
            'property_access'     => $this->evaluatePropertyAccessExpression($expr),
            'css_property'        => $this->evaluateCssPropertyExpression($expr),
            'unary'               => $this->evaluateUnaryExpression($expr),
            'color'               => $this->evaluateColorExpression($expr),
            default               => throw new CompilationException(StringFormatter::concatMultiple($values)),
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

    private function tryParseNumericString(string $expr): string|array|float
    {
        if (preg_match('/^(\d+\.?\d*)\s*(px|em|rem|%)?$/', $expr, $matches)) {
            $value = (float) $matches[1];
            $unit  = $matches[2] ?? '';

            if ($unit === '') {
                return $value;
            }

            return SassNumber::tryFrom(['value' => $value, 'unit' => $unit])?->toArray()
                ?? ['value' => $value, 'unit' => $unit];
        }

        return $expr;
    }

    private function evaluateFunctionExpression(AstNode $expr): mixed
    {
        $name = $expr->properties['name'];
        $args = $expr->properties['args'] ?? [];

        return match (true) {
            $name === 'if'                  => $this->evaluateIfFunction($args),
            $name === 'calc'                => $this->context->calcEvaluator->evaluate($args, $this->evaluate(...)),
            $this->hasSlashSeparator($args) => $this->evaluateFunctionWithSlashSeparator($name, $args),
            $name === 'url'                 => $this->evaluateUrlFunction($args),
            default                         => $this->evaluateStandardFunction($name, $args),
        };
    }

    private function evaluateNumberExpression(AstNode $expr): string|array|int|float
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

        if ($unit === '') {
            return $numericValue;
        }

        $sassNumber = new SassNumber((float) $numericValue, $unit);

        return $sassNumber->toArray();
    }

    private function evaluateStringExpression(AstNode $expr): string
    {
        $value = $expr->properties['value'];
        $value = $this->context->interpolationEvaluator->evaluate($value, $this->evaluate(...));

        if (preg_match('/^[a-zA-Z_-][a-zA-Z0-9_-]*$/', $value)) {
            return $value;
        }

        return StringFormatter::forceQuoteString($value);
    }

    private function evaluateListExpression(array $props): SassList
    {
        return new SassList(
            $this->evaluateArguments($props['values']),
            $props['separator'] ?? 'comma',
            $props['bracketed'] ?? false
        );
    }

    private function evaluateMapExpression(array $props): SassMap
    {
        $pairs = $props['pairs'] ?? [];
        $map   = [];

        foreach ($pairs as [$key, $value]) {
            $evaluatedKey   = $this->evaluate($key);
            $evaluatedValue = $this->evaluate($value);

            $keyString = $this->convertKeyToString($evaluatedKey);

            if ($keyString !== null) {
                $map[$keyString] = $evaluatedValue;
            }
        }

        return new SassMap($map);
    }

    private function evaluateIdentifierExpression(AstNode $expr): mixed
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

        return StringFormatter::concatMultiple([$property, ': ', $this->formatValue($value)]);
    }

    private function evaluateColorExpression(AstNode $expr): AstNode
    {
        return $expr;
    }

    private function evaluateUnaryExpression(AstNode $expr): string|array|bool|float
    {
        $operand  = $this->evaluate($expr->properties['operand']);
        $operator = $expr->properties['operator'];

        // Try to use SassNumber for numeric operations
        $sassNumber = SassNumber::tryFrom($operand);

        if ($sassNumber !== null) {
            return match ($operator) {
                '+'     => $sassNumber->toArray()['unit'] === '' ? $sassNumber->getValue() : $sassNumber->toArray(),
                '-'     => $this->negateNumber($sassNumber),
                'not'   => ValueComparator::not($sassNumber->getValue()),
                default => throw new CompilationException("Unknown unary operator: $operator"),
            };
        }

        // Handle non-numeric operands
        return match ($operator) {
            'not'   => ValueComparator::not($operand),
            default => StringFormatter::concat($operator, $this->formatValue($operand)),
        };
    }

    private function evaluateArguments(array $args): array
    {
        foreach ($args as $key => $arg) {
            $args[$key] = $this->evaluate($arg);
        }

        return $args;
    }

    private function convertKeyToString(mixed $key): ?string
    {
        if (is_string($key)) {
            return trim($key, "'\"");
        }

        if (is_numeric($key)) {
            return (string) $key;
        }

        return null;
    }

    private function evaluateIfFunction(array $args): mixed
    {
        $result = $this->context->functionHandler->call('if', $args);

        return $this->evaluate($result);
    }

    private function evaluateFunctionWithSlashSeparator(string $name, array $args): mixed
    {
        $args = $this->evaluateArgumentsWithSlashSeparator($args);

        return $this->context->functionHandler->call($name, $args);
    }

    private function evaluateUrlFunction(array $args): mixed
    {
        $args = $this->evaluateUrlArguments($args);

        return $this->context->functionHandler->call('url', $args);
    }

    private function evaluateStandardFunction(string $name, array $args): mixed
    {
        $args = $this->evaluateArguments($args);

        if ($this->hasSpreadArguments($args)) {
            $args = $this->expandSpreadArguments($args);
        }

        return $this->context->functionHandler->call($name, $args);
    }

    private function isSpreadArgument(mixed $arg): bool
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
                $processedArgs = $this->appendSpreadValues($processedArgs, $spreadValue);
            } else {
                $processedArgs[] = $arg;
            }
        }

        return $processedArgs;
    }

    private function appendSpreadValues(array $processedArgs, mixed $spreadValue): array
    {
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
            $processedArgs[] = $spreadValue;
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

    private function containsDivisionOperation(mixed $arg): bool
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

    private function negateNumber(SassNumber $number): float|array
    {
        $negated = ArithmeticCalculator::negate($number);

        return $negated->getUnit() === null ? $negated->getValue() : $negated->toArray();
    }
}
