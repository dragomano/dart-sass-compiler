<?php

declare(strict_types=1);

namespace DartSass\Handlers;

use DartSass\Handlers\Builtins\ModuleHandlerInterface;

use function array_merge;
use function array_unique;
use function str_starts_with;

class ModuleRegistry
{
    private array $functionMap = [];

    public function register(ModuleHandlerInterface $handler): void
    {
        $namespace = $handler->getModuleNamespace()->value;

        foreach ($handler->getModuleFunctions() as $functionName) {
            $this->functionMap[$namespace . '.' . $functionName] = $handler;
        }

        // Register global functions - they should be available without @use
        foreach ($handler->getGlobalFunctions() as $functionName) {
            $this->functionMap[$functionName] = $handler;
        }
    }

    public function getHandler(string $functionName): ?ModuleHandlerInterface
    {
        return $this->functionMap[$functionName] ?? null;
    }

    public function getFunctionsForModule(string $moduleName): array
    {
        $handler = null;

        foreach ($this->functionMap as $fullName => $h) {
            if (str_starts_with($fullName, $moduleName . '.')) {
                $handler = $h;

                break;
            }
        }

        if ($handler) {
            return array_unique(array_merge(
                $handler->getModuleFunctions(),
                $handler->getGlobalFunctions()
            ));
        }

        return [];
    }
}
