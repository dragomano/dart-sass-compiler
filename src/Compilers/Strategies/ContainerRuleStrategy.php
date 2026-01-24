<?php

declare(strict_types=1);

namespace DartSass\Compilers\Strategies;

use DartSass\Parsers\Nodes\NodeType;

readonly class ContainerRuleStrategy extends ConditionalRuleStrategy
{
    protected function getRuleName(): NodeType
    {
        return NodeType::CONTAINER;
    }

    protected function getAtSymbol(): string
    {
        return '@container';
    }
}
