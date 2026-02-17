<?php

declare(strict_types=1);

namespace DartSass\Compilers\Nodes;

use DartSass\Compilers\CompilerEngineInterface;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\ForwardNode;
use DartSass\Parsers\Nodes\NodeType;

class ForwardNodeCompiler extends AbstractNodeCompiler
{
    protected function getNodeClass(): string
    {
        return ForwardNode::class;
    }

    protected function getNodeType(): NodeType
    {
        return NodeType::FORWARD;
    }

    protected function compileNode(
        ForwardNode|AstNode $node,
        CompilerEngineInterface $engine,
        string $parentSelector = '',
        int $nestingLevel = 0
    ): string {
        $properties = $engine->getModuleHandler()->forwardModule(
            $node->path,
            fn($expr): mixed => $engine->evaluateExpression($expr),
            $node->namespace ?? null,
            $node->config ?? [],
            $node->hide ?? [],
            $node->show ?? []
        );

        foreach ($properties['variables'] as $varName => $varValue) {
            $engine->getVariableHandler()->define($varName, $varValue, true);
        }

        return '';
    }
}
