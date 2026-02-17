<?php

declare(strict_types=1);

namespace DartSass\Compilers\Nodes;

use DartSass\Handlers\MixinHandler;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\MixinNode;

class MixinNodeCompiler extends AbstractNodeCompiler
{
    public function __construct(private readonly MixinHandler $mixinHandler) {}

    protected function getNodeClass(): string
    {
        return MixinNode::class;
    }

    protected function compileNode(
        MixinNode|AstNode $node,
        string $parentSelector = '',
        int $nestingLevel = 0
    ): string {
        $this->mixinHandler->define(
            $node->name,
            $node->args ?? [],
            $node->body ?? [],
            $nestingLevel === 0
        );

        return '';
    }
}
