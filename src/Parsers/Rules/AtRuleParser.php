<?php

declare(strict_types=1);

namespace DartSass\Parsers\Rules;

use DartSass\Parsers\AbstractParser;

abstract class AtRuleParser extends AbstractParser
{
    protected function incrementTokenIndex(): void
    {
        $this->setTokenIndex($this->getTokenIndex() + 1);
    }
}
