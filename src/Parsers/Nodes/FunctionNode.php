<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class FunctionNode extends AstNode
{
    public function __construct(
        public string $name,
        public array $args,
        public ?array $body = null,
        public int $line = 0
    ) {
        $properties = [
            'name' => $name,
            'args' => $args,
            'line' => $line,
        ];

        if ($body !== null) {
            $properties['body'] = $body;
        }

        parent::__construct('function', $properties);
    }
}
