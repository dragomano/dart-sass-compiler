<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class InterpolationNode extends AstNode
{
    public function __construct(public AstNode $expression, public int $line)
    {
        parent::__construct('interpolation', ['expression' => $this->expression, 'line' => $line]);
    }
}
