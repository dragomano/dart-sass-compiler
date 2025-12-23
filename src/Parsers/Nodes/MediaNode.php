<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class MediaNode extends AstNode
{
    public function __construct(
        public string $query,
        public array $body,
        public int $line
    ) {
        parent::__construct('media', [
            'query' => $query,
            'body'  => $body,
            'line'  => $line,
        ]);
    }
}
