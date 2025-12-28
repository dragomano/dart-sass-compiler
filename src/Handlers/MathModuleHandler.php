<?php

declare(strict_types=1);

namespace DartSass\Handlers;

use DartSass\Modules\MathModule;
use DartSass\Utils\UnitValidator;
use DartSass\Utils\ValueFormatter;

use function array_map;
use function implode;
use function in_array;

class MathModuleHandler extends BaseModuleHandler
{
    private const SUPPORTED_FUNCTIONS = [
        'abs', 'acos', 'asin', 'atan', 'atan2', 'calc', 'ceil', 'clamp',
        'compatible', 'cos', 'div', 'floor', 'hypot', 'is-unitless', 'log',
        'max', 'min', 'percentage', 'pow', 'random', 'round', 'sin', 'sqrt', 'tan', 'unit',
    ];

    public function __construct(
        private readonly MathModule $mathModule,
        private readonly UnitValidator $unitValidator,
        private readonly ValueFormatter $valueFormatter
    ) {}

    public function canHandle(string $functionName): bool
    {
        return in_array($functionName, self::SUPPORTED_FUNCTIONS, true);
    }

    public function handle(string $functionName, array $args): string
    {
        $processedArgs = $this->normalizeArgs($args);

        $functionMapping = [
            'is-unitless' => 'isUnitless',
        ];

        $methodName = $functionMapping[$functionName] ?? $functionName;

        $noUnitCheckFunctions = ['compatible', 'is-unitless', 'random'];
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

    public function getSupportedFunctions(): array
    {
        return self::SUPPORTED_FUNCTIONS;
    }

    public function getModuleNamespace(): string
    {
        return 'math';
    }

    private function formatMathFunctionCall(string $name, array $args): string
    {
        $argsList = implode(', ', array_map($this->valueFormatter->format(...), $args));

        return "$name($argsList)";
    }
}
