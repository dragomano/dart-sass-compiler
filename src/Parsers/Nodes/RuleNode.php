<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class RuleNode extends AstNode
{
    public function __construct(
        public AstNode $selector,
        public array $declarations,
        public array $nested,
        public int $line,
        public int $column = 0
    ) {
        parent::__construct('rule', [
            'selector'     => $selector,
            'declarations' => $declarations,
            'nested'       => $nested,
            'line'         => $line,
            'column'       => $column,
        ]);
    }
}
