<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class EachNode extends AstNode
{
    public function __construct(
        public string $variable,
        public AstNode $condition,
        public array $body,
        public int $line
    ) {
        parent::__construct('each', [
            'variable'  => $variable,
            'condition' => $condition,
            'body'      => $body,
            'line'      => $line,
        ]);
    }
}
