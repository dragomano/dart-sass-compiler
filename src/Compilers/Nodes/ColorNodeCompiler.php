<?php

declare(strict_types=1);

namespace DartSass\Compilers\Nodes;

use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\ColorNode;

class ColorNodeCompiler extends AbstractNodeCompiler
{
    protected function getNodeClass(): string
    {
        return ColorNode::class;
    }

    protected function compileNode(
        AstNode $node,
        string $parentSelector = '',
        int $nestingLevel = 0
    ): string {
        return (string) $node;
    }
}
