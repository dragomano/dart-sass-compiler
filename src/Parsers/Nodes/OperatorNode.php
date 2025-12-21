<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class OperatorNode extends AstNode
{
    public function __construct(public string $value, public int $line)
    {
        parent::__construct('operator', ['value' => $value, 'line' => $line]);
    }
}
