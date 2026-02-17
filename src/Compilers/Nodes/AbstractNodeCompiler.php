<?php

declare(strict_types=1);

namespace DartSass\Compilers\Nodes;

use DartSass\Compilers\CompilerEngineInterface;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\NodeType;

abstract class AbstractNodeCompiler implements NodeCompiler
{
    public function canCompile(NodeType $nodeType): bool
    {
        return $nodeType === $this->getNodeType();
    }

    public function compile(
        AstNode $node,
        CompilerEngineInterface $engine,
        string $parentSelector = '',
        int $nestingLevel = 0
    ): string {
        if (! $node instanceof ($this->getNodeClass())) {
            return '';
        }

        return $this->compileNode($node, $engine, $parentSelector, $nestingLevel);
    }

    abstract protected function getNodeClass(): string;

    abstract protected function getNodeType(): NodeType;

    abstract protected function compileNode(
        AstNode $node,
        CompilerEngineInterface $engine,
        string $parentSelector = '',
        int $nestingLevel = 0
    ): string;
}
