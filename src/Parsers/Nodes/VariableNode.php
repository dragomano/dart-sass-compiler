<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class VariableNode extends AstNode
{
    public function __construct(public string $name, int $line = 0)
    {
        parent::__construct(NodeType::VARIABLE, $line);
    }
}
