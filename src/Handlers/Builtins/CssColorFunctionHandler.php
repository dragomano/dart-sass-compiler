<?php

declare(strict_types=1);

namespace DartSass\Handlers\Builtins;

use DartSass\Handlers\SassModule;
use DartSass\Values\SassColor;
use DartSass\Values\SassList;
use DartSass\Values\SassNumber;

use function is_array;

class CssColorFunctionHandler extends BaseModuleHandler
{
    protected const GLOBAL_FUNCTIONS = ['hsl', 'hwb', 'lab', 'lch', 'oklch'];

    public function handle(string $functionName, array $args): string
    {
        if (isset($args[0]) && $args[0] instanceof SassList) {
            $processedArgs = $this->normalizeArgs($args[0]->value);
        } else {
            $processedArgs = $this->normalizeArgs($args);
        }

        $hue     = $this->extractValue($processedArgs[0]);
        $sOrWOrA = $this->extractValue($processedArgs[1]);
        $lOrB    = $this->extractValue($processedArgs[2]);
        $alpha   = isset($processedArgs[3]) ? $this->extractValue($processedArgs[3]) : null;

        return (string) SassColor::$functionName($hue, $sOrWOrA, $lOrB, $alpha);
    }

    public function getModuleNamespace(): SassModule
    {
        return SassModule::CSS;
    }

    private function extractValue(mixed $arg): float
    {
        if ($arg instanceof SassNumber) {
            return $arg->getValue();
        }

        return is_array($arg) && isset($arg['value'])
            ? (float) $arg['value']
            : (float) $arg;
    }
}
