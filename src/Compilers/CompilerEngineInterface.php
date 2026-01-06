<?php

declare(strict_types=1);

namespace DartSass\Compilers;

interface CompilerEngineInterface
{
    public function compileAst(array $ast, string $parentSelector = '', int $nestingLevel = 0): string;

    public function compileDeclarations(array $declarations, int $nestingLevel, string $parentSelector = ''): string;

    public function evaluateExpression(mixed $expr): mixed;
}
