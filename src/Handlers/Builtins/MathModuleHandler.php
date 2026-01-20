<?php

declare(strict_types=1);

namespace DartSass\Handlers\Builtins;

use DartSass\Handlers\SassModule;
use DartSass\Modules\MathModule;
use DartSass\Utils\ResultFormatterInterface;
use DartSass\Values\SassNumber;

use function array_map;
use function implode;
use function is_array;

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
        private readonly ResultFormatterInterface $resultFormatter
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

        $result = $this->mathModule->$methodName($processedArgs);

        if ($result instanceof SassNumber || (is_array($result) && isset($result['value'], $result['unit']))) {
            return $this->resultFormatter->format($result);
        }

        if (is_array($result) && $result[0] === 'css') {
            $processedArgs = $result[1];
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
        $argsList = implode(', ', array_map($this->resultFormatter->format(...), $args));

        return "$name($argsList)";
    }
}
