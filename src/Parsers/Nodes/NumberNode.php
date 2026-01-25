<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class NumberNode extends AstNode
{
    public function __construct(public float $value, public ?string $unit = null, int $line = 0)
    {
        parent::__construct(NodeType::NUMBER, $line);
    }
}
