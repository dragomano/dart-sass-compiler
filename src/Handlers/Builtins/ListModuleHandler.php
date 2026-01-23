<?php

declare(strict_types=1);

namespace DartSass\Handlers\Builtins;

use DartSass\Handlers\SassModule;
use DartSass\Modules\ListModule;

use function in_array;

class ListModuleHandler extends BaseModuleHandler implements ConditionalPreservationInterface, LazyEvaluationInterface
{
    protected const MODULE_FUNCTIONS = [
        'append', 'index', 'is-bracketed',
        'join', 'length', 'separator', 'nth',
        'set-nth', 'slash', 'zip',
    ];

    protected const GLOBAL_FUNCTIONS = [
        'append', 'index', 'is-bracketed',
        'join', 'length', 'list-separator',
        'nth', 'set-nth', 'zip',
    ];

    public function __construct(private readonly ListModule $listModule) {}

    public function handle(string $functionName, array $args): mixed
    {
        $processedArgs = $this->normalizeArgs($args);

        $functionMapping = ['list-separator' => 'separator'];

        $methodName = $functionMapping[$functionName] ?? $this->kebabToCamel($functionName);

        return $this->listModule->$methodName($processedArgs);
    }

    public function getModuleNamespace(): SassModule
    {
        return SassModule::LIST;
    }

    public function requiresRawResult(string $functionName): bool
    {
        return true;
    }

    public function shouldPreserveForConditions(string $functionName): bool
    {
        return in_array($functionName, ['index', 'is-bracketed'], true);
    }
}
