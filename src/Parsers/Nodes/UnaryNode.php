<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class UnaryNode extends AstNode
{
    public function __construct(
        public string $operator,
        public AstNode $operand,
        public int $line
    ) {
        parent::__construct('unary', [
            'operator' => $this->operator,
            'operand'  => $this->operand,
            'line'     => $line,
        ]);
    }
}
