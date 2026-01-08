<?php

declare(strict_types=1);

namespace DartSass\Handlers;

use DartSass\Exceptions\CompilationException;
use DartSass\Modules\StringModule;

use function in_array;

class StringModuleHandler extends BaseModuleHandler
{
    private const SUPPORTED_FUNCTIONS = [
        // Module functions
        'quote', 'index', 'insert', 'length', 'slice', 'split',
        'to-upper-case', 'to-lower-case', 'unique-id', 'unquote',
        // Global functions
        'str-index', 'str-length', 'str-insert', 'str-slice',
    ];

    public function __construct(private readonly StringModule $stringModule) {}

    public function canHandle(string $functionName): bool
    {
        return in_array($functionName, self::SUPPORTED_FUNCTIONS, true);
    }

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

    public function getSupportedFunctions(): array
    {
        return self::SUPPORTED_FUNCTIONS;
    }

    public function getModuleNamespace(): string
    {
        return 'string';
    }
}
