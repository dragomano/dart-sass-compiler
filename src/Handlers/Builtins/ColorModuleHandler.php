<?php

declare(strict_types=1);

namespace DartSass\Handlers\Builtins;

use DartSass\Exceptions\CompilationException;
use DartSass\Handlers\SassModule;
use DartSass\Modules\ColorFormat;
use DartSass\Modules\ColorModule;
use DartSass\Modules\ColorSerializer;

use function array_slice;
use function count;
use function implode;
use function in_array;
use function is_array;
use function is_numeric;
use function is_string;
use function method_exists;
use function preg_match;
use function str_contains;
use function str_replace;
use function str_starts_with;
use function trim;
use function ucwords;

class ColorModuleHandler extends BaseModuleHandler
{
    protected const MODULE_FUNCTIONS = [
        'adjust',
        'change',
        'channel',
        'complement',
        'grayscale',
        'ie-hex-str',
        'invert',
        'is-legacy',
        'is-missing',
        'is-powerless',
        'mix',
        'same',
        'scale',
        'space',
        'to-gamut',
        'to-space',
        'alpha',
        'blackness',
        'red',
        'green',
        'blue',
        'hue',
        'lightness',
        'saturation',
        'whiteness',
        'hwb',
    ];

    protected const GLOBAL_FUNCTIONS = [
        'adjust-color',
        'change-color',
        'complement',
        'grayscale',
        'ie-hex-str',
        'invert',
        'mix',
        'scale-color',
        'adjust-hue',
        'alpha',
        'opacity',
        'blackness',
        'red',
        'green',
        'blue',
        'darken',
        'desaturate',
        'hue',
        'lighten',
        'lightness',
        'opacify',
        'fade-in',
        'fade-out',
        'saturate',
        'saturation',
        'transparentize',
    ];

    private const CSS_FILTER_FUNCTIONS = [
        'blur', 'brightness', 'contrast', 'drop-shadow', 'grayscale',
        'hue-rotate', 'invert', 'opacity', 'saturate', 'sepia',
    ];

    private const STRING_PARAM_FUNCTIONS = [
        'channel', 'is-missing', 'is-powerless', 'same', 'to-gamut', 'to-space',
    ];

    private const INT_PARAM_FUNCTIONS = ['invert'];

    public function __construct(
        private readonly ColorModule $colorModule,
        private readonly CssColorFunctionHandler $cssColorHandler
    ) {}

    public function handle(string $functionName, array $args): string
    {
        // Check if this is a CSS filter function that should be returned as-is
        if ($this->isCssFilterFunction($functionName, $args)) {
            return $this->formatCssFunction($functionName, $args);
        }

        $processedArgs = $this->normalizeArgs($args);

        // Map legacy function names to their modern equivalents
        $mappedFunctionName = match ($functionName) {
            'adjust-color' => 'adjust',
            'change-color' => 'change',
            'scale-color'  => 'scale',
            default        => $functionName,
        };

        return match ($mappedFunctionName) {
            'hwb'    => $this->cssColorHandler->handle('hwb', $processedArgs),
            'adjust' => $this->handleAdjustmentFunction('adjust', $processedArgs),
            'change' => $this->handleAdjustmentFunction('change', $processedArgs),
            'mix'    => $this->handleMixFunction($processedArgs),
            'scale'  => $this->handleScaleFunction($processedArgs),
            default  => $this->handleSimpleColorFunction($functionName, $processedArgs)
        };
    }

    public function getModuleNamespace(): SassModule
    {
        return SassModule::COLOR;
    }

    private function handleAdjustmentFunction(string $function, array $args): string
    {
        $color = $this->extract($args[0]);
        $adjustments = $this->extractAdjustments($args);

        return $this->colorModule->$function($color, $adjustments);
    }

    private function handleMixFunction(array $args): string
    {
        return $this->colorModule->mix(
            $this->extract($args[0]),
            $this->extract($args[1]),
            $this->extractWeight($args[2] ?? 0.5)
        );
    }

    private function handleScaleFunction(array $args): string
    {
        $color = $this->extract($args[0]);

        // If first argument is not a valid color, return as CSS function call
        if (! $this->isValidColorFormat($color)) {
            $formattedArgs = array_map($this->extract(...), $args);

            return 'scale(' . implode(', ', $formattedArgs) . ')';
        }

        // Handle as Sass color scale function
        return $this->colorModule->scale($color, $this->extractAdjustments($args));
    }

    private function handleSimpleColorFunction(string $functionName, array $args): string
    {
        $color = $this->extract($args[0]);
        $methodName = $this->convertToMethodName($functionName);

        if (! method_exists($this->colorModule, $methodName)) {
            throw new CompilationException("Unknown color function: $functionName");
        }

        // Get remaining arguments after color
        $remainingArgs = array_slice($args, 1);

        // If no additional arguments, just call with color
        if (empty($remainingArgs)) {
            return $this->colorModule->$methodName($color);
        }

        // Otherwise extract scalar arguments based on function type
        $scalarArgs = $this->extractTypedArguments($functionName, $remainingArgs);

        return $this->colorModule->$methodName($color, ...$scalarArgs);
    }

    private function extractTypedArguments(string $functionName, array $args): array
    {
        $scalarArgs = [];

        foreach ($args as $arg) {
            $scalarArgs[] = match (true) {
                in_array($functionName, self::STRING_PARAM_FUNCTIONS, true) => $this->extract($arg),
                in_array($functionName, self::INT_PARAM_FUNCTIONS, true) => $this->extract($arg, 'int'),
                default => $this->extract($arg, 'float'),
            };
        }

        return $scalarArgs;
    }

    private function convertToMethodName(string $functionName): string
    {
        return str_replace(' ', '', ucwords(str_replace('-', ' ', $functionName)));
    }

    private function extract(mixed $arg, string $cast = 'string'): int|float|string
    {
        $value = is_array($arg) && isset($arg['value']) ? $arg['value'] : $arg;

        return match ($cast) {
            'int'   => (int) $value,
            'float' => (float) $value,
            default => (string) $value,
        };
    }

    private function extractWeight(mixed $arg): float
    {
        $weight = $this->extract($arg, 'float');

        return $weight > 1 ? $weight / 100 : $weight;
    }

    private function extractAdjustments(array $args): array
    {
        $adjustments = [];

        foreach (array_slice($args, 1) as $key => $value) {
            if (is_string($key) && str_starts_with($key, '$')) {
                $adjustments[$key] = $this->extract($value, 'float');
            }
        }

        return $adjustments;
    }

    private function isValidColorFormat(string $color): bool
    {
        if (is_numeric($color)) {
            return false;
        }

        $color = trim($color);

        // Check for named colors
        if (isset(ColorSerializer::NAMED_COLORS[$color])) {
            return true;
        }

        // Check for functional color notations using ColorFormat patterns
        foreach (ColorFormat::cases() as $format) {
            if (preg_match($format->getPattern(), $color)) {
                return true;
            }
        }

        return false;
    }

    private function isCssFilterFunction(string $functionName, array $args): bool
    {
        if (count($args) !== 1) {
            return false;
        }

        if (! in_array($functionName, self::CSS_FILTER_FUNCTIONS, true)) {
            return false;
        }

        $arg = $args[0];

        $value = is_array($arg) && isset($arg['value']) ? (string) $arg['value'] : (string) $arg;

        // Check if the argument looks like a CSS filter value
        // (contains units, percentages, or looks like a CSS value)
        return preg_match('/^(-?\d*\.?\d+)(deg|px|em|rem|%|)$/', $value) > 0
            || str_contains($value, '%')
            || str_contains($value, 'deg')
            || str_contains($value, 'px')
            || str_contains($value, 'em')
            || str_contains($value, 'rem');
    }

    private function formatCssFunction(string $functionName, array $args): string
    {
        $formattedArgs = array_map(function ($arg) {
            if (is_array($arg) && isset($arg['value'])) {
                $value = (string) $arg['value'];
                $unit  = $arg['unit'] ?? '';

                return $unit !== '' ? $value . $unit : $value;
            }

            return (string) $arg;
        }, $args);

        return $functionName . '(' . implode(', ', $formattedArgs) . ')';
    }
}
