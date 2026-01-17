<?php

declare(strict_types=1);

namespace DartSass\Handlers\Builtins;

use DartSass\Handlers\SassModule;
use DartSass\Utils\ValueFormatter;

use function array_map;
use function implode;
use function str_ends_with;
use function str_starts_with;
use function substr;

class FormatFunctionHandler extends BaseModuleHandler implements LazyEvaluationInterface
{
    protected const GLOBAL_FUNCTIONS = ['format'];

    public function __construct(private readonly ValueFormatter $valueFormatter) {}

    public function requiresRawResult(string $functionName): bool
    {
        return false;
    }

    public function handle(string $functionName, array $args): string
    {
        $processedArgs = array_map(function ($arg) {
            $value = $this->valueFormatter->format($arg);

            if (! str_starts_with($value, '"')) {
                if (str_starts_with($value, "'") && str_ends_with($value, "'")) {
                    $value = substr($value, 1, -1);
                    $value = '"' . $value . '"';
                }

                $value = '"' . $value . '"';
            }

            return $value;
        }, $args);

        return $functionName . '(' . implode(', ', $processedArgs) . ')';
    }

    public function getModuleNamespace(): SassModule
    {
        return SassModule::CSS;
    }
}
