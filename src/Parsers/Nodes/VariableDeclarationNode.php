<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class VariableDeclarationNode extends AstNode
{
    public function __construct(
        public string $name,
        public AstNode $value,
        public int $line,
        public bool $global = false,
        public bool $default = false,
        public ?int $indent = null
    ) {
        $properties = [
            'name'    => $name,
            'value'   => $value,
            'line'    => $line,
            'global'  => $global,
            'default' => $default,
        ];

        if ($indent !== null) {
            $properties['indent'] = $indent;
        }

        parent::__construct('variable', $properties);
    }
}
