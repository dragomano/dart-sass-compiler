<?php

declare(strict_types=1);

namespace DartSass\Evaluators;

use Closure;
use DartSass\Exceptions\CompilationException;
use DartSass\Handlers\FunctionHandler;
use DartSass\Handlers\ModuleHandler;
use DartSass\Handlers\VariableHandler;
use DartSass\Modules\SassList;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\OperationNode;
use DartSass\Parsers\Nodes\VariableNode;
use DartSass\Utils\ValueFormatter;

use function array_map;
use function end;
use function explode;
use function is_array;
use function is_numeric;
use function is_string;
use function preg_match;
use function str_contains;
use function str_starts_with;
use function strtolower;

class ExpressionEvaluator
{
    private ?Closure $evaluateCallback = null;

    public function __construct(
        private readonly VariableHandler $variableHandler,
        private readonly FunctionHandler $functionHandler,
        private readonly ModuleHandler $moduleHandler,
        private readonly ValueFormatter $valueFormatter,
        private readonly CalcFunctionEvaluator $calcEvaluator,
        private readonly InterpolationEvaluator $interpolationEvaluator
    ) {}

    public function setEvaluateCallback(Closure $callback): void
    {
        $this->evaluateCallback = $callback;
    }

    public function evaluate(mixed $expr)
    {
        if ($expr instanceof OperationNode && $this->evaluateCallback !== null) {
            return ($this->evaluateCallback)($expr);
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
                'identifier'          => $this->evaluateIdentifierExpression($expr),
                'variable'            => $this->evaluateVariableNode($props['name']),
                'condition'           => $this->evaluate($props['expression']),
                'css_custom_property' => $props['name'],
                'interpolation'       => $this->valueFormatter->format($this->evaluate($props['expression'])),
                'property_access'     => $this->evaluatePropertyAccessExpression($expr),
                'css_property'        => $this->evaluateCssPropertyExpression($expr),
                'unary'               => $this->evaluateUnaryExpression($expr),
                'operation'           => $this->calcEvaluator->evaluate([$expr], $this->evaluate(...)),
                default               => throw new CompilationException(
                    "Unknown expression type: $type at line " . ($props['line'] ?? 0)
                ),
            };
        }

        if (is_string($expr) && str_starts_with($expr, '$')) {
            return $this->evaluateVariableString($expr);
        }

        if (is_string($expr) && str_contains($expr, ',') && ! str_contains($expr, '(')) {
            $parts = array_map(trim(...), explode(',', $expr));

            foreach ($parts as $i => $part) {
                $parts[$i] = $this->evaluate($part);
            }

            return $parts;
        }

        if (is_string($expr) && preg_match('/^(\d+\.?\d*)\s*(px|em|rem|%)?$/', $expr, $matches)) {
            $value = (float) $matches[1];
            $unit  = $matches[2] ?? '';

            return $unit === '' ? $value : ['value' => $value, 'unit' => $unit];
        }

        if (is_string($expr)) {
            return $this->interpolationEvaluator->evaluate($expr, $this->evaluate(...));
        }

        return $expr;
    }

    public function evaluateNumberExpression(AstNode $expr): mixed
    {
        $value = $expr->properties['value'];
        $unit  = $expr->properties['unit'] ?? '';

        if ($unit === '' && is_string($value)) {
            if (preg_match('/^(-?\d+(?:\.\d+)?)\s*(\S+)?$/', $value, $matches)) {
                $unit  = $matches[2] ?? '';
                $value = (float) $matches[1];
            }
        }

        return $unit === '' ? $value : ['value' => $value, 'unit' => $unit];
    }

    public function evaluateStringExpression(AstNode $expr): string
    {
        $value = $expr->properties['value'];
        $value = $this->interpolationEvaluator->evaluate($value, $this->evaluate(...));

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
            $result = $this->functionHandler->call('if', $args);

            return $this->evaluate($result);
        }

        if ($name === 'calc') {
            return $this->calcEvaluator->evaluate($args, $this->evaluate(...));
        }

        if ($this->hasSlashSeparator($args)) {
            $args = $this->evaluateArgumentsWithSlashSeparator($args);

            return $this->functionHandler->call($name, $args);
        }

        if ($name === 'url') {
            $args = $this->evaluateUrlArguments($args);

            return $this->functionHandler->call('url', $args);
        }

        $args = $this->evaluateArguments($args);

        return $this->functionHandler->call($name, $args);
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
            return $this->moduleHandler->getProperty($namespace, $propertyName, $this->evaluate(...));
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

        return $property . ': ' . $this->valueFormatter->format($value);
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
            default => $operator . $this->valueFormatter->format($operand),
        };
    }

    private function evaluateVariableString(string $expr): mixed
    {
        if (str_contains($expr, '.')) {
            [$namespace, $name] = explode('.', $expr, 2);
            $propertyName = '$' . $name;

            try {
                return $this->moduleHandler->getProperty($namespace, $propertyName, $this->evaluate(...));
            } catch (CompilationException) {
                try {
                    return $this->variableHandler->get($expr);
                } catch (CompilationException) {
                    throw new CompilationException("Undefined property: $propertyName in module $namespace");
                }
            }
        }

        return $this->variableHandler->get($expr);
    }

    private function evaluateArguments(array $args): array
    {
        foreach ($args as $key => $arg) {
            $args[$key] = $this->evaluate($arg);
        }

        return $args;
    }

    private function evaluateVariableNode(string $varName)
    {
        return $this->evaluate($this->variableHandler->get($varName));
    }
}
