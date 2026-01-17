<?php

declare(strict_types=1);

namespace DartSass\Handlers\Builtins;

interface LazyEvaluationInterface extends ModuleHandlerInterface
{
    public function requiresRawResult(string $functionName): bool;
}
