<?php

declare(strict_types=1);

namespace DartSass\Evaluators;

use DartSass\Exceptions\CompilationException;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\ConditionNode;
use DartSass\Parsers\Nodes\CssCustomPropertyNode;
use DartSass\Parsers\Nodes\CssPropertyNode;
use DartSass\Parsers\Nodes\FunctionNode;
use DartSass\Parsers\Nodes\IdentifierNode;
use DartSass\Parsers\Nodes\InterpolationNode;
use DartSass\Parsers\Nodes\ListNode;
use DartSass\Parsers\Nodes\MapNode;
use DartSass\Parsers\Nodes\NodeType;
use DartSass\Parsers\Nodes\NumberNode;
use DartSass\Parsers\Nodes\OperationNode;
use DartSass\Parsers\Nodes\OperatorNode;
use DartSass\Parsers\Nodes\PropertyAccessNode;
use DartSass\Parsers\Nodes\SelectorNode;
use DartSass\Parsers\Nodes\StringNode;
use DartSass\Parsers\Nodes\UnaryNode;
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
        if ($expr instanceof SelectorNode || $expr instanceof OperatorNode) {
            return $expr->value;
        }

        if ($expr instanceof ConditionNode || $expr instanceof InterpolationNode) {
            return $this->evaluate($expr->expression);
        }

        $type = $expr->type;

        $values = ['Unknown expression type: ', $type->value, ' at line ', $expr->line ?? 0];

        return match ($type) {
            NodeType::FUNCTION            => $this->evaluateFunctionExpression($expr),
            NodeType::NUMBER              => $this->evaluateNumberExpression($expr),
            NodeType::STRING              => $this->evaluateStringExpression($expr),
            NodeType::LIST                => $this->evaluateListExpression($expr),
            NodeType::MAP                 => $this->evaluateMapExpression($expr),
            NodeType::IDENTIFIER          => $this->evaluateIdentifierExpression($expr),
            NodeType::VARIABLE            => $this->evaluateVariableExpression($expr),
            NodeType::CSS_CUSTOM_PROPERTY => $this->evaluateCssCustomProperty($expr),
            NodeType::PROPERTY_ACCESS     => $this->evaluatePropertyAccessExpression($expr),
            NodeType::CSS_PROPERTY        => $this->evaluateCssPropertyExpression($expr),
            NodeType::UNARY               => $this->evaluateUnaryExpression($expr),
            NodeType::COLOR,
            NodeType::HEX_COLOR           => $expr,
            default                       => throw new CompilationException(StringFormatter::concatMultiple($values)),
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

    private function evaluateFunctionExpression(FunctionNode|AstNode $expr): mixed
    {
        $name = $expr->name;
        $args = $expr->args ?? [];

        if ($this->hasSlashSeparator($args)) {
            return $this->evaluateFunctionWithSlashSeparator($name, $args);
        }

        return match (true) {
            $name === 'if'   => $this->evaluateIfFunction($args),
            $name === 'calc' => $this->context->calcEvaluator->evaluate($args, $this->evaluate(...)),
            $name === 'url'  => $this->evaluateUrlFunction($args),
            default          => $this->evaluateStandardFunction($name, $args),
        };
    }

    private function evaluateNumberExpression(NumberNode|AstNode $expr): string|array|int|float
    {
        $value = $expr->value;
        $unit  = $expr->unit ?? '';

        if ($unit === '') {
            return $value;
        }

        $sassNumber = new SassNumber($value, $unit);

        return $sassNumber->toArray();
    }

    private function evaluateStringExpression(StringNode|AstNode $expr): string
    {
        $value = $expr->value;
        $value = $this->context->interpolationEvaluator->evaluate($value, $this->evaluate(...));

        if (preg_match('/^[a-zA-Z_-][a-zA-Z0-9_-]*$/', $value)) {
            return $value;
        }

        return StringFormatter::forceQuoteString($value);
    }

    private function evaluateListExpression(ListNode|AstNode $expr): SassList
    {
        return new SassList(
            $this->evaluateArguments($expr->values),
            $expr->separator ?? 'comma',
            $expr->bracketed ?? false
        );
    }

    private function evaluateMapExpression(MapNode|AstNode $expr): SassMap
    {
        $pairs = $expr->pairs ?? [];
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

    private function evaluateIdentifierExpression(IdentifierNode|AstNode $expr): mixed
    {
        $value = $expr->value;

        return match (strtolower($value)) {
            'true'  => true,
            'false' => false,
            'null'  => null,
            default => $value,
        };
    }

    private function evaluateVariableExpression(VariableNode|AstNode $expr): mixed
    {
        return $this->evaluate($this->context->variableHandler->get($expr->name));
    }

    private function evaluateCssCustomProperty(CssCustomPropertyNode|AstNode $expr): mixed
    {
        return $expr->name;
    }

    private function evaluatePropertyAccessExpression(PropertyAccessNode|AstNode $expr): mixed
    {
        $namespace = $this->evaluate($expr->namespace);

        $propertyNode = $expr->property;
        if ($propertyNode instanceof VariableNode) {
            $propertyName = $propertyNode->name;
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

    private function evaluateCssPropertyExpression(CssPropertyNode|AstNode $expr): string
    {
        $property = $expr->property;
        $value    = $this->evaluate($expr->value);

        return StringFormatter::concatMultiple([$property, ': ', $this->formatValue($value)]);
    }

    private function evaluateUnaryExpression(UnaryNode|AstNode $expr): string|array|bool|float
    {
        $operand  = $this->evaluate($expr->operand);
        $operator = $expr->operator;

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
                $spreadValue   = $this->evaluate($arg['value']);
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
            if ($arg instanceof StringNode) {
                $processedArgs[] = $this->evaluateUrlString($arg);
            } else {
                $processedArgs[] = $this->evaluate($arg);
            }
        }

        return $processedArgs;
    }

    private function evaluateUrlString(StringNode|AstNode $arg): array
    {
        $originalContent = $arg->value;

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

        if ($arg->type === NodeType::OPERATION && isset($arg->operator)) {
            return $arg->operator === '/';
        }

        return false;
    }

    private function evaluateArgumentsWithSlashSeparator(array $args): array
    {
        $processedArgs = [];

        foreach ($args as $arg) {
            if ($this->containsDivisionOperation($arg)) {
                $hueArg   = $this->evaluate($arg->left ?? $arg);
                $alphaArg = $this->evaluate($arg->right ?? null);

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
