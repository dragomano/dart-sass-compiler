<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class UseNode extends AstNode
{
    public function __construct(
        public string $path,
        public ?string $namespace,
        public int $line
    ) {
        parent::__construct('use', ['path' => $path, 'namespace' => $namespace, 'line' => $line]);
    }
}
