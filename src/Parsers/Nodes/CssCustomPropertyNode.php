<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class CssCustomPropertyNode extends AstNode
{
    public function __construct(public string $name, int $line = 0)
    {
        parent::__construct(NodeType::CSS_CUSTOM_PROPERTY, $line);
    }
}
