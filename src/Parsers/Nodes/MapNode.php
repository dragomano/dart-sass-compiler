<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class MapNode extends AstNode
{
    public function __construct(public array $pairs, int $line = 0)
    {
        parent::__construct(NodeType::MAP, $line);
    }
}
