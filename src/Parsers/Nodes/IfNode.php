<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class IfNode extends AstNode
{
    public function __construct(
        public AstNode $condition,
        public array $body,
        public ?array $else = null,
        public int $line = 0
    ) {
        parent::__construct('if', [
            'condition' => $condition,
            'body'      => $body,
            'else'      => $else,
            'line'      => $line,
        ]);
    }
}
