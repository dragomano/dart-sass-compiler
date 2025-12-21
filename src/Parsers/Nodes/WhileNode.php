<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class WhileNode extends AstNode
{
    public function __construct(
        public AstNode $condition,
        public array $body,
        public int $line
    ) {
        parent::__construct('while', [
            'condition' => $this->condition,
            'body'      => $body,
            'line'      => $line,
        ]);
    }
}
