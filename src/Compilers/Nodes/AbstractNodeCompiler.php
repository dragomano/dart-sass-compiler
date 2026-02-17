<?php

declare(strict_types=1);

namespace DartSass\Compilers\Nodes;

use DartSass\Parsers\Nodes\AstNode;

abstract class AbstractNodeCompiler implements NodeCompiler
{
    public function compile(AstNode $node, string $parentSelector = '', int $nestingLevel = 0): string
    {
        if (! $node instanceof ($this->getNodeClass())) {
            return '';
        }

        return $this->compileNode($node, $parentSelector, $nestingLevel);
    }

    abstract protected function getNodeClass(): string;

    abstract protected function compileNode(
        AstNode $node,
        string $parentSelector = '',
        int $nestingLevel = 0
    ): string;
}
