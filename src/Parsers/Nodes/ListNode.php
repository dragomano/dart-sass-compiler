<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class ListNode extends AstNode
{
    public function __construct(
        public array $values,
        public ?int $line = 0,
        public string $separator = 'comma',
        public bool $bracketed = false
    ) {
        $line ??= 0;

        parent::__construct('list', [
            'values'    => $values,
            'line'      => $line,
            'separator' => $separator,
            'bracketed' => $bracketed,
        ]);
    }
}
