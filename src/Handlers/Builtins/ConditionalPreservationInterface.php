<?php

declare(strict_types=1);

namespace DartSass\Handlers\Builtins;

interface ConditionalPreservationInterface extends ModuleHandlerInterface
{
    public function shouldPreserveForConditions(string $functionName): bool;
}
