<?php

declare(strict_types=1);

namespace DartSass\Handlers\Builtins;

use function array_key_exists;
use function array_map;
use function array_merge;
use function in_array;
use function is_array;
use function is_string;
use function lcfirst;
use function str_replace;
use function str_starts_with;
use function trim;
use function ucwords;

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

    protected function kebabToCamel(string $name): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $name))));
    }

    protected function normalizeArgs(array $args): array
    {
        return array_map($this->normalizeArg(...), $args);
    }

    protected function hasArgument(array $args, int $position, array $names = []): bool
    {
        if (array_key_exists($position, $args)) {
            return true;
        }

        foreach ($names as $name) {
            if (array_key_exists($name, $args)) {
                return true;
            }

            $normalizedName = str_starts_with($name, '$') ? $name : '$' . $name;
            if (array_key_exists($normalizedName, $args)) {
                return true;
            }
        }

        return false;
    }

    protected function getArgument(
        array $args,
        int $position,
        array $names = [],
        mixed $default = null
    ): mixed {
        if (array_key_exists($position, $args)) {
            return $args[$position];
        }

        foreach ($names as $name) {
            if (array_key_exists($name, $args)) {
                return $args[$name];
            }

            $normalizedName = str_starts_with($name, '$') ? $name : '$' . $name;
            if (array_key_exists($normalizedName, $args)) {
                return $args[$normalizedName];
            }
        }

        return $default;
    }

    private function normalizeArg(mixed $arg): mixed
    {
        if (is_array($arg) && isset($arg['value'], $arg['unit'])) {
            return $arg;
        }

        if (is_array($arg) && isset($arg['value'])) {
            return $this->normalizeArg($arg['value']);
        }

        if (is_string($arg)) {
            return trim($arg, '"\'');
        }

        return $arg;
    }
}
