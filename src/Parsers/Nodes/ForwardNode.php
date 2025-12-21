<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class ForwardNode extends AstNode
{
    public function __construct(
        public string $path,
        public ?string $namespace,
        public array $config,
        public array $hide,
        public array $show,
        public int $line
    ) {
        parent::__construct('forward', [
            'path'      => $path,
            'namespace' => $namespace,
            'config'    => $config,
            'hide'      => $hide,
            'show'      => $show,
            'line'      => $line,
        ]);
    }
}
