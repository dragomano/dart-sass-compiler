<?php

declare(strict_types=1);

namespace DartSass\Handlers\ModuleHandlers;

use DartSass\Handlers\SassModule;
use DartSass\Modules\MapModule;

class MapModuleHandler extends BaseModuleHandler implements LazyEvaluationHandlerInterface
{
    protected const MODULE_FUNCTIONS = [
        'deep-merge',
        'deep-remove',
        'get',
        'has-key',
        'keys',
        'merge',
        'remove',
        'set',
        'values',
    ];

    protected const GLOBAL_FUNCTIONS = [
        'map-get',
        'map-has-key',
        'map-keys',
        'map-merge',
        'map-remove',
        'map-values',
    ];

    public function __construct(private readonly MapModule $mapModule) {}

    public function handle(string $functionName, array $args): mixed
    {
        $processedArgs = $this->normalizeArgs($args);

        $functionMapping = [
            'deep-merge'  => 'deepMerge',
            'deep-remove' => 'deepRemove',
            'has-key'     => 'hasKey',
            'map-get'     => 'get',
            'map-has-key' => 'hasKey',
            'map-keys'    => 'keys',
            'map-merge'   => 'merge',
            'map-remove'  => 'remove',
            'map-values'  => 'values',
        ];

        $methodName = $functionMapping[$functionName] ?? $functionName;

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
