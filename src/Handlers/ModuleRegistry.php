<?php

declare(strict_types=1);

namespace DartSass\Handlers;

class ModuleRegistry
{
    private array $functionMap = [];

    public function register(ModuleHandlerInterface $handler): void
    {
        $namespace = $handler->getModuleNamespace();

        foreach ($handler->getSupportedFunctions() as $functionName) {
            // Register both namespace.function and function
            $this->functionMap[$namespace . '.' . $functionName] = $handler;
            $this->functionMap[$functionName] = $handler;
        }
    }

    public function getHandler(string $functionName): ?ModuleHandlerInterface
    {
        return $this->functionMap[$functionName] ?? null;
    }
}
