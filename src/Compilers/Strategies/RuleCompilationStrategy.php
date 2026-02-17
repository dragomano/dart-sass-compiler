<?php

declare(strict_types=1);

namespace DartSass\Compilers\Strategies;

use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\NodeType;

interface RuleCompilationStrategy
{
    public function canHandle(NodeType $ruleType): bool;

    public function compile(
        AstNode $node,
        string $parentSelector,
        int $currentNestingLevel,
        ...$params
    ): string;
}
