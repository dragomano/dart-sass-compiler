<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class NumberNode extends AstNode
{
    public function __construct(public float $value, public int $line = 0, public ?string $unit = null)
    {
        parent::__construct('number', ['value' => $value, 'line' => $line, 'unit' => $unit]);
    }
}
