<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class CssPropertyNode extends AstNode
{
    public function __construct(
        public string $property,
        public AstNode $value,
        public int $line
    ) {
        parent::__construct('css_property', [
            'property' => $this->property,
            'value'    => $value,
            'line'     => $line,
        ]);
    }
}
