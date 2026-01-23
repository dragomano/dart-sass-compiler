<?php

declare(strict_types=1);

namespace DartSass\Handlers\Builtins;

use DartSass\Handlers\SassModule;
use DartSass\Modules\SelectorModule;

use function str_replace;

class SelectorModuleHandler extends BaseModuleHandler
{
    protected const MODULE_FUNCTIONS = [
        'is-superselector', 'append', 'extend',
        'nest', 'parse', 'replace', 'unify',
        'simple-selectors',
    ];

    protected const GLOBAL_FUNCTIONS = [
        'is-superselector', 'selector-append', 'selector-extend',
        'selector-nest', 'selector-parse', 'selector-replace',
        'selector-unify', 'simple-selectors',
    ];

    public function __construct(private readonly SelectorModule $selectorModule) {}

    public function handle(string $functionName, array $args): mixed
    {
        $processedArgs = $this->normalizeArgs($args);

        $functionMapping = ['is-superselector' => 'isSuperSelector'];

        $methodName = $functionMapping[$functionName]
            ?? $this->kebabToCamel(str_replace('selector-', '', $functionName));

        return $this->selectorModule->$methodName($processedArgs);
    }

    public function getModuleNamespace(): SassModule
    {
        return SassModule::SELECTOR;
    }
}
