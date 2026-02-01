<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class ErrorNode extends AstNode
{
    public function __construct(public mixed $expression, int $line = 0)
    {
        parent::__construct(NodeType::ERROR, $line);
    }
}
