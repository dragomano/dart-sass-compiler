<?php

declare(strict_types=1);

namespace DartSass\Compilers\Strategies;

readonly class MediaRuleStrategy extends ConditionalRuleStrategy
{
    protected function getRuleName(): string
    {
        return 'media';
    }

    protected function getAtSymbol(): string
    {
        return '@media';
    }
}
