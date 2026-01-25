<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

use Stringable;

final class HexColorNode extends AstNode implements Stringable
{
    public function __construct(public string $value, int $line = 0, int $column = 0)
    {
        parent::__construct(NodeType::HEX_COLOR, $line, $column);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
