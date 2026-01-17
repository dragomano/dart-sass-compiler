<?php

declare(strict_types=1);

namespace DartSass\Handlers\Builtins;

use function array_map;
use function array_merge;
use function in_array;
use function is_array;

abstract class BaseModuleHandler implements ModuleHandlerInterface
{
    protected const MODULE_FUNCTIONS = [];

    protected const GLOBAL_FUNCTIONS = [];

    public function canHandle(string $functionName): bool
    {
        return in_array($functionName, $this->getSupportedFunctions(), true);
    }

    public function getSupportedFunctions(): array
    {
        return array_merge($this->getModuleFunctions(), $this->getGlobalFunctions());
    }

    public function getModuleFunctions(): array
    {
        return static::MODULE_FUNCTIONS;
    }

    public function getGlobalFunctions(): array
    {
        return static::GLOBAL_FUNCTIONS;
    }

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
