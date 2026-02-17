<?php

declare(strict_types=1);

namespace DartSass\Compilers;

use DartSass\Compilers\Nodes\NodeCompiler;
use DartSass\Parsers\Nodes\NodeType;

final class NodeCompilerRegistry
{
    /** @var array<string, NodeCompiler> */
    private array $compilers = [];

    public function register(NodeType $nodeType, NodeCompiler $compiler): void
    {
        $this->compilers[$nodeType->value] = $compiler;
    }

    public function find(NodeType $nodeType): ?NodeCompiler
    {
        return $this->compilers[$nodeType->value] ?? null;
    }
}
