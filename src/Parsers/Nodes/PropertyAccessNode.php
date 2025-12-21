<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class PropertyAccessNode extends AstNode
{
    public function __construct(
        public AstNode $namespace,
        public AstNode $property,
        public int $line
    ) {
        parent::__construct('property_access', [
            'namespace' => $this->namespace,
            'property'  => $this->property,
            'line'      => $line,
        ]);
    }
}
