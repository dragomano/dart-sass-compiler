<?php

declare(strict_types=1);

namespace DartSass\Compilers\Nodes;

use DartSass\Compilers\CompilerContext;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\VariableDeclarationNode;

class VariableNodeCompiler extends AbstractNodeCompiler
{
    protected function getNodeClass(): string
    {
        return VariableDeclarationNode::class;
    }

    protected function getNodeType(): string
    {
        return 'variable';
    }

    protected function compileNode(
        AstNode $node,
        CompilerContext $context,
        string $parentSelector = '',
        int $nestingLevel = 0
    ): string {
        $valueNode = $node->properties['value'];

        $value = $context->engine->evaluateExpression($valueNode);

        $context->variableHandler->define(
            $node->properties['name'],
            $value,
            $node->global ?? false,
            $node->default ?? false,
        );

        return '';
    }
}
