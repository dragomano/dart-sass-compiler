<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class PropertyAccessNode extends AstNode
{
    public function __construct(public AstNode $namespace, public AstNode $property, int $line = 0)
    {
        parent::__construct(NodeType::PROPERTY_ACCESS, $line);
    }
}
