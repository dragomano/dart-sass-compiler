<?php

declare(strict_types=1);

namespace DartSass\Handlers;

use function count;
use function is_array;

abstract class BaseModuleHandler implements ModuleHandlerInterface
{
    protected function normalizeArgs(array $args): array
    {
        if (count($args) === 1 && is_array($args[0]) && ! isset($args[0]['value'])) {
            return $args[0];
        }

        return $args;
    }
}
