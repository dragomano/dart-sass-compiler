<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class EachNode extends AstNode
{
    public function __construct(
        public array $variables,
        public AstNode $condition,
        public array $body,
        public int $line
    ) {
        parent::__construct('each', [
            'variables' => $variables,
            'condition' => $condition,
            'body'      => $body,
            'line'      => $line,
        ]);
    }
}
