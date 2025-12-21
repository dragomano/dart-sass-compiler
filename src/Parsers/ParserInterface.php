<?php

declare(strict_types=1);

namespace DartSass\Parsers;

use DartSass\Parsers\Nodes\AstNode;

interface ParserInterface
{
    public function parse(): array;

    public function parseAtRule(): AstNode;

    public function parseRule(): AstNode;

    public function parseDeclaration(): array;

    public function parseExpression(): AstNode;

    public function parseBlock(): array;

    public function parseInclude(): AstNode;

    public function parseVariable(): AstNode;
}
