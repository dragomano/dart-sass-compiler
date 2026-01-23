<?php

declare(strict_types=1);

namespace DartSass\Handlers\Builtins;

use DartSass\Handlers\SassModule;
use DartSass\Modules\MapModule;

use function str_replace;

class MapModuleHandler extends BaseModuleHandler implements LazyEvaluationInterface
{
    protected const MODULE_FUNCTIONS = [
        'deep-merge', 'deep-remove', 'get',
        'has-key', 'keys', 'merge',
        'remove', 'set', 'values',
    ];

    protected const GLOBAL_FUNCTIONS = [
        'map-get', 'map-has-key', 'map-keys',
        'map-merge', 'map-remove', 'map-values',
    ];

    public function __construct(private readonly MapModule $mapModule) {}

    public function handle(string $functionName, array $args): mixed
    {
        $processedArgs = $this->normalizeArgs($args);

        $methodName = $this->kebabToCamel(str_replace('map-', '', $functionName));

        return $this->mapModule->$methodName($processedArgs);
    }

    public function requiresRawResult(string $functionName): bool
    {
        return true;
    }

    public function getModuleNamespace(): SassModule
    {
        return SassModule::MAP;
    }
}
