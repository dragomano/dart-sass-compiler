<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class UnaryNode extends AstNode
{
    public function __construct(public string $operator, public AstNode $operand, int $line = 0)
    {
        parent::__construct(NodeType::UNARY, $line);
    }
}
