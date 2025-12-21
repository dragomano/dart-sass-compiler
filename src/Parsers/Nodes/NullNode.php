<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class NullNode extends AstNode
{
    public function __construct(public int $line)
    {
        parent::__construct('null', ['line' => $line]);
    }
}
