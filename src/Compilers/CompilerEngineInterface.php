<?php

declare(strict_types=1);

namespace DartSass\Compilers;

use DartSass\Parsers\Syntax;

interface CompilerEngineInterface
{
    public function compileString(string $string, ?Syntax $syntax = null): string;

    public function compileFile(string $filePath): string;

    public function addFunction(string $name, callable $callback): void;

    public function getOptions(): array;

    public function getMappings(): array;
}
