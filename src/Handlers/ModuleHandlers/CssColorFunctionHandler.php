<?php

declare(strict_types=1);

namespace DartSass\Handlers\ModuleHandlers;

use DartSass\Handlers\SassModule;
use DartSass\Modules\SassColor;

use function is_array;

class CssColorFunctionHandler extends BaseModuleHandler
{
    protected const GLOBAL_FUNCTIONS = ['hsl', 'hwb', 'lab', 'lch', 'oklch'];

    public function handle(string $functionName, array $args): string
    {
        $processedArgs = $this->normalizeArgs($args);

        $hue     = $this->extractValue($processedArgs[0]);
        $sOrWOrA = $this->extractValue($processedArgs[1]);
        $lOrB    = $this->extractValue($processedArgs[2]);
        $alpha   = $this->extractAlpha($processedArgs);

        return (string) SassColor::$functionName($hue, $sOrWOrA, $lOrB, $alpha);
    }

    public function getModuleNamespace(): SassModule
    {
        return SassModule::CSS;
    }

    private function extractAlpha(array $args): ?float
    {
        return isset($args[3]) ? $this->extractValue($args[3]) : null;
    }

    private function extractValue(mixed $arg): float
    {
        return is_array($arg) && isset($arg['value'])
            ? (float) $arg['value']
            : (float) $arg;
    }
}
