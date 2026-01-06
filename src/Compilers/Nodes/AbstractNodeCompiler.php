<?php

declare(strict_types=1);

namespace DartSass\Compilers\Nodes;

use DartSass\Compilers\CompilerContext;
use DartSass\Parsers\Nodes\AstNode;

abstract class AbstractNodeCompiler implements NodeCompiler
{
    public function canCompile(string $nodeType): bool
    {
        return $nodeType === $this->getNodeType();
    }

    public function compile(
        AstNode $node,
        CompilerContext $context,
        string $parentSelector = '',
        int $nestingLevel = 0
    ): string {
        if (! $node instanceof ($this->getNodeClass())) {
            return '';
        }

        return $this->compileNode($node, $context, $parentSelector, $nestingLevel);
    }

    abstract protected function getNodeClass(): string;

    abstract protected function getNodeType(): string;

    abstract protected function compileNode(
        AstNode $node,
        CompilerContext $context,
        string $parentSelector = '',
        int $nestingLevel = 0
    ): string;
}
