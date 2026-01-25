<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class ConditionNode extends AstNode
{
    public function __construct(public AstNode $expression, int $line = 0)
    {
        parent::__construct(NodeType::CONDITION, $line);
    }
}
