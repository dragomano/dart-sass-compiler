<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class VariableNode extends AstNode
{
    public function __construct(public string $name, public int $line)
    {
        parent::__construct('variable', ['name' => $name, 'line' => $line]);
    }
}
