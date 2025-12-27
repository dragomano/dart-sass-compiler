<?php

declare(strict_types=1);

namespace DartSass\Handlers;

class ModuleRegistry
{
    private array $functionMap = [];

    public function register(ModuleHandlerInterface $handler): void
    {
        foreach ($handler->getSupportedFunctions() as $functionName) {
            $this->functionMap[$functionName] = $handler;
        }
    }

    public function getHandler(string $functionName): ?ModuleHandlerInterface
    {
        return $this->functionMap[$functionName] ?? null;
    }
}
