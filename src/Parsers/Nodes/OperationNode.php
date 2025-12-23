<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class OperationNode extends AstNode
{
    public function __construct(
        public AstNode $left,
        public string $operator,
        public AstNode $right,
        public int $line
    ) {
        parent::__construct('operation', [
            'left'     => $left,
            'operator' => $operator,
            'right'    => $right,
            'line'     => $line,
        ]);
    }
}
