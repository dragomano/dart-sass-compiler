<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class SupportsNode extends AstNode
{
    public function __construct(
        public string $query,
        public array $body,
        int $line,
        int $column = 0,
    ) {
        parent::__construct(NodeType::SUPPORTS, $line, $column);
    }
}
