<?php

declare(strict_types=1);

namespace DartSass\Compilers\Nodes;

use DartSass\Compilers\CompilerContext;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\FunctionNode;

class FunctionNodeCompiler extends AbstractNodeCompiler
{
    protected function getNodeClass(): string
    {
        return FunctionNode::class;
    }

    protected function getNodeType(): string
    {
        return 'function';
    }

    protected function compileNode(
        AstNode $node,
        CompilerContext $context,
        string $parentSelector = '',
        int $nestingLevel = 0
    ): string {
        $context->functionHandler->defineUserFunction(
            $node->properties['name'],
            $node->args ?? [],
            $node->body ?? [],
            $context->variableHandler,
        );

        return '';
    }
}
