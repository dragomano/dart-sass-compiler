<?php

declare(strict_types=1);

namespace DartSass\Handlers;

interface ModuleHandlerInterface
{
    public function canHandle(string $functionName): bool;

    public function handle(string $functionName, array $args): mixed;

    public function getSupportedFunctions(): array;

    public function getModuleNamespace(): string;
}
