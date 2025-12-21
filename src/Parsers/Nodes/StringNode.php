<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class StringNode extends AstNode
{
    public function __construct(public string $value, public int $line)
    {
        parent::__construct('string', ['value' => $value, 'line' => $line]);
    }
}
