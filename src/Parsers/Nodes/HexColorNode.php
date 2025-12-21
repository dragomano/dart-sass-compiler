<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class HexColorNode extends AstNode
{
    public function __construct(public string $value, public int $line)
    {
        parent::__construct('hex_color', ['value' => $value, 'line' => $line]);
    }
}
