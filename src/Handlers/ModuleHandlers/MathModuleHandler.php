<?php

declare(strict_types=1);

namespace DartSass\Handlers\ModuleHandlers;

use DartSass\Handlers\SassModule;
use DartSass\Modules\MathModule;
use DartSass\Utils\UnitValidator;
use DartSass\Utils\ValueFormatter;

use function array_map;
use function implode;
use function in_array;

class MathModuleHandler extends BaseModuleHandler
{
    protected const MODULE_FUNCTIONS = [
        'ceil',
        'clamp',
        'floor',
        'max',
        'min',
        'round',
        'abs',
        'hypot',
        'log',
        'pow',
        'sqrt',
        'cos',
        'sin',
        'tan',
        'acos',
        'asin',
        'atan',
        'atan2',
        'compatible',
        'is-unitless',
        'unit',
        'div',
        'percentage',
        'random',
    ];

    protected const GLOBAL_FUNCTIONS = [
        'ceil',
        'floor',
        'max',
        'min',
        'round',
        'abs',
        'comparable',
        'unitless',
        'unit',
        'percentage',
        'random',
    ];

    public function __construct(
        private readonly MathModule $mathModule,
        private readonly UnitValidator $unitValidator,
        private readonly ValueFormatter $valueFormatter
    ) {}

    public function handle(string $functionName, array $args): string
    {
        $processedArgs = $this->normalizeArgs($args);

        $functionMapping = [
            'comparable'  => 'compatible',
            'is-unitless' => 'isUnitless',
            'unitless'    => 'isUnitless',
        ];

        $methodName = $functionMapping[$functionName] ?? $functionName;

        $noUnitCheckFunctions = ['comparable', 'compatible', 'is-unitless', 'random'];
        $shouldCompute = in_array($functionName, $noUnitCheckFunctions, true);

        if (! $shouldCompute) {
            $shouldCompute = $this->unitValidator->validate($processedArgs);
        }

        if ($shouldCompute) {
            $result = $this->mathModule->$methodName($processedArgs);

            return $this->valueFormatter->format($result);
        }

        return $this->formatMathFunctionCall($functionName, $processedArgs);
    }

    public function getModuleNamespace(): SassModule
    {
        return SassModule::MATH;
    }

    public function getGlobalFunctions(): array
    {
        return [...self::GLOBAL_FUNCTIONS, 'clamp'];
    }

    private function formatMathFunctionCall(string $name, array $args): string
    {
        $argsList = implode(', ', array_map($this->valueFormatter->format(...), $args));

        return "$name($argsList)";
    }
}
