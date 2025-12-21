<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class KeyframesNode extends AstNode
{
    public function __construct(
        public string $name,
        public array $keyframes,
        public int $line
    ) {
        parent::__construct('keyframes', [
            'name'      => $name,
            'keyframes' => $keyframes,
            'line'      => $line,
        ]);
    }
}
