<?php

declare(strict_types=1);

namespace DartSass\Handlers\Builtins;

use DartSass\Handlers\SassModule;
use DartSass\Modules\MetaModule;

use function in_array;

class MetaModuleHandler extends BaseModuleHandler implements LazyEvaluationInterface
{
    protected const MODULE_FUNCTIONS = [
        'apply', 'load-css', 'accepts-content',
        'calc-args', 'calc-name', 'call',
        'content-exists', 'feature-exists',
        'function-exists', 'get-function',
        'get-mixin', 'global-variable-exists',
        'inspect', 'keywords', 'mixin-exists',
        'module-functions', 'module-mixins',
        'module-variables', 'type-of', 'variable-exists',
    ];

    protected const GLOBAL_FUNCTIONS = [
        'call', 'content-exists', 'feature-exists',
        'function-exists', 'get-function',
        'global-variable-exists', 'inspect',
        'keywords', 'mixin-exists', 'type-of',
        'variable-exists',
    ];

    public function __construct(private readonly MetaModule $metaModule) {}

    public function handle(string $functionName, array $args): mixed
    {
        $processedArgs = $this->requiresRawResult($functionName) ? $args : $this->normalizeArgs($args);

        $methodName = $this->kebabToCamel($functionName);

        return $this->metaModule->$methodName($processedArgs);
    }

    public function getModuleNamespace(): SassModule
    {
        return SassModule::META;
    }

    public function requiresRawResult(string $functionName): bool
    {
        return in_array($functionName, [
            'calc-args',
            'get-function',
            'get-mixin',
            'keywords',
            'module-functions',
            'module-mixins',
            'module-variables',
        ], true);
    }
}
