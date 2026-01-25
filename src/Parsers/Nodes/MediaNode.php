<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class MediaNode extends AstNode
{
    public function __construct(public string $query, public array $body, int $line = 0)
    {
        parent::__construct(NodeType::MEDIA, $line);
    }
}
