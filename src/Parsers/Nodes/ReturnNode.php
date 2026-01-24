<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class ReturnNode extends AstNode
{
    public function __construct(public AstNode $value, int $line = 0)
    {
        parent::__construct(NodeType::RETURN, $line);
    }
}
