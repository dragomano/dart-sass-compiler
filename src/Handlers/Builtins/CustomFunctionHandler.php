<?php

declare(strict_types=1);

namespace DartSass\Handlers\Builtins;

use DartSass\Handlers\ModuleRegistry;
use DartSass\Handlers\SassModule;

use function array_keys;
use function array_map;
use function call_user_func_array;
use function is_array;
use function is_numeric;

class CustomFunctionHandler extends BaseModuleHandler
{
    private array $customFunctions = [];

    private ?ModuleRegistry $registry = null;

    public function setRegistry(ModuleRegistry $registry): void
    {
        $this->registry = $registry;
    }

    public function canHandle(string $functionName): bool
    {
        return isset($this->customFunctions[$functionName]);
    }

    public function handle(string $functionName, array $args): mixed
    {
        if (! isset($this->customFunctions[$functionName])) {
            return null;
        }

        $callback = $this->customFunctions[$functionName];

        // Extract metadata about arguments (units, etc.)
        $metadata = $this->extractMetadata($args);

        // Process arguments for the callback
        $processedArgs = array_map($this->extractScalarValue(...), $args);

        // Call the custom function
        $result = call_user_func_array($callback, $processedArgs);

        // If first argument had a unit, apply it to the result
        if (isset($metadata[0]['unit']) && is_numeric($result)) {
            return ['value' => $result, 'unit' => $metadata[0]['unit']];
        }

        return $result;
    }

    public function getSupportedFunctions(): array
    {
        return array_keys($this->customFunctions);
    }

    public function getModuleNamespace(): SassModule
    {
        return SassModule::CUSTOM;
    }

    public function getGlobalFunctions(): array
    {
        return $this->getSupportedFunctions();
    }

    public function addCustomFunction(string $name, callable $callback): void
    {
        $this->customFunctions[$name] = $callback;

        $this->registry?->register($this);
    }

    public function setCustomFunctions(array $functions): void
    {
        $this->customFunctions = $functions;
    }

    private function extractMetadata(array $args): array
    {
        $metadata = [];

        foreach ($args as $arg) {
            if (is_array($arg)) {
                $metadata[] = [
                    'unit' => $arg['unit'] ?? null,
                ];
            } else {
                $metadata[] = ['unit' => null];
            }
        }

        return $metadata;
    }

    private function extractScalarValue(mixed $arg): mixed
    {
        if (is_array($arg) && isset($arg['value'])) {
            return is_numeric($arg['value']) ? (float) $arg['value'] : $arg['value'];
        }

        return $arg;
    }
}
