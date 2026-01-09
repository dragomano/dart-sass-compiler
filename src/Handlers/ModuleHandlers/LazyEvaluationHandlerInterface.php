<?php

declare(strict_types=1);

namespace DartSass\Handlers\ModuleHandlers;

interface LazyEvaluationHandlerInterface extends ModuleHandlerInterface
{
    public function requiresRawResult(string $functionName): bool;
}
