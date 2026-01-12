<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class MapNode extends AstNode
{
    public function __construct(
        public array $pairs,
        public ?int $line = 0
    ) {
        $line ??= 0;

        parent::__construct('map', [
            'pairs' => $pairs,
            'line'  => $line,
        ]);
    }
}
