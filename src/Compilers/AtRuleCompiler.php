<?php

declare(strict_types=1);

namespace DartSass\Compilers;

use Closure;
use DartSass\Parsers\Nodes\AstNode;

readonly class AtRuleCompiler
{
    public function __construct(private CompilerContext $context) {}

    public function compile(
        AstNode $node,
        string $parentSelector,
        int $nestingLevel,
        Closure $expression,
        Closure $compileDeclarations,
        Closure $compileAst,
        Closure $evaluateInterpolationsInString
    ): string {
        $css = $this->context->ruleCompiler->compileRule(
            $node,
            $this->context,
            $parentSelector,
            $nestingLevel,
            $evaluateInterpolationsInString,
            $compileDeclarations,
            $compileAst,
            $expression
        );

        $this->context->positionTracker->updatePosition($css);

        return $css;
    }
}
