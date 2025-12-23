<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class SelectorNode extends AstNode
{
    public function __construct(public string $value, public int $line)
    {
        parent::__construct('selector', ['value' => $value, 'line'  => $line]);
    }
}
