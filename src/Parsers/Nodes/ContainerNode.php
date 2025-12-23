<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class ContainerNode extends AstNode
{
    public function __construct(
        public string $query,
        public array $body,
        public int $line
    ) {
        parent::__construct('container', [
            'query' => $query,
            'body'  => $body,
            'line'  => $line,
        ]);
    }
}
