<?php

declare(strict_types=1);

namespace DartSass\Compilers;

use Closure;
use DartSass\Exceptions\CompilationException;
use DartSass\Handlers\VariableHandler;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\EachNode;
use DartSass\Parsers\Nodes\ForNode;
use DartSass\Parsers\Nodes\IfNode;
use DartSass\Parsers\Nodes\WhileNode;

use function count;
use function explode;
use function is_array;
use function is_object;
use function is_string;
use function key;
use function property_exists;
use function str_contains;

readonly class FlowControlCompiler
{
    public function __construct(private VariableHandler $variableHandler) {}

    public function compile(
        AstNode $node,
        int $nestingLevel,
        Closure $evaluateExpression,
        Closure $compileAst
    ): string {
        return match (true) {
            $node instanceof IfNode    => $this->compileIf($node, $nestingLevel, $evaluateExpression, $compileAst),
            $node instanceof EachNode  => $this->compileEach($node, $nestingLevel, $evaluateExpression, $compileAst),
            $node instanceof ForNode   => $this->compileFor($node, $nestingLevel, $evaluateExpression, $compileAst),
            $node instanceof WhileNode => $this->compileWhile($node, $nestingLevel, $evaluateExpression, $compileAst),
            default => throw new CompilationException('Unknown control flow node type: ' . $node::class),
        };
    }

    private function compileIf(
        IfNode $node,
        int $nestingLevel,
        Closure $evaluateExpression,
        Closure $compileAst
    ): string {
        $condition = $evaluateExpression($node->condition);

        if ($this->isTruthy($condition)) {
            return $compileAst($node->body, '', $nestingLevel + 1);
        } elseif (is_array($node->else) && count($node->else) > 0) {
            return $compileAst($node->else, '', $nestingLevel + 1);
        }

        return '';
    }

    private function compileEach(
        EachNode $node,
        int $nestingLevel,
        Closure $evaluateExpression,
        Closure $compileAst
    ): string {
        $list = $evaluateExpression($node->condition);

        if (is_object($list) && property_exists($list, 'value')) {
            $list = $list->value;
        }

        if (! is_array($list)) {
            $list = [$list];
        }

        $variables = $node->variables ?? throw new CompilationException('Missing variables for @each');

        $css = '';
        $this->variableHandler->enterScope();

        $numVars = count($variables);

        if ($numVars === 1) {
            $varName = $variables[0];
            foreach ($list as $value) {
                $this->variableHandler->define($varName, $value);
                $css .= $compileAst($node->body, '', $nestingLevel);
            }
        } else {
            // For multiple variables, assume map or list of lists
            foreach ($list as $key => $value) {
                if ($numVars === 2) {
                    if (is_array($value) && count($value) === 1) {
                        // Map entry: key => value as array
                        $entryKey = key($value);
                        $entryValue = $value[$entryKey];

                        $this->variableHandler->define($variables[0], $entryKey);
                        $this->variableHandler->define($variables[1], $entryValue);
                    } elseif (is_array($value) && count($value) === 2) {
                        // List of pairs
                        [$val1, $val2] = $value;

                        $this->variableHandler->define($variables[0], $val1);
                        $this->variableHandler->define($variables[1], $val2);
                    } else {
                        // Map: key => value, or string 'key: value'
                        if (is_string($value) && str_contains($value, ':')) {
                            [$entryKey, $entryValue] = explode(': ', $value, 2);

                            $this->variableHandler->define($variables[0], $entryKey);
                            $this->variableHandler->define($variables[1], $entryValue);
                        } else {
                            $this->variableHandler->define($variables[0], $key);
                            $this->variableHandler->define($variables[1], $value);
                        }
                    }
                } elseif ($numVars > 2) {
                    throw new CompilationException('Multiple variables in @each with more than 2 not supported');
                }

                $css .= $compileAst($node->body, '', $nestingLevel);
            }
        }

        $this->variableHandler->exitScope();

        return $css;
    }

    private function compileFor(
        ForNode $node,
        int $nestingLevel,
        Closure $evaluateExpression,
        Closure $compileAst
    ): string {
        $from = (int) $evaluateExpression($node->from);
        $to   = (int) $evaluateExpression($node->to);

        $varName   = $node->variable ?? throw new CompilationException('Missing variable name for @for');
        $inclusive = $node->inclusive ?? false;

        $css = '';

        $this->variableHandler->enterScope();

        $end  = $inclusive ? $to : $to - 1;
        $step = $from <= $end ? 1 : -1;

        if ($step > 0) {
            for ($i = $from; $i <= $end; $i += $step) {
                $this->variableHandler->define($varName, $i);
                $css .= $compileAst($node->body, '', $nestingLevel);
            }
        } else {
            for ($i = $from; $i >= $end; $i += $step) {
                $this->variableHandler->define($varName, $i);
                $css .= $compileAst($node->body, '', $nestingLevel);
            }
        }

        $this->variableHandler->exitScope();

        return $css;
    }

    private function compileWhile(
        WhileNode $node,
        int $nestingLevel,
        Closure $evaluateExpression,
        Closure $compileAst
    ): string {
        $css = '';

        $maxIterations = 1000;
        $iteration = 0;

        $this->variableHandler->enterScope();

        while ($iteration < $maxIterations) {
            $condition = $evaluateExpression($node->condition);
            if (! $this->isTruthy($condition)) {
                break;
            }

            $css .= $compileAst($node->body, '', $nestingLevel);
            $iteration++;
        }

        $this->variableHandler->exitScope();

        if ($iteration >= $maxIterations) {
            throw new CompilationException('Maximum @while iterations exceeded (1000)');
        }

        return $css;
    }

    private function isTruthy(mixed $value): bool
    {
        return $value !== false && $value !== null;
    }
}
