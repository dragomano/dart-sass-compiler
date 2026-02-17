<?php

declare(strict_types=1);

namespace DartSass\Compilers\Nodes;

use Closure;
use DartSass\Handlers\VariableHandler;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\VariableDeclarationNode;

class VariableNodeCompiler extends AbstractNodeCompiler
{
    public function __construct(
        private readonly VariableHandler $variableHandler,
        private readonly Closure $evaluateExpression
    ) {}

    protected function getNodeClass(): string
    {
        return VariableDeclarationNode::class;
    }

    protected function compileNode(
        VariableDeclarationNode|AstNode $node,
        string $parentSelector = '',
        int $nestingLevel = 0
    ): string {
        $valueNode = $node->value;

        $value = ($this->evaluateExpression)($valueNode);

        $this->variableHandler->define(
            $node->name,
            $value,
            $node->global ?? false,
            $node->default ?? false,
        );

        return '';
    }
}
