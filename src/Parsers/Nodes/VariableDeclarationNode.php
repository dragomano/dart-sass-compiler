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
        public bool $default = false
    ) {
        parent::__construct('variable', [
            'name'    => $name,
            'value'   => $value,
            'line'    => $line,
            'global'  => $global,
            'default' => $default,
        ]);
    }
}
