<?php

declare(strict_types=1);

namespace DartSass\Handlers\ModuleHandlers;

interface ConditionalPreservationHandlerInterface extends ModuleHandlerInterface
{
    public function shouldPreserveForConditions(string $functionName): bool;
}
