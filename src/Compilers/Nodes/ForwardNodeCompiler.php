<?php

declare(strict_types=1);

namespace DartSass\Compilers\Nodes;

use Closure;
use DartSass\Handlers\ModuleHandler;
use DartSass\Handlers\VariableHandler;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\ForwardNode;

class ForwardNodeCompiler extends AbstractNodeCompiler
{
    public function __construct(
        private readonly ModuleHandler $moduleHandler,
        private readonly VariableHandler $variableHandler,
        private readonly Closure $evaluateExpression
    ) {}

    protected function getNodeClass(): string
    {
        return ForwardNode::class;
    }

    protected function compileNode(
        ForwardNode|AstNode $node,
        string $parentSelector = '',
        int $nestingLevel = 0
    ): string {
        $properties = $this->moduleHandler->forwardModule(
            $node->path,
            $this->evaluateExpression,
            $node->namespace ?? null,
            $node->config ?? [],
            $node->hide ?? [],
            $node->show ?? []
        );

        foreach ($properties['variables'] as $varName => $varValue) {
            $this->variableHandler->define($varName, $varValue, true);
        }

        return '';
    }
}
