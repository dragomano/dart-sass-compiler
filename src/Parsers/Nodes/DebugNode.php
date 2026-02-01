<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class DebugNode extends AstNode
{
    public function __construct(public mixed $expression, int $line = 0)
    {
        parent::__construct(NodeType::DEBUG, $line);
    }
}
