<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class ForwardNode extends AstNode
{
    public function __construct(
        public string $path,
        public ?string $namespace,
        public array $config,
        public array $hide,
        public array $show,
        int $line = 0
    ) {
        parent::__construct(NodeType::FORWARD, $line);
    }
}
