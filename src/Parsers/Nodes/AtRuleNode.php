<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class AtRuleNode extends AstNode
{
    public function __construct(
        public string $name,
        public string $value,
        public ?array $body,
        public int $line
    ) {
        parent::__construct('at-rule', [
            'name' => $name,
            'value' => $value,
            'body' => $body,
            'line' => $line,
        ]);
    }
}
