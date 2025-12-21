<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class ForNode extends AstNode
{
    public function __construct(
        public string $variable,
        public AstNode $from,
        public AstNode $to,
        public bool $inclusive,
        public array $body,
        public int $line
    ) {
        parent::__construct('for', [
            'variable'  => $variable,
            'from'      => $from,
            'to'        => $to,
            'inclusive' => $inclusive,
            'body'      => $body,
            'line'      => $line,
        ]);
    }
}
