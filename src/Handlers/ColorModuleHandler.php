<?php

declare(strict_types=1);

namespace DartSass\Handlers;

use DartSass\Exceptions\CompilationException;
use DartSass\Modules\ColorModule;

use function array_slice;
use function implode;
use function in_array;
use function is_array;
use function is_numeric;
use function is_string;
use function method_exists;
use function preg_match;
use function str_replace;
use function str_starts_with;
use function trim;
use function ucwords;

class ColorModuleHandler extends BaseModuleHandler
{
    private const SUPPORTED_FUNCTIONS = [
        // Basic color constructors
        'hsl', 'lch', 'oklch', 'hwb',
        // Color manipulation
        'adjust', 'change', 'channel', 'complement', 'grayscale',
        'ie-hex-str', 'invert', 'is-legacy', 'is-missing',
        'is-powerless', 'mix', 'same', 'scale', 'space', 'to-gamut', 'to-space',
        // Legacy color functions
        'adjust-color', 'adjust-hue', 'change-color', 'darken', 'desaturate', 'lighten',
        'saturate', 'opacify', 'transparentize', 'fade-in', 'fade-out',
        // Color property accessors
        'alpha', 'blackness', 'blue', 'green', 'hue', 'lightness',
        'red', 'saturation', 'whiteness', 'opacity',
    ];

    private const STRING_PARAM_FUNCTIONS = [
        'channel', 'is-missing', 'is-powerless', 'same', 'to-gamut', 'to-space',
    ];

    private const INT_PARAM_FUNCTIONS = ['invert'];

    public function __construct(private readonly ColorModule $colorModule) {}

    public function canHandle(string $functionName): bool
    {
        return in_array($functionName, self::SUPPORTED_FUNCTIONS, true);
    }

    public function handle(string $functionName, array $args): string
    {
        $processedArgs = $this->normalizeArgs($args);

        return match ($functionName) {
            'hsl'    => $this->handleColorConstructor('hsl', $processedArgs),
            'hwb'    => $this->handleColorConstructor('hwb', $processedArgs),
            'lch'    => $this->handleColorConstructor('lch', $processedArgs),
            'oklch'  => $this->handleColorConstructor('oklch', $processedArgs),
            'adjust' => $this->handleAdjustmentFunction('adjust', $processedArgs),
            'change' => $this->handleAdjustmentFunction('change', $processedArgs),
            'mix'    => $this->handleMixFunction($processedArgs),
            'scale'  => $this->handleScaleFunction($processedArgs),
            default  => $this->handleSimpleColorFunction($functionName, $processedArgs)
        };
    }

    public function getSupportedFunctions(): array
    {
        return self::SUPPORTED_FUNCTIONS;
    }

    public function getModuleNamespace(): string
    {
        return 'color';
    }

    private function handleColorConstructor(string $type, array $args): string
    {
        return $this->colorModule->$type(
            $this->extractValue($args[0]),
            $this->extractValue($args[1]),
            $this->extractValue($args[2]),
            $this->extractAlpha($args[3] ?? null)
        );
    }

    private function handleAdjustmentFunction(string $function, array $args): string
    {
        $color = $this->extractColor($args[0]);
        $adjustments = $this->extractAdjustments($args);

        return $this->colorModule->$function($color, $adjustments);
    }

    private function handleMixFunction(array $args): string
    {
        return $this->colorModule->mix(
            $this->extractColor($args[0]),
            $this->extractColor($args[1]),
            $this->extractWeight($args[2] ?? 0.5)
        );
    }

    private function handleScaleFunction(array $args): string
    {
        $color = $this->extractColor($args[0]);

        // If first argument is not a valid color, return as CSS function call
        if (! $this->isValidColorFormat($color)) {
            $formattedArgs = array_map($this->formatArgument(...), $args);

            return 'scale(' . implode(', ', $formattedArgs) . ')';
        }

        // Handle as Sass color scale function
        return $this->colorModule->scale($color, $this->extractAdjustments($args));
    }

    private function handleSimpleColorFunction(string $functionName, array $args): string
    {
        $color = $this->extractColor($args[0]);
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
                in_array($functionName, self::STRING_PARAM_FUNCTIONS, true) => $this->extractString($arg),
                in_array($functionName, self::INT_PARAM_FUNCTIONS, true) => $this->extractInt($arg),
                default => $this->extractValue($arg),
            };
        }

        return $scalarArgs;
    }

    private function convertToMethodName(string $functionName): string
    {
        return str_replace(' ', '', ucwords(str_replace('-', ' ', $functionName)));
    }

    private function extractColor(mixed $arg): string
    {
        return is_array($arg) && isset($arg['value'])
            ? (string) $arg['value']
            : (string) $arg;
    }

    private function extractValue(mixed $arg): float
    {
        return is_array($arg) && isset($arg['value'])
            ? (float) $arg['value']
            : (float) $arg;
    }

    private function extractString(mixed $arg): string
    {
        return is_array($arg) && isset($arg['value'])
            ? (string) $arg['value']
            : (string) $arg;
    }

    private function extractInt(mixed $arg): int
    {
        return is_array($arg) && isset($arg['value'])
            ? (int) $arg['value']
            : (int) $arg;
    }

    private function extractAlpha(mixed $arg): ?float
    {
        return $arg === null ? null : $this->extractValue($arg);
    }

    private function extractWeight(mixed $arg): float
    {
        $weight = $this->extractValue($arg);

        return $weight > 1 ? $weight / 100 : $weight;
    }

    private function extractAdjustments(array $args): array
    {
        $adjustments = [];

        foreach (array_slice($args, 1) as $key => $value) {
            if (is_string($key) && str_starts_with($key, '$')) {
                $adjustments[$key] = $this->extractValue($value);
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

        // Check for hex colors
        if (preg_match('/^#[0-9a-fA-F]+$/', $color)) {
            return true;
        }

        // Check for named colors
        if (isset(ColorModule::NAMED_COLORS[$color])) {
            return true;
        }

        // Check for functional color notations
        return (bool) preg_match('/^(rgb|rgba|hsl|hsla|hwb|lch|oklch)\(/i', $color);
    }

    private function formatArgument(mixed $arg): string
    {
        return is_array($arg) && isset($arg['value'])
            ? (string) $arg['value']
            : (string) $arg;
    }
}
