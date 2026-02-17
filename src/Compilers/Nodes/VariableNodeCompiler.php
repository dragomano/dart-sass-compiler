<?php

declare(strict_types=1);

namespace DartSass\Compilers\Nodes;

use DartSass\Compilers\CompilerEngineInterface;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\NodeType;
use DartSass\Parsers\Nodes\VariableDeclarationNode;

class VariableNodeCompiler extends AbstractNodeCompiler
{
    protected function getNodeClass(): string
    {
        return VariableDeclarationNode::class;
    }

    protected function getNodeType(): NodeType
    {
        return NodeType::VARIABLE;
    }

    protected function compileNode(
        VariableDeclarationNode|AstNode $node,
        CompilerEngineInterface $engine,
        string $parentSelector = '',
        int $nestingLevel = 0
    ): string {
        $valueNode = $node->value;

        $value = $engine->evaluateExpression($valueNode);

        $engine->getVariableHandler()->define(
            $node->name,
            $value,
            $node->global ?? false,
            $node->default ?? false,
        );

        return '';
    }
}
