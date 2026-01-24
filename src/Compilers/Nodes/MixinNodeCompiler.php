<?php

declare(strict_types=1);

namespace DartSass\Compilers\Nodes;

use DartSass\Compilers\CompilerContext;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\MixinNode;
use DartSass\Parsers\Nodes\NodeType;

class MixinNodeCompiler extends AbstractNodeCompiler
{
    protected function getNodeClass(): string
    {
        return MixinNode::class;
    }

    protected function getNodeType(): NodeType
    {
        return NodeType::MIXIN;
    }

    protected function compileNode(
        MixinNode|AstNode $node,
        CompilerContext $context,
        string $parentSelector = '',
        int $nestingLevel = 0
    ): string {
        $context->mixinHandler->define(
            $node->name,
            $node->args ?? [],
            $node->body ?? []
        );

        return '';
    }
}
