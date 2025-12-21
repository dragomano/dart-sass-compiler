<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class CssCustomPropertyNode extends AstNode
{
    public function __construct(public string $name, public int $line)
    {
        parent::__construct('css_custom_property', ['name' => $name, 'line' => $line]);
    }
}
