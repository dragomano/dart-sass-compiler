<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class HexColorNode extends AstNode
{
    public function __construct(public string $value, public int $line, public int $column = 0)
    {
        parent::__construct('hex_color', ['value' => $value, 'line' => $line, 'column' => $column]);
    }
}
