<?php

declare(strict_types=1);

namespace DartSass\Handlers;

use DartSass\Exceptions\CompilationException;
use DartSass\Parsers\Nodes\ListNode;
use DartSass\Utils\ColorFunctions;
use DartSass\Utils\LazyValue;
use DartSass\Utils\ListFunctions;
use DartSass\Utils\MathFunctions;
use DartSass\Utils\ValueFormatter;

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
    public const ADJUST_PARAM_ORDER = [
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

    private const MODULE_LIST_FUNCTIONS = [
        'append'       => true,
        'index'        => true,
        'is-bracketed' => true,
        'join'         => true,
        'length'       => true,
        'nth'          => true,
        'separator'    => true,
        'set-nth'      => true,
        'slash'        => true,
        'zip'          => true,
    ];

    private const MODULE_COLOR_FUNCTIONS = [
        'adjust'       => true,
        'change'       => true,
        'channel'      => true,
        'complement'   => true,
        'grayscale'    => true,
        'hwb'          => true,
        'ie-hex-str'   => true,
        'invert'       => true,
        'is-legacy'    => true,
        'is-missing'   => true,
        'is-powerless' => true,
        'mix'          => true,
        'same'         => true,
        'scale'        => true,
        'space'        => true,
        'to-gamut'     => true,
        'to-space'     => true,
    ];

    private const COLOR_GLOBAL_FUNCTIONS = [
        'adjust-color' => true,
        'change-color' => true,
        'complement'   => true,
        'grayscale'    => true,
        'ie-hex-str'   => true,
        'invert'       => true,
        'mix'          => true,
        'scale-color'  => true,
    ];

    private const MODULE_COLOR_LEGACY_FUNCTIONS = [
        'alpha'      => true,
        'blackness'  => true,
        'blue'       => true,
        'green'      => true,
        'hue'        => true,
        'lightness'  => true,
        'red'        => true,
        'saturation' => true,
        'whiteness'  => true,
    ];

    private const COLOR_LEGACY_GLOBAL_FUNCTIONS = [
        'adjust-hue'     => true,
        'alpha'          => true,
        'blackness'      => true,
        'blue'           => true,
        'darken'         => true,
        'desaturate'     => true,
        'fade-in'        => true,
        'fade-out'       => true,
        'green'          => true,
        'hue'            => true,
        'lighten'        => true,
        'lightness'      => true,
        'opacify'        => true,
        'opacity'        => true,
        'red'            => true,
        'saturate'       => true,
        'saturation'     => true,
        'transparentize' => true,
    ];

    private const MODULE_MATH_FUNCTIONS = [
        'abs'         => true,
        'acos'        => true,
        'asin'        => true,
        'atan'        => true,
        'atan2'       => true,
        'calc'        => true,
        'ceil'        => true,
        'clamp'       => true,
        'compatible'  => true,
        'cos'         => true,
        'div'         => true,
        'floor'       => true,
        'hypot'       => true,
        'is-unitless' => true,
        'log'         => true,
        'max'         => true,
        'min'         => true,
        'percentage'  => true,
        'pow'         => true,
        'random'      => true,
        'round'       => true,
        'sin'         => true,
        'sqrt'        => true,
        'tan'         => true,
        'unit'        => true,
    ];

    private const SIMPLE_COLOR_ADJUSTMENTS = [
        'darken'         => true,
        'desaturate'     => true,
        'lighten'        => true,
        'opacify'        => true,
        'saturate'       => true,
        'transparentize' => true,
    ];

    private readonly ColorFunctions $colorFunctions;

    private readonly ListFunctions $listFunctions;

    private readonly MathFunctions $mathFunctions;

    private array $customFunctions = [];

    private array $userDefinedFunctions = [];

    private $evaluateExpression;

    public function __construct(
        private readonly ValueFormatter $valueFormatter,
        private readonly ModuleHandler $moduleHandler,
        callable $evaluateExpression,
    ) {
        $this->colorFunctions     = new ColorFunctions();
        $this->listFunctions      = new ListFunctions();
        $this->mathFunctions      = new MathFunctions($valueFormatter);
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
            && ! isset(self::MODULE_LIST_FUNCTIONS[$funcName])
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

            return $this->handleColorFunction($funcName, $args);
        }

        if (isset(self::COLOR_GLOBAL_FUNCTIONS[$funcName])) {
            return $this->handleColorGlobalFunction($funcName, $args);
        }

        if (isset(self::MODULE_COLOR_LEGACY_FUNCTIONS[$funcName])) {
            return $this->handleColorLegacyFunction($funcName, $args);
        }

        if (isset(self::COLOR_LEGACY_GLOBAL_FUNCTIONS[$funcName])) {
            if (isset(self::SIMPLE_COLOR_ADJUSTMENTS[$funcName])) {
                return $this->handleSimpleColorAdjustment($funcName, $args);
            }

            return $this->handleColorLegacyGlobalFunction($funcName, $args);
        }

        if (isset(self::MODULE_LIST_FUNCTIONS[$funcName])) {
            $this->moduleHandler->loadModule('sass:list', 'list');

            return $this->handleListFunction($funcName, $args);
        }

        if (isset(self::MODULE_MATH_FUNCTIONS[$funcName])) {
            $this->moduleHandler->loadModule('sass:math', 'math');

            return $this->handleMathFunction($funcName, $args);
        }

        return match ($funcName) {
            'if'     => $this->handleIf($args),
            'hsl'    => $this->handleHsl($args),
            'lch'    => $this->handleLch($args),
            'oklch'  => $this->handleOklch($args),
            default  => $this->formatFunctionCall($funcName, $args),
        };
    }

    private function handleSimpleColorAdjustment(string $funcName, array $args): string
    {
        if ($funcName === 'saturate') {
            try {
                $color = $this->requireColor($args, 0, $funcName);
            } catch (CompilationException) {
                // Not a color, treat as CSS saturate() function
                return $this->formatFunctionCall('saturate', $args);
            }
        } else {
            $color = $this->requireColor($args, 0, $funcName);
        }

        return $this->handleColorAdjustment(
            $color,
            $this->requireAmount($args, 1, $funcName),
            $funcName
        );
    }

    private function handleIf(array $args): mixed
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
                'if() expects either 3 positional arguments (condition, true-value, false-value)'
                . ' or named arguments (condition, then, else)'
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
                    // Special handling for $space parameter - keep as string
                    if ($adjustKey === 'space') {
                        $adjustments[$adjustKey] = trim($paramValue);
                    } else {
                        $adjustments[$adjustKey] = $this->extractAmount(trim($paramValue));
                    }
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

    private function handleChange(array $args): string
    {
        $color = $this->requireColor($args, 0, 'change');

        $adjustments = $this->extractAdjustments($args);

        return $this->colorFunctions->change($color, $adjustments);
    }

    private function handleMix(array $args): string
    {
        $color1Arg = $args[0] ?? throw new CompilationException('Missing first color for mix');
        $color1    = is_array($color1Arg) ? $color1Arg['value'] : $color1Arg;
        $color2Arg = $args[1] ?? throw new CompilationException('Missing second color for mix');
        $color2    = is_array($color2Arg) ? $color2Arg['value'] : $color2Arg;
        $weight    = (float) (is_array($args[2] ?? 0.5) ? $args[2]['value'] : $args[2] ?? 0.5);

        return $this->colorFunctions->mix($color1, $color2, $weight);
    }

    private function handleScale(array $args): string
    {
        try {
            $color = $this->requireColor($args, 0, 'scale');

            $adjustments = $this->extractAdjustments($args);

            return $this->colorFunctions->scale($color, $adjustments);
        } catch (CompilationException) {
            return $this->formatFunctionCall('scale', $args);
        }
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
        return $this->colorFunctions->hsl(
            $this->requireAmount($args, 0, 'hsl'),
            $this->requireAmount($args, 1, 'hsl'),
            $this->requireAmount($args, 2, 'hsl'),
            $this->optionalArg($args, 3) !== null ? $this->extractAmount($args[3]) : null
        );
    }

    private function handleHwb(array $args): string
    {
        return $this->colorFunctions->hwb(
            $this->requireAmount($args, 0, 'hwb'),
            $this->requireAmount($args, 1, 'hwb'),
            $this->requireAmount($args, 2, 'hwb'),
            $this->optionalArg($args, 3) !== null ? $this->extractAmount($args[3]) : null
        );
    }

    private function handleLch(array $args): string
    {
        return $this->colorFunctions->lch(
            $this->requireAmount($args, 0, 'lch'),
            $this->requireAmount($args, 1, 'lch'),
            $this->requireAmount($args, 2, 'lch'),
            $this->optionalArg($args, 3) !== null ? $this->extractAmount($args[3]) : null
        );
    }

    private function handleOklch(array $args): string
    {
        return $this->colorFunctions->oklch(
            $this->requireAmount($args, 0, 'oklch'),
            $this->requireAmount($args, 1, 'oklch'),
            $this->requireAmount($args, 2, 'oklch'),
            $this->optionalArg($args, 3) !== null ? $this->extractAmount($args[3]) : null
        );
    }

    private function getNamedParamValue(array $args, int $position, string $paramName): mixed
    {
        return $args[$paramName] ?? $args[$position] ?? null;
    }

    private function handleColorFunction(string $funcName, array $args): string
    {
        return match ($funcName) {
            'adjust' => $this->handleAdjust($args, $funcName),
            'change' => $this->handleChange($args),
            'scale'  => $this->handleScale($args),
            'mix'    => $this->handleMix($args),
            'hwb'    => $this->handleHwb($args),
            'complement',
            'grayscale',
            'ie-hex-str',
            'is-legacy',
            'space' => $this->callColorMethod($funcName, [$this->requireColor($args, 0, $funcName)]),
            'is-missing' => $this->callColorMethod($funcName, [
                $this->requireColor($args, 0, $funcName),
                $this->requireString($args, $funcName),
            ]),
            'same' => $this->callColorMethod($funcName, [
                $this->requireColor($args, 0, $funcName),
                $this->requireColor($args, 1, $funcName),
            ]),
            'channel', 'is-powerless' => $this->callColorMethod($funcName, [
                $this->requireColor($args, 0, $funcName),
                $this->requireString($args, $funcName),
                $this->optionalArg($args, 2),
            ]),
            'invert' => $this->callColorMethod($funcName, [
                $this->requireColor($args, 0, $funcName),
                (int) $this->extractAmount($this->optionalArg($args, 1) ?? 100),
                $this->optionalArg($args, 2),
            ]),
            'to-gamut' => $this->callColorMethod($funcName, [
                $this->requireColor($args, 0, $funcName),
                $this->getNamedParamValue($args, 1, '$space'),
                $this->getNamedParamValue($args, 2, '$method'),
            ]),
            'to-space' => $this->callColorMethod($funcName, [
                $this->requireColor($args, 0, $funcName),
                $this->optionalArg($args, 1),
            ]),
            default => throw new CompilationException("Unknown color function: $funcName")
        };
    }

    private function handleColorGlobalFunction(string $funcName, array $args): string
    {
        return $this->callColorMethod($funcName, [$this->requireColor($args, 0, $funcName)]);
    }

    private function handleColorLegacyFunction(string $funcName, array $args): string
    {
        return $this->callColorMethod($funcName, [$this->requireColor($args, 0, $funcName)]);
    }

    private function handleColorLegacyGlobalFunction(string $funcName, array $args): string
    {
        $twoArgFunctions = [
            'adjust-hue', 'fade-in', 'fade-out', 'lighten', 'darken',
            'saturate', 'desaturate', 'opacify', 'transparentize',
        ];

        if (in_array($funcName, $twoArgFunctions, true)) {
            return $this->callColorMethod(
                $funcName,
                [
                    $this->requireColor($args, 0, $funcName),
                    $this->requireAmount($args, 1, $funcName),
                ]
            );
        }

        if ($funcName === 'opacity') {
            return $this->callColorMethod($funcName, [$this->requireColor($args, 0, $funcName)]);
        }

        throw new CompilationException("Unknown color legacy function: $funcName");
    }

    private function convertFunctionNameToMethodName(string $funcName): string
    {
        return str_replace(' ', '', ucwords(str_replace('-', ' ', $funcName)));
    }

    private function extractStringValue(mixed $arg): string
    {
        if ($arg instanceof LazyValue) {
            $arg = $arg->getValue();
        }

        if (is_array($arg) && isset($arg['value'])) {
            return (string) $arg['value'];
        }

        if (is_string($arg)) {
            return $arg;
        }

        if (is_object($arg) && isset($arg->value)) {
            return (string) $arg->value;
        }

        throw new CompilationException(
            "Invalid string argument: expected string or array with 'value', got " . gettype($arg)
        );
    }

    private function handleListFunction(string $name, array $args): string|array|bool|null
    {
        $functionMapping = [
            'is-bracketed' => 'isBracketed',
            'set-nth'      => 'setNth',
        ];

        $methodName = $functionMapping[$name] ?? $name;

        // For functions that expect a single list argument but receive multiple positional args
        if ($name === 'length' && count($args) > 1 && ! isset($args['$separator'])) {
            $args = [new ListNode($args, 0, 'space')];
        }

        // For append, if multiple positional args without separator, treat first n-1 as list, last as val
        if ($name === 'append' && count($args) > 2 && ! isset($args['$separator'])) {
            $listArgs = array_slice($args, 0, count($args) - 1);
            $val = $args[count($args) - 1];
            $args = [new ListNode($listArgs, 0, 'space'), $val];
        }

        // For join, if multiple positional args without separator, treat first n/2 as list1, second n/2 as list2
        if ($name === 'join' && count($args) > 2 && ! isset($args['$separator'])) {
            $midPoint = intdiv(count($args), 2);
            $list1Args = array_slice($args, 0, $midPoint);
            $list2Args = array_slice($args, $midPoint);
            $args = [new ListNode($list1Args, 0, 'space'), new ListNode($list2Args, 0, 'space')];
        }

        if (isset(self::MODULE_LIST_FUNCTIONS[$name]) || $this->allUnitsCompatible($args)) {
            $evaluatedArgs = array_map($this->evaluateExpression, $args);
            $result = $this->listFunctions->$methodName($evaluatedArgs);

            return match (true) {
                $result === null => null,
                is_array($result) || is_bool($result) => $result,
                default => $this->valueFormatter->format($result),
            };
        }

        return $this->formatFunctionCall($name, $args);
    }

    private function handleMathFunction(string $name, array $args): string
    {
        $functionMapping = [
            'is-unitless' => 'isUnitless',
        ];

        $methodName = $functionMapping[$name] ?? $name;

        $noUnitCheckFunctions = ['compatible', 'is-unitless', 'random'];

        if (in_array($name, $noUnitCheckFunctions, true) || $this->allUnitsCompatible($args)) {
            return $this->valueFormatter->format($this->mathFunctions->$methodName($args));
        }

        return $this->formatFunctionCall($name, $args);
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

    private function extractColorValue(mixed $arg): string
    {
        if ($arg instanceof LazyValue) {
            $arg = $arg->getValue();
        }

        while (is_array($arg) && isset($arg['value'])) {
            $arg = $arg['value'];
        }

        while (is_object($arg) && isset($arg->value)) {
            $arg = $arg->value;
        }

        if (! is_string($arg)) {
            throw new CompilationException(
                'Invalid color argument: expected string, got ' . gettype($arg)
            );
        }

        return $arg;
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
                is_string($arg) && preg_match('/^-?d+(?:\.d+)?(\D*)$/', $arg, $matches) => $matches[1] ?? '',
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
                    $returnValue->type === 'operation'
                    && $returnValue->properties['left']->type === 'variable'
                    && $returnValue->properties['operator'] === '*'
                    && $returnValue->properties['right']->type === 'number'
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

    private function requireColor(array $args, int $index, string $funcName): string
    {
        $arg = $args[$index] ?? throw new CompilationException("Missing color argument for $funcName");

        return $this->extractColorValue($arg);
    }

    private function requireAmount(array $args, int $index, string $funcName): float|string
    {
        $arg = $args[$index] ?? throw new CompilationException("Missing amount argument for $funcName");

        return $this->extractAmount($arg);
    }

    private function requireString(array $args, string $funcName): string
    {
        $arg = $args[1] ?? throw new CompilationException(
            "Missing argument at position 1 for $funcName"
        );

        return $this->extractStringValue($arg);
    }

    private function optionalArg(array $args, int $index): mixed
    {
        return $args[$index] ?? null;
    }

    private function callColorMethod(string $funcName, array $args): string
    {
        $methodName = $this->convertFunctionNameToMethodName($funcName);

        if (! method_exists($this->colorFunctions, $methodName)) {
            throw new CompilationException("Method $methodName does not exist in ColorFunctions");
        }

        return $this->colorFunctions->$methodName(...$args);
    }
}
