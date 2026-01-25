<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class IfNode extends AstNode
{
    public function __construct(
        public AstNode $condition,
        public array $body,
        public ?array $else = null,
        int $line = 0
    ) {
        parent::__construct(NodeType::IF, $line);
    }
}
