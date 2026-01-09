<?php

declare(strict_types=1);

namespace DartSass\Handlers;

use DartSass\Handlers\ModuleHandlers\ModuleHandlerInterface;

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
}
