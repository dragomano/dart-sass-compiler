<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

use stdClass;

abstract class AstNode extends stdClass
{
    public function __construct(public NodeType $type, public int $line = 0, public int $column = 0) {}
}
