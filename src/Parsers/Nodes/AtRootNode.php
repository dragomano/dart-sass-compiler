<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class AtRootNode extends AstNode
{
    public function __construct(
        public ?string $without,
        public ?string $with,
        public array $body,
        int $line = 0
    ) {
        parent::__construct(NodeType::AT_ROOT, $line);
    }
}
