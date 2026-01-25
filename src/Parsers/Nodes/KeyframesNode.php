<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class KeyframesNode extends AstNode
{
    public function __construct(public string $name, public array $keyframes, int $line = 0)
    {
        parent::__construct(NodeType::KEYFRAMES, $line);
    }
}
