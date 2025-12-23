<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

class AstNode
{
    public function __construct(public string $type, public array $properties = [])
    {
    }
}
