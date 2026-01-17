<?php

declare(strict_types=1);

namespace DartSass\Handlers\Builtins;

use DartSass\Exceptions\CompilationException;
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

    public function handle(string $functionName, array $args): string|int|array|null
    {
        $args = $this->normalizeArgs($args);

        return match ($functionName) {
            'quote'                => $this->stringModule->quote($args),
            'index', 'str-index'   => $this->stringModule->index($args),
            'insert', 'str-insert' => $this->stringModule->insert($args),
            'length', 'str-length' => $this->stringModule->length($args),
            'slice', 'str-slice'   => $this->stringModule->slice($args),
            'split'                => $this->stringModule->split($args),
            'to-upper-case'        => $this->stringModule->toUpperCase($args),
            'to-lower-case'        => $this->stringModule->toLowerCase($args),
            'unique-id'            => $this->stringModule->uniqueId($args),
            'unquote'              => $this->stringModule->unquote($args),
            default                => throw new CompilationException("Unknown string function: $functionName"),
        };
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
