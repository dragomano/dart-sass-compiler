<?php

declare(strict_types=1);

namespace DartSass\Handlers\Builtins;

use DartSass\Handlers\SassModule;

interface ModuleHandlerInterface
{
    public function canHandle(string $functionName): bool;

    public function handle(string $functionName, array $args): mixed;

    public function getSupportedFunctions(): array;

    public function getModuleNamespace(): SassModule;

    public function getModuleFunctions(): array;

    public function getGlobalFunctions(): array;
}
