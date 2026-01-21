<?php

declare(strict_types=1);

namespace DartSass\Compilers;

use DartSass\Compilers\Nodes\NodeCompiler;
use DartSass\Parsers\Syntax;

interface CompilerEngineInterface
{
    public function compileString(string $string, ?Syntax $syntax = null): string;

    public function compileFile(string $filePath): string;

    public function evaluateExpression(mixed $expr): mixed;

    public function addFunction(string $name, callable $callback): void;

    public function getContext(): CompilerContext;

    public function findNodeCompiler(string $nodeType): ?NodeCompiler;

    public function compileAst(array $ast, string $parentSelector = '', int $nestingLevel = 0): string;

    public function compileDeclarations(array $declarations, int $nestingLevel, string $parentSelector = ''): string;

    public function formatRule(string $selector, string $content, int $nestingLevel): string;

    public function getIndent(int $level): string;
}
