<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class ListNode extends AstNode
{
    public function __construct(public array $values, public int $line)
    {
        parent::__construct('list', ['values' => $values, 'line' => $line]);
    }
}
