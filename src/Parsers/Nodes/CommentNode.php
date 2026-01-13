<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class CommentNode extends AstNode
{
    public function __construct(public string $value, int $line, int $column = 0)
    {
        parent::__construct('comment', [
            'value'  => $value,
            'line'   => $line,
            'column' => $column,
        ]);
    }
}
