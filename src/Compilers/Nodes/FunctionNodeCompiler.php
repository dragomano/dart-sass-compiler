<?php

declare(strict_types=1);

namespace DartSass\Compilers\Nodes;

use DartSass\Handlers\FunctionHandler;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\FunctionNode;

class FunctionNodeCompiler extends AbstractNodeCompiler
{
    public function __construct(private readonly FunctionHandler $functionHandler) {}

    protected function getNodeClass(): string
    {
        return FunctionNode::class;
    }

    protected function compileNode(
        FunctionNode|AstNode $node,
        string $parentSelector = '',
        int $nestingLevel = 0
    ): string {
        $this->functionHandler->defineUserFunction(
            $node->name,
            $node->args ?? [],
            $node->body ?? []
        );

        return '';
    }
}
