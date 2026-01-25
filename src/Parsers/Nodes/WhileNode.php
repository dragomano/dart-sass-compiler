<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class WhileNode extends AstNode
{
    public function __construct(public AstNode $condition, public array $body, int $line = 0)
    {
        parent::__construct(NodeType::WHILE, $line);
    }
}
