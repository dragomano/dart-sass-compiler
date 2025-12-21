<?php

declare(strict_types=1);

namespace DartSass\Parsers\Rules;

use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\ContainerNode;

class ContainerRuleParser extends MediaRuleParser
{
    protected function createNode(string $query, array $body, int $line): AstNode
    {
        return new ContainerNode($query, $body, $line);
    }
}
