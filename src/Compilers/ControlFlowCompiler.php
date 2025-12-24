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
use function is_array;

readonly class ControlFlowCompiler
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

        if (! is_array($list)) {
            $list = [$list];
        }

        $varName = $node->variable ?? throw new CompilationException('Missing variable name for @each');

        $css = '';
        $this->variableHandler->enterScope();

        foreach ($list as $value) {
            $this->variableHandler->define($varName, $value);
            $css .= $compileAst($node->body, '', $nestingLevel);
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
