<?php

declare(strict_types=1);

namespace DartSass\Compilers\Nodes;

use DartSass\Compilers\CompilerContext;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\FunctionNode;
use DartSass\Parsers\Nodes\NodeType;

class FunctionNodeCompiler extends AbstractNodeCompiler
{
    protected function getNodeClass(): string
    {
        return FunctionNode::class;
    }

    protected function getNodeType(): NodeType
    {
        return NodeType::FUNCTION;
    }

    protected function compileNode(
        FunctionNode|AstNode $node,
        CompilerContext $context,
        string $parentSelector = '',
        int $nestingLevel = 0
    ): string {
        $context->functionHandler->defineUserFunction(
            $node->name,
            $node->args ?? [],
            $node->body ?? [],
            $context->variableHandler,
        );

        return '';
    }
}
