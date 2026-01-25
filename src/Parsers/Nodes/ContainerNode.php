<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class ContainerNode extends AstNode
{
    public function __construct(public string $query, public array $body, int $line = 0)
    {
        parent::__construct(NodeType::CONTAINER, $line);
    }
}
