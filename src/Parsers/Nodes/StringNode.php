<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class StringNode extends AstNode
{
    public function __construct(public string $value, int $line = 0)
    {
        parent::__construct(NodeType::STRING, $line);
    }
}
