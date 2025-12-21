<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class IdentifierNode extends AstNode
{
    public function __construct(public string $value, public int $line)
    {
        parent::__construct('identifier', ['value' => $value, 'line' => $line]);
    }
}
