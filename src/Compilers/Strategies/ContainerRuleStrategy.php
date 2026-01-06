<?php

declare(strict_types=1);

namespace DartSass\Compilers\Strategies;

readonly class ContainerRuleStrategy extends ConditionalRuleStrategy
{
    protected function getRuleName(): string
    {
        return 'container';
    }

    protected function getAtSymbol(): string
    {
        return '@container';
    }
}
