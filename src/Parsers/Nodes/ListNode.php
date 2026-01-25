<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class ListNode extends AstNode
{
    public function __construct(
        public array $values,
        public string $separator = 'comma',
        public bool $bracketed = false,
        int $line = 0
    ) {
        parent::__construct(NodeType::LIST, $line);
    }
}
