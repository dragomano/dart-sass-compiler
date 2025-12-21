<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class ConditionNode extends AstNode
{
    public function __construct(public AstNode $expression, public int $line)
    {
        parent::__construct('condition', ['expression' => $expression, 'line' => $line]);
    }
}
