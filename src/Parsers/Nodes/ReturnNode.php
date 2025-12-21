<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class ReturnNode extends AstNode
{
    public function __construct(public AstNode $value, public int $line)
    {
        parent::__construct('return', ['value' => $value, 'line' => $line]);
    }
}
