<?php

declare(strict_types=1);

namespace DartSass\Handlers\Builtins;

use DartSass\Handlers\SassModule;
use DartSass\Utils\ResultFormatterInterface;
use DartSass\Utils\StringFormatter;
use InvalidArgumentException;

use function addcslashes;
use function array_map;
use function count;
use function implode;
use function is_array;
use function is_string;
use function str_ends_with;
use function str_replace;
use function str_starts_with;
use function trim;

class UrlFunctionHandler extends BaseModuleHandler
{
    protected const GLOBAL_FUNCTIONS = ['url', 'format'];

    public function __construct(private readonly ResultFormatterInterface $resultFormatter) {}

    public function handle(string $functionName, array $args): string
    {
        return match ($functionName) {
            'url'    => $this->handleUrl($args),
            'format' => $this->handleFormat($args),
            default  => throw new InvalidArgumentException("Unknown function: $functionName")
        };
    }

    public function getModuleNamespace(): SassModule
    {
        return SassModule::CSS;
    }

    private function handleUrl(array $args): string
    {
        if (count($args) !== 1) {
            throw new InvalidArgumentException('url() function expects exactly one argument');
        }

        $arg = $args[0];

        if (is_array($arg) && isset($arg['value']) && isset($arg['quoted'])) {
            $value    = str_replace('"', "'", $arg['value']);
            $isQuoted = $arg['quoted'];
        } elseif (is_string($arg)) {
            $value    = $arg;
            $isQuoted = false;
        } else {
            throw new InvalidArgumentException(
                'url() argument must be a string or array with value and quoted status'
            );
        }

        $value = trim($value);

        if ($isQuoted) {
            $value = trim($value, '"\'');
            $value = addcslashes($value, '"');

            return 'url("' . $value . '")';
        }

        return 'url(' . $value . ')';
    }

    private function handleFormat(array $args): string
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

        return 'format(' . implode(', ', $processedArgs) . ')';
    }
}
