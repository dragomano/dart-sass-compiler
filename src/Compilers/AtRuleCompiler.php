<?php

declare(strict_types=1);

namespace DartSass\Compilers;

use Closure;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Utils\PositionTracker;

readonly class AtRuleCompiler
{
    public function __construct(private RuleCompiler $ruleCompiler, private PositionTracker $positionTracker) {}

    public function compile(
        CompilerContext $context,
        AstNode $node,
        int $nestingLevel,
        string $parentSelector,
        Closure $evaluateExpression,
        Closure $compileDeclarations,
        Closure $compileAst,
        Closure $evaluateInterpolationsInString
    ): string {
        $css = $this->ruleCompiler->compileRule(
            $node,
            $context,
            $nestingLevel,
            $parentSelector,
            $evaluateInterpolationsInString,
            $compileDeclarations,
            $compileAst,
            $evaluateExpression
        );

        $this->positionTracker->updatePosition($css);

        return $css;
    }
}
