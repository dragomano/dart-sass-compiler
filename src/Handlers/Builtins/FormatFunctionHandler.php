<?php

declare(strict_types=1);

namespace DartSass\Handlers\Builtins;

use DartSass\Handlers\SassModule;
use DartSass\Utils\ResultFormatterInterface;
use DartSass\Utils\StringFormatter;

use function array_map;
use function implode;
use function str_ends_with;
use function str_starts_with;

class FormatFunctionHandler extends BaseModuleHandler implements LazyEvaluationInterface
{
    protected const GLOBAL_FUNCTIONS = ['format'];

    public function __construct(private readonly ResultFormatterInterface $resultFormatter) {}

    public function requiresRawResult(string $functionName): bool
    {
        return false;
    }

    public function handle(string $functionName, array $args): string
    {
        $processedArgs = array_map(function ($arg) {
            $value = $this->resultFormatter->format($arg);

            if (! StringFormatter::isQuoted($value)) {
                $value = StringFormatter::forceQuoteString($value);
            } elseif (str_starts_with($value, "'") && str_ends_with($value, "'")) {
                $unquoted = StringFormatter::unquoteString($value);
                $value    = StringFormatter::forceQuoteString($unquoted);
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
