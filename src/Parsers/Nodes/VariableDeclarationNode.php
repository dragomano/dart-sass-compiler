<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class VariableDeclarationNode extends AstNode
{
    public function __construct(
        public string $name,
        public AstNode $value,
        public bool $global = false,
        public bool $default = false,
        int $line = 0
    ) {
        parent::__construct(NodeType::VARIABLE, $line);
    }
}
