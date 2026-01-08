<?php

declare(strict_types=1);

namespace DartSass\Handlers;

use function array_map;
use function is_array;

abstract class BaseModuleHandler implements ModuleHandlerInterface
{
    protected function normalizeArgs(array $args): array
    {
        return array_map($this->normalizeArg(...), $args);
    }

    private function normalizeArg(mixed $arg): mixed
    {
        if (is_array($arg) && isset($arg['value'], $arg['unit'])) {
            // Keep the full array if it has both value and unit
            return $arg;
        }

        if (is_array($arg) && isset($arg['value'])) {
            return $arg['value'];
        }

        return $arg;
    }
}
