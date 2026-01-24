<?php

declare(strict_types=1);

namespace DartSass\Compilers\Strategies;

use DartSass\Parsers\Nodes\NodeType;

readonly class MediaRuleStrategy extends ConditionalRuleStrategy
{
    protected function getRuleName(): NodeType
    {
        return NodeType::MEDIA;
    }

    protected function getAtSymbol(): string
    {
        return '@media';
    }
}
