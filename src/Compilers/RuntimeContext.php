<?php

declare(strict_types=1);

namespace DartSass\Compilers;

use Closure;

final class RuntimeContext
{
    public ?CompilerEngine $engine = null;

    public ?Closure $compileAst = null;

    public ?Closure $compileDeclarations = null;

    public ?Closure $addMapping = null;
}
