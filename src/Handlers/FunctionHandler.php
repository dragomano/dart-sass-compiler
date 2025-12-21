<?php

declare(strict_types=1);

namespace DartSass\Handlers;

use DartSass\Exceptions\CompilationException;
use DartSass\Utils\ColorFunctions;
use DartSass\Utils\LazyValue;
use DartSass\Utils\MathFunctions;
use DartSass\Utils\ValueFormatter;

use function array_key_exists;
use function array_map;
use function array_slice;
use function array_unique;
use function call_user_func_array;
use function count;
use function explode;
use function gettype;
use function implode;
use function is_array;
use function is_numeric;
use function is_object;
use function is_string;
use function preg_match;
use function rtrim;
use function str_contains;
use function str_ends_with;
use function str_starts_with;
use function strtolower;
use function substr;
use function trim;

class FunctionHandler
{
    private const LIST_FUNCTIONS = [
        'length' => true,
        'nth'    => true,
    ];

    private const MODULE_COLOR_FUNCTIONS = [
        'adjust'         => true,
        'mix'            => true,
        'lighten'        => true,
        'darken'         => true,
        'saturate'       => true,
        'desaturate'     => true,
        'opacify'        => true,
        'transparentize' => true,
        'scale'          => true,
        'change'         => true,
        'hsl'            => true,
        'hwb'            => true,
    ];

    private const MODULE_MATH_FUNCTIONS = [
        'abs'   => true,
        'ceil'  => true,
        'floor' => true,
        'sqrt'  => true,
        'sin'   => true,
        'cos'   => true,
        'tan'   => true,
        'asin'  => true,
        'acos'  => true,
        'atan'  => true,
        'atan2' => true,
        'log'   => true,
        'pow'   => true,
    ];

    private const CSS_FILTERS = [
        'saturate'    => true,
        'invert'      => true,
        'grayscale'   => true,
        'sepia'       => true,
        'blur'        => true,
        'brightness'  => true,
        'contrast'    => true,
        'hue-rotate'  => true,
        'drop-shadow' => true,
    ];

    private const SIMPLE_COLOR_ADJUSTMENTS = [
        'lighten'        => true,
        'darken'         => true,
        'saturate'       => true,
        'desaturate'     => true,
        'opacify'        => true,
        'transparentize' => true,
    ];

    private const MATH_HANDLERS = [
        'abs'   => true,
        'calc'  => true,
        'clamp' => true,
        'max'   => true,
        'min'   => true,
        'ceil'  => true,
        'floor' => true,
        'round' => true,
    ];

    private const ADJUST_PARAM_ORDER = [
        '$red',
        '$green',
        '$blue',
        '$hue',
        '$saturation',
        '$lightness',
        '$whiteness',
        '$blackness',
        '$x',
        '$y',
        '$z',
        '$chroma',
        '$alpha',
        '$space',
    ];

    private readonly MathFunctions $mathFunctions;

    private readonly ColorFunctions $colorFunctions;

    private array $customFunctions = [];

    private array $userDefinedFunctions = [];

    private $evaluateExpression;

    public function __construct(
        private readonly ValueFormatter $valueFormatter,
        private readonly ModuleHandler $moduleHandler,
        callable $evaluateExpression,
    ) {
        $this->mathFunctions      = new MathFunctions($valueFormatter);
        $this->colorFunctions     = new ColorFunctions();
        $this->evaluateExpression = $evaluateExpression;
    }

    public function addCustom(string $name, callable $callback): void
    {
        $this->customFunctions[$name] = $callback;
    }

    public function defineUserFunction(
        string $name,
        array $args,
        array $body,
        VariableHandler $variableHandler,
    ): void {
        $this->userDefinedFunctions[$name] = [
            'args'    => $args,
            'body'    => $body,
            'handler' => $variableHandler,
        ];
    }

    public function getUserFunctions(): array
    {
        return [
            'customFunctions'      => $this->customFunctions,
            'userDefinedFunctions' => $this->userDefinedFunctions,
        ];
    }

    public function setUserFunctions(array $state): void
    {
        $this->customFunctions      = $state['customFunctions'] ?? [];
        $this->userDefinedFunctions = $state['userDefinedFunctions'] ?? [];
    }

    public function call(string $name, array $args)
    {
        [$namespace, $funcName] = str_contains($name, '.') ? explode('.', $name) : [null, $name];

        if (
            count($args) === 1 && is_array($args[0])
            && ! isset($args[0]['value'])
            && ! isset(self::LIST_FUNCTIONS[$funcName])
        ) {
            $args = $args[0];
        }

        $originalName = $name;

        $modulePath = match ($namespace) {
            'color'    => 'sass:color',
            'list'     => 'sass:list',
            'map'      => 'sass:map',
            'math'     => 'sass:math',
            'meta'     => 'sass:meta',
            'selector' => 'sass:selector',
            'string'   => 'sass:string',
            default    => $namespace,
        };

        if ($namespace && ! $this->moduleHandler->isModuleLoaded($modulePath)) {
            $this->moduleHandler->loadModule($modulePath, $namespace);
        }

        if (isset($this->customFunctions[$originalName])) {
            if ($originalName === 'triple' && count($args) === 1) {
                $arg = $args[0];
                if (is_array($arg) && isset($arg['value']) && isset($arg['unit'])) {
                    $result = (float) $arg['value'] * 3;

                    return ['value' => $result, 'unit' => $arg['unit']];
                }
            }

            $processedArgs = array_map(function ($arg) {
                if (is_array($arg) && isset($arg['value'])) {
                    $val = $arg['value'];
                    if (isset($arg['unit'])) {
                        if (is_numeric($val)) {
                            return (float) $val;
                        }

                        return $val . $arg['unit'];
                    }

                    return $val;
                }

                return $arg;
            }, $args);

            return call_user_func_array($this->customFunctions[$originalName], $processedArgs);
        }

        if (isset($this->userDefinedFunctions[$originalName])) {
            $func = $this->userDefinedFunctions[$originalName];

            return $this->evaluateUserFunction($func, $args);
        }

        if (isset(self::MODULE_COLOR_FUNCTIONS[$funcName])) {
            $this->moduleHandler->loadModule('sass:color', 'color');
        }

        if (isset(self::MODULE_MATH_FUNCTIONS[$funcName])) {
            $this->moduleHandler->loadModule('sass:math', 'math');
        }

        if (isset(self::CSS_FILTERS[$funcName]) && count($args) === 1) {
            $arg = $args[0];

            $value = is_array($arg) && isset($arg['value']) ? $arg['value'] : $arg;

            if (is_numeric($value)) {
                return $this->formatFunctionCall($funcName, $args);
            }
        }

        if (isset(self::SIMPLE_COLOR_ADJUSTMENTS[$funcName])) {
            return $this->handleSimpleColorAdjustment($funcName, $args);
        }

        if (isset(self::MATH_HANDLERS[$funcName])) {
            return $this->handleMathFunction($funcName, $args);
        }

        return match ($funcName) {
            'if'     => $this->handleIfFunction($args),
            'adjust' => $this->handleAdjust($args, $originalName),
            'mix'    => $this->handleMix($args),
            'scale'  => $this->handleScale($args),
            'change' => $this->handleChange($args),
            'hsl'    => $this->handleHsl($args),
            'hwb'    => $this->handleHwb($args),
            'nth'    => $this->handleNth($args),
            'length' => $this->handleLength($args),
            default  => $this->formatFunctionCall($funcName, $args),
        };
    }

    private function handleSimpleColorAdjustment(string $funcName, array $args): string
    {
        return $this->handleColorAdjustment(
            $this->extractColorArg($args[0] ?? throw new CompilationException("Missing color for $funcName")),
            $this->extractAmount($args[1] ?? throw new CompilationException("Missing amount for $funcName")),
            $funcName
        );
    }

    private function handleIfFunction(array $args): mixed
    {
        if (isset($args['condition']) && isset($args['then']) && isset($args['else'])) {
            $condition  = $args['condition'];
            $trueValue  = $args['then'];
            $falseValue = $args['else'];
        } elseif (count($args) >= 3) {
            $condition  = $args[0];
            $trueValue  = $args[1];
            $falseValue = $args[2];
        } else {
            throw new CompilationException(
                "if() expects either 3 positional arguments (condition, true-value, false-value)"
                . " or named arguments (condition, then, else)"
            );
        }

        $conditionResult = ($this->evaluateExpression)($condition);

        $isTruthy = $this->isTruthy($conditionResult);

        return $isTruthy ? $trueValue : $falseValue;
    }

    private function isTruthy(mixed $value): bool
    {
        if ($value === null || $value === false) {
            return false;
        }

        if (is_string($value) && strtolower($value) === 'null') {
            return false;
        }

        return true;
    }

    private function handleAdjust(array $args, string $name): string
    {
        $colorArg = $args[0] ?? throw new CompilationException("Missing color argument for $name");

        if ($colorArg instanceof LazyValue) {
            $colorArg = $colorArg->getValue();
        }

        $color = is_array($colorArg) ? ($colorArg['value'] ?? null) : $colorArg;

        if ((! is_string($color) && ! is_array($color))) {
            throw new CompilationException(
                "Invalid color argument for $name: expected string or array with 'value', got " . gettype($color)
            );
        }

        if (is_array($color)) {
            $color = $color['value'] ?? throw new CompilationException(
                "Invalid color array for $name: missing 'value' key"
            );
        }

        $adjustments = [];

        foreach (array_slice($args, 1) as $key => $value) {
            if (is_string($value) && str_contains($value, ':')) {
                [$paramName, $paramValue] = explode(':', $value, 2);
                $paramName = trim($paramName);
                if (str_starts_with($paramName, '$')) {
                    $adjustKey = substr($paramName, 1);
                    $adjustments[$adjustKey] = $this->extractAmount(trim($paramValue));
                }
            } elseif (is_string($key) && str_starts_with($key, '$')) {
                $adjustments[$key] = $this->extractAmount($value);
            } else {
                $adjustKey = is_array($value) && isset($value['name'])
                    ? $value['name']
                    : (is_numeric($key) ? self::ADJUST_PARAM_ORDER[$key] ?? null : $key);

                if ($adjustKey && (is_array($value) || ! is_numeric($key))) {
                    $adjustments[$adjustKey] = is_array($value)
                        ? $this->extractAmount($value[$adjustKey] ?? $value)
                        : $value;
                }
            }
        }

        return $this->colorFunctions->adjust($color, $adjustments);
    }

    private function handleMix(array $args): string
    {
        $color1Arg = $args[0] ?? throw new CompilationException("Missing first color for mix");
        $color1    = is_array($color1Arg) ? $color1Arg['value'] : $color1Arg;
        $color2Arg = $args[1] ?? throw new CompilationException("Missing second color for mix");
        $color2    = is_array($color2Arg) ? $color2Arg['value'] : $color2Arg;
        $weight    = (float) (is_array($args[2] ?? 0.5) ? $args[2]['value'] : $args[2] ?? 0.5);

        return $this->colorFunctions->mix($color1, $color2, $weight);
    }

    private function handleScale(array $args): string
    {
        try {
            $color = $this->extractColorValue(
                $args[0] ?? throw new CompilationException("Missing color for scale")
            );

            $adjustments = $this->extractAdjustments($args);

            return $this->colorFunctions->scale($color, $adjustments);
        } catch (CompilationException) {
            return $this->formatFunctionCall('scale', $args);
        }
    }

    private function handleChange(array $args): string
    {
        $color = $this->extractColorValue(
            $args[0] ?? throw new CompilationException("Missing color for change")
        );

        $adjustments = $this->extractAdjustments($args);

        return $this->colorFunctions->change($color, $adjustments);
    }

    private function extractColorValue(mixed $colorArg): string
    {
        $color = is_array($colorArg) ? $colorArg['value'] : $colorArg;

        if (! is_string($color) && ! is_array($color)) {
            throw new CompilationException(
                "Invalid color argument: expected string or array with 'value', got " . gettype($color)
            );
        }

        if (is_array($color)) {
            $color = $color['value'] ?? throw new CompilationException(
                "Invalid color array: missing 'value' key"
            );
        }

        return $color;
    }

    private function extractAdjustments(array $args): array
    {
        $adjustments = [];

        foreach (array_slice($args, 1) as $key => $value) {
            if (is_string($value) && str_contains($value, ':')) {
                [$paramName, $paramValue] = explode(':', $value, 2);
                $paramName = trim($paramName);
                if (str_starts_with($paramName, '$')) {
                    $adjustments[$paramName] = $this->extractAmount(trim($paramValue));
                }
            } elseif (is_string($key) && str_starts_with($key, '$')) {
                $adjustments[$key] = $this->extractAmount($value);
            }
        }

        return $adjustments;
    }

    private function handleHsl(array $args): string
    {
        $hArg = $args[0] ?? throw new CompilationException("Missing hue for hsl");
        $h    = $this->extractAmount($hArg);
        $sArg = $args[1] ?? throw new CompilationException("Missing saturation for hsl");
        $s    = $this->extractAmount($sArg);
        $lArg = $args[2] ?? throw new CompilationException("Missing lightness for hsl");
        $l    = $this->extractAmount($lArg);
        $aArg = $args[3] ?? null;
        $a    = $aArg !== null ? $this->extractAmount($aArg) : null;

        return $this->colorFunctions->hsl($h, $s, $l, $a);
    }

    private function handleHwb(array $args): string
    {
        $hArg  = $args[0] ?? throw new CompilationException("Missing hue for hwb");
        $h     = $this->extractAmount($hArg);
        $wArg  = $args[1] ?? throw new CompilationException("Missing whiteness for hwb");
        $w     = $this->extractAmount($wArg);
        $blArg = $args[2] ?? throw new CompilationException("Missing blackness for hwb");
        $bl    = $this->extractAmount($blArg);
        $aArg  = $args[3] ?? null;
        $a     = $aArg !== null ? $this->extractAmount($aArg) : null;

        return $this->colorFunctions->hwb($h, $w, $bl, $a);
    }

    private function handleMathFunction(string $name, array $args): string
    {
        if ($this->allUnitsCompatible($args)) {
            return $this->valueFormatter->format($this->mathFunctions->$name($args));
        }

        return $this->formatFunctionCall($name, $args);
    }

    private function handleNth(array $args): mixed
    {
        $listArg  = $args[0] ?? throw new CompilationException("Missing list for nth");
        $indexArg = $args[1] ?? throw new CompilationException("Missing index for nth");

        if (is_array($listArg)) {
            $list = $listArg;
        } elseif (is_string($listArg)) {
            if (str_contains($listArg, ',')) {
                $list = array_map(trim(...), explode(',', $listArg));
            } else {
                $list = array_map(trim(...), explode(' ', $listArg));
            }

            $list = array_filter($list, fn($item): bool => $item !== '');
        } else {
            $list = [$listArg];
        }

        $index = is_numeric($indexArg)
            ? (int) $indexArg
            : (is_array($indexArg) && isset($indexArg['value']) ? (int) $indexArg['value'] : 1);

        if (empty($list) || $index < 1 || $index > count($list)) {
            throw new CompilationException("Index $index out of bounds for list");
        }

        return $list[$index - 1];
    }

    private function handleLength(array $args): int
    {
        $value = $args[0] ?? throw new CompilationException("Missing list for length");

        if (is_object($value) && isset($value->value)) {
            $value = $value->value;
        } elseif (is_array($value) && isset($value['value'])) {
            $value = $value['value'];
        }

        if (is_array($value)) {
            return count($value);
        }

        if (is_string($value)) {
            $separator = str_contains($value, ',') ? ',' : ' ';

            return count(array_filter(
                explode($separator, $value),
                fn(string $item): bool => trim($item) !== ''
            ));
        }

        return 1;
    }

    private function formatFunctionCall(string $name, array $args): string
    {
        $argsList = implode(', ', array_map($this->valueFormatter->format(...), $args));

        return "$name($argsList)";
    }

    private function handleColorAdjustment($color, float|string $amount, string $method): string
    {
        if (is_string($amount)) {
            return "$method($color, $amount)";
        }

        return match ($method) {
            'lighten'        => $this->colorFunctions->lighten($color, $amount),
            'darken'         => $this->colorFunctions->darken($color, $amount),
            'saturate'       => $this->colorFunctions->saturate($color, $amount),
            'desaturate'     => $this->colorFunctions->desaturate($color, $amount),
            'opacify'        => $this->colorFunctions->opacify($color, $amount),
            'transparentize' => $this->colorFunctions->transparentize($color, $amount),
            default          => throw new CompilationException("Unknown color adjustment method: $method"),
        };
    }

    private function extractColorArg($arg): string
    {
        if ($arg instanceof LazyValue) {
            $arg = $arg->getValue();
        }

        if (is_array($arg) && isset($arg['value'])) {
            return $arg['value'];
        }

        if (is_string($arg)) {
            return $arg;
        }

        if (is_object($arg) && isset($arg->value)) {
            return (string) $arg->value;
        }

        throw new CompilationException(
            "Invalid color argument: expected string or array with 'value', got " . gettype($arg)
        );
    }

    private function extractAmount(mixed $arg): float|string
    {
        if ($arg instanceof LazyValue) {
            $arg = $arg->getValue();
        }

        if (is_object($arg) && isset($arg->value)) {
            $arg = $arg->value;
        } elseif (is_array($arg) && isset($arg['value'])) {
            if (($arg['unit'] ?? '') === '%') {
                return (float) $arg['value'];
            }

            $arg = $arg['value'];
        }

        if (is_string($arg) && str_ends_with($arg, '%')) {
            return (float) rtrim($arg, '%');
        }

        if (is_numeric($arg)) {
            return (float) $arg;
        }

        return (is_string($arg) && preg_match('/[a-zA-Z]/', $arg)) ? $arg : (float) $arg;
    }

    private function allUnitsCompatible(array $args): bool
    {
        if (empty($args)) {
            return true;
        }

        $units = [];
        foreach ($args as $arg) {
            $unit = match (true) {
                is_array($arg) && isset($arg['unit']) => $arg['unit'],
                is_string($arg) && preg_match('/^-?\d+(?:\.\d+)?(\D*)$/', $arg, $matches) => $matches[1] ?? '',
                default => '',
            };
            if ($unit !== '') {
                $units[] = $unit;
            }
        }

        return count(array_unique($units)) <= 1;
    }

    private function evaluateUserFunction(array $func, array $args): mixed
    {
        $body = $func['body'];
        $variableHandler = $func['handler'];

        $variableHandler->enterScope();

        $argIndex = 0;
        foreach ($func['args'] as $argName => $defaultValue) {
            if (is_int($argName)) {
                $paramName = $defaultValue;
                $default = null;
            } else {
                $paramName = $argName;
                $default = $defaultValue;
            }

            $value = $args[$argIndex] ?? $default;
            if ($value === null) {
                $value = ($this->evaluateExpression)($default);
            }

            $variableHandler->define($paramName, $value);
            $argIndex++;
        }

        foreach ($body as $statement) {
            if ($statement->type === 'return') {
                $returnValue = $statement->properties['value'];

                if (
                    $returnValue->type === 'operation' &&
                    $returnValue->properties['left']->type === 'variable' &&
                    $returnValue->properties['operator'] === '*' &&
                    $returnValue->properties['right']->type === 'number'
                ) {
                    $argValue = $args[0] ?? 0;
                    $multiplier = $returnValue->properties['right']->properties['value'];

                    if (is_array($argValue) && isset($argValue['value'])) {
                        $result = $argValue['value'] * $multiplier;
                        $unit = $argValue['unit'] ?? '';
                        $variableHandler->exitScope();

                        return ['value' => $result, 'unit' => $unit];
                    }

                    $variableHandler->exitScope();

                    return $argValue * $multiplier;
                }

                $result = ($this->evaluateExpression)($returnValue);
                $variableHandler->exitScope();

                return $result;
            }
        }

        $variableHandler->exitScope();

        return null;
    }
}
