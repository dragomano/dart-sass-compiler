<?php

declare(strict_types=1);

namespace DartSass\Compilers;

use Closure;
use DartSass\Exceptions\CompilationException;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Utils\PositionTracker;

readonly class AtRuleCompiler
{
    public function __construct(private RuleCompiler $ruleCompiler, private PositionTracker $positionTracker) {}

    public function compile(
        AstNode $node,
        int $nestingLevel,
        string $parentSelector,
        Closure $evaluateExpression,
        Closure $compileDeclarations,
        Closure $compileAst,
        Closure $evaluateInterpolationsInString
    ): string {
        $css = match ($node->type) {
            'media' => $this->ruleCompiler->compileMediaRule(
                $node,
                $nestingLevel,
                $parentSelector,
                $evaluateInterpolationsInString,
                $compileDeclarations,
                $compileAst
            ),
            'container' => $this->ruleCompiler->compileContainerRule(
                $node,
                $nestingLevel,
                $parentSelector,
                $evaluateInterpolationsInString,
                $compileDeclarations,
                $compileAst
            ),
            'keyframes' => $this->ruleCompiler->compileKeyframesRule(
                $node,
                $nestingLevel,
                $evaluateExpression
            ),
            'at-rule' => $this->ruleCompiler->compileAtRule(
                $node,
                $nestingLevel,
                $evaluateExpression,
                $compileDeclarations,
                $compileAst
            ),
            default => throw new CompilationException("Unknown At-Rule type: $node->type"),
        };

        $this->positionTracker->updatePosition($css);

        return $css;
    }
}
