<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class MixinNode extends AstNode
{
    public function __construct(
        public string $name,
        public array $args,
        public ?array $body = null,
        int $line = 0
    ) {
        parent::__construct(NodeType::MIXIN, $line);
    }
}
