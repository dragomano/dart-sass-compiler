<?php

declare(strict_types=1);

namespace DartSass\Compilers\Nodes;

use DartSass\Parsers\Nodes\AstNode;

interface NodeCompiler
{
    public function compile(AstNode $node, string $parentSelector = '', int $nestingLevel = 0): string;
}
