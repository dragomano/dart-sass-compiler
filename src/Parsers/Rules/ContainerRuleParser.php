<?php

declare(strict_types=1);

namespace DartSass\Parsers\Rules;

use Closure;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\ContainerNode;
use DartSass\Parsers\Tokens\TokenStreamInterface;

class ContainerRuleParser extends MediaRuleParser
{
    public function __construct(
        TokenStreamInterface $stream,
        protected Closure $parseAtRule,
        protected Closure $parseInclude,
        protected Closure $parseVariable,
        protected Closure $parseRule,
        protected Closure $parseDeclaration
    ) {
        parent::__construct(
            $stream,
            $parseAtRule,
            $parseInclude,
            $parseVariable,
            $parseRule,
            $parseDeclaration
        );
    }

    protected function createNode(string $query, array $body, int $line): AstNode
    {
        return new ContainerNode($query, $body, $line);
    }
}
