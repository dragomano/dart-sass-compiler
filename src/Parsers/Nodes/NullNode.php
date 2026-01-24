<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class NullNode extends AstNode
{
    public function __construct(int $line = 0)
    {
        parent::__construct(NodeType::NULL, $line);
    }
}
