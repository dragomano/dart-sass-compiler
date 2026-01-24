<?php

declare(strict_types=1);

namespace DartSass\Compilers\Strategies;

use DartSass\Compilers\CompilerContext;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\NodeType;

interface RuleCompilationStrategy
{
    public function canHandle(NodeType $ruleType): bool;

    public function compile(
        AstNode $node,
        CompilerContext $context,
        int $currentNestingLevel,
        string $parentSelector,
        ...$params
    ): string;
}
