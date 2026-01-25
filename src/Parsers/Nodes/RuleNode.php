<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class RuleNode extends AstNode
{
    public function __construct(
        public AstNode $selector,
        public array $declarations,
        public array $nested,
        int $line = 0,
        int $column = 0
    ) {
        parent::__construct(NodeType::RULE, $line, $column);
    }
}
