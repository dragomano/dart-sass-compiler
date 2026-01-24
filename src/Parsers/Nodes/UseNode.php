<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class UseNode extends AstNode
{
    public function __construct(public string $path, public ?string $namespace, int $line = 0)
    {
        parent::__construct(NodeType::USE, $line);
    }
}
