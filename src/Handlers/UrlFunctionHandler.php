<?php

declare(strict_types=1);

namespace DartSass\Handlers;

use InvalidArgumentException;

use function addcslashes;
use function count;
use function is_array;
use function is_string;
use function preg_match;
use function str_replace;
use function trim;

class UrlFunctionHandler extends BaseModuleHandler
{
    public function canHandle(string $functionName): bool
    {
        return $functionName === 'url';
    }

    public function handle(string $functionName, array $args): string
    {
        if (count($args) !== 1) {
            throw new InvalidArgumentException('url() function expects exactly one argument');
        }

        $arg = $args[0];

        // Handle both string arguments and arrays with quote information
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

        // Remove double quotes that appear in the middle of strings
        if (preg_match('/"([^"]*)""([^"]*)"/', $value, $matches)) {
            $value = '"' . $matches[1] . $matches[2] . '"';
        }

        if ($isQuoted) {
            $value = trim($value, '"\'');
            $value = addcslashes($value, '"');

            return 'url("' . $value . '")';
        }

        // No quotes in original - keep it that way
        return 'url(' . $value . ')';
    }

    public function getSupportedFunctions(): array
    {
        return ['url'];
    }

    public function getModuleNamespace(): string
    {
        return 'builtin';
    }
}
