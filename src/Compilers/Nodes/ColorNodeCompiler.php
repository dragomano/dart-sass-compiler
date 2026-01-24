<?php

declare(strict_types=1);

namespace DartSass\Compilers\Nodes;

use DartSass\Compilers\CompilerContext;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\ColorNode;
use DartSass\Parsers\Nodes\NodeType;

class ColorNodeCompiler extends AbstractNodeCompiler
{
    protected function getNodeClass(): string
    {
        return ColorNode::class;
    }

    protected function getNodeType(): NodeType
    {
        return NodeType::COLOR;
    }

    protected function compileNode(
        AstNode $node,
        CompilerContext $context,
        string $parentSelector = '',
        int $nestingLevel = 0
    ): string {
        return (string) $node;
    }
}
