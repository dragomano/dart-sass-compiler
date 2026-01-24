<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class AtRuleNode extends AstNode
{
    public function __construct(
        public string $name,
        public string $value,
        public ?array $body,
        int $line = 0
    ) {
        parent::__construct(NodeType::AT_RULE, $line);
    }
}
