<?php

declare(strict_types=1);

namespace DartSass\Handlers\Builtins;

use DartSass\Handlers\SassModule;
use DartSass\Modules\StringModule;

use function in_array;

class StringModuleHandler extends BaseModuleHandler implements ConditionalPreservationInterface
{
    protected const MODULE_FUNCTIONS = [
        'quote',
        'index',
        'insert',
        'length',
        'slice',
        'split',
        'to-upper-case',
        'to-lower-case',
        'unique-id',
        'unquote',
    ];

    protected const GLOBAL_FUNCTIONS = [
        'quote',
        'str-index',
        'str-insert',
        'str-length',
        'str-slice',
        'to-upper-case',
        'to-lower-case',
        'unique-id',
        'unquote',
    ];

    public function __construct(private readonly StringModule $stringModule) {}

    public function handle(string $functionName, array $args): mixed
    {
        $processedArgs = $this->normalizeArgs($args);

        $functionMapping = [
            'str-index'     => 'index',
            'str-insert'    => 'insert',
            'str-length'    => 'length',
            'str-slice'     => 'slice',
            'to-upper-case' => 'toUpperCase',
            'to-lower-case' => 'toLowerCase',
            'unique-id'     => 'uniqueId',
        ];

        $methodName = $functionMapping[$functionName] ?? $functionName;

        return $this->stringModule->$methodName($processedArgs);
    }

    public function getModuleNamespace(): SassModule
    {
        return SassModule::STRING;
    }

    public function shouldPreserveForConditions(string $functionName): bool
    {
        return in_array($functionName, ['index', 'str-index', 'unquote'], true);
    }
}
