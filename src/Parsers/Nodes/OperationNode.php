<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class OperationNode extends AstNode
{
    public function __construct(
        public AstNode $left,
        public string $operator,
        public AstNode $right,
        int $line = 0
    ) {
        parent::__construct(NodeType::OPERATION, $line);
    }
}
