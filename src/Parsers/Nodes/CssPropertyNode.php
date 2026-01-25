<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class CssPropertyNode extends AstNode
{
    public function __construct(public string $property, public AstNode $value, int $line = 0)
    {
        parent::__construct(NodeType::CSS_PROPERTY, $line);
    }
}
