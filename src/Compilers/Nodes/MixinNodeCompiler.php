<?php

declare(strict_types=1);

namespace DartSass\Compilers\Nodes;

use DartSass\Compilers\CompilerEngineInterface;
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
        CompilerEngineInterface $engine,
        string $parentSelector = '',
        int $nestingLevel = 0
    ): string {
        $engine->getMixinHandler()->define(
            $node->name,
            $node->args ?? [],
            $node->body ?? [],
            $nestingLevel === 0
        );

        return '';
    }
}
