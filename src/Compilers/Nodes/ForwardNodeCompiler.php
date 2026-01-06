<?php

declare(strict_types=1);

namespace DartSass\Compilers\Nodes;

use DartSass\Compilers\CompilerContext;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\ForwardNode;

class ForwardNodeCompiler extends AbstractNodeCompiler
{
    protected function getNodeClass(): string
    {
        return ForwardNode::class;
    }

    protected function getNodeType(): string
    {
        return 'forward';
    }

    protected function compileNode(
        AstNode $node,
        CompilerContext $context,
        string $parentSelector = '',
        int $nestingLevel = 0
    ): string {
        $properties = $context->moduleHandler->forwardModule(
            $node->properties['path'],
            fn($expr): mixed => $context->engine->evaluateExpression($expr),
            $node->namespace ?? null,
            $node->config ?? [],
            $node->hide ?? [],
            $node->show ?? []
        );

        foreach ($properties['variables'] as $varName => $varValue) {
            $context->variableHandler->define($varName, $varValue, true);
        }

        return '';
    }
}
