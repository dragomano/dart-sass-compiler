<?php

declare(strict_types=1);

namespace DartSass\Modules;

use DartSass\Exceptions\CompilationException;
use DartSass\Utils\ValueFormatter;

use function abs;
use function acos;
use function array_map;
use function asin;
use function atan;
use function atan2;
use function ceil;
use function cos;
use function count;
use function deg2rad;
use function floor;
use function implode;
use function in_array;
use function is_array;
use function is_numeric;
use function log;
use function max;
use function min;
use function mt_getrandmax;
use function mt_rand;
use function round;
use function sin;
use function sprintf;
use function sqrt;
use function tan;

use const M_E;
use const M_PI;

readonly class MathModule
{
    public function __construct(private ValueFormatter $valueFormatter) {}

    public function ceil(array $args): array
    {
        if (count($args) !== 1) {
            throw new CompilationException('ceil() requires exactly one argument');
        }

        $val = $this->normalize($args[0]);

        if ($val === null) {
            throw new CompilationException('ceil() argument must be a number');
        }

        return ['value' => ceil($val['value']), 'unit' => $val['unit']];
    }

    public function clamp(array $args): string|array
    {
        if (count($args) !== 3) {
            throw new CompilationException('clamp() requires exactly three arguments');
        }

        $v1 = $this->normalize($args[0]);
        $v2 = $this->normalize($args[1]);
        $v3 = $this->normalize($args[2]);

        if (
            $v1 && $v2 && $v3 &&
            $this->areUnitsCompatible($v1['unit'], $v2['unit']) &&
            $this->areUnitsCompatible($v1['unit'], $v3['unit'])
        ) {
            $val1 = $v1['value'];
            $val2 = $v2['value'];
            $val3 = $v3['value'];

            if ($v1['unit'] === $v2['unit'] && $v2['unit'] === $v3['unit']) {
                $clamped = min(max($val1, $val2), $val3);

                return ['value' => $clamped, 'unit' => $v1['unit']];
            }
        }

        return 'clamp(' . implode(', ', array_map($this->formatArg(...), $args)) . ')';
    }

    public function floor(array $args): array
    {
        if (count($args) !== 1) {
            throw new CompilationException('floor() requires exactly one argument');
        }

        $val = $this->normalize($args[0]);

        if ($val === null) {
            throw new CompilationException('floor() argument must be a number');
        }

        return ['value' => floor($val['value']), 'unit' => $val['unit']];
    }

    public function max(array $args): string|array
    {
        return $this->handleMinMax($args, 'max');
    }

    public function min(array $args): string|array
    {
        return $this->handleMinMax($args, 'min');
    }

    public function round(array $args): array
    {
        if (count($args) < 1 || count($args) > 2) {
            throw new CompilationException('round() requires one or two arguments');
        }

        $val = $this->normalize($args[0]);

        if ($val === null) {
            throw new CompilationException('round() argument must be a number');
        }

        $precision = 0;
        if (isset($args[1])) {
            $pVal = $this->normalize($args[1]);
            if ($pVal) {
                $precision = (int) $pVal['value'];
            }
        }

        return ['value' => round($val['value'], $precision), 'unit' => $val['unit']];
    }

    public function abs(array $args): array
    {
        if (count($args) !== 1) {
            throw new CompilationException('abs() requires exactly one argument');
        }

        $val = $this->normalize($args[0]);

        if ($val === null) {
            throw new CompilationException('abs() argument must be a number');
        }

        return ['value' => abs($val['value']), 'unit' => $val['unit']];
    }

    public function hypot(array $args): array
    {
        if (empty($args)) {
            throw new CompilationException('hypot() requires at least one argument');
        }

        $sumOfSquares = 0.0;
        $unit = '';

        foreach ($args as $arg) {
            $val = $this->normalize($arg);
            if ($val === null) {
                throw new CompilationException('hypot() argument must be a number');
            }

            $sumOfSquares += $val['value'] ** 2;

            if ($unit === '' && $val['unit'] !== '') {
                $unit = $val['unit'];
            } elseif ($val['unit'] !== '' && $val['unit'] !== $unit) {
                throw new CompilationException('arguments must have the same unit');
            }
        }

        return ['value' => sqrt($sumOfSquares), 'unit' => $unit];
    }

    public function log(array $args): array
    {
        if (empty($args) || count($args) > 2) {
            throw new CompilationException('log() requires one or two arguments');
        }

        $number = $this->normalize($args[0]);
        if ($number === null) {
            throw new CompilationException('log() first argument must be a number');
        }

        if ($number['unit'] !== '') {
            throw new CompilationException('log() arguments must be unitless');
        }

        $base = M_E; // Natural logarithm by default
        if (isset($args[1])) {
            $baseVal = $this->normalize($args[1]);
            if ($baseVal === null) {
                throw new CompilationException('log() second argument must be a number');
            }

            if ($baseVal['unit'] !== '') {
                throw new CompilationException('log() arguments must be unitless');
            }

            $base = $baseVal['value'];
        }

        if ($number['value'] <= 0) {
            throw new CompilationException('log() first argument must be greater than zero');
        }

        if ($base <= 0 || $base == 1) {
            throw new CompilationException('log() base must be greater than zero and not equal to one');
        }

        return ['value' => log($number['value'], $base), 'unit' => ''];
    }

    public function pow(array $args): array
    {
        if (count($args) !== 2) {
            throw new CompilationException('pow() requires exactly two arguments');
        }

        $base = $this->normalize($args[0]);
        $exponent = $this->normalize($args[1]);

        if ($base === null) {
            throw new CompilationException('pow() first argument must be a number');
        }

        if ($exponent === null) {
            throw new CompilationException('pow() second argument must be a number');
        }

        if ($base['unit'] !== '' || $exponent['unit'] !== '') {
            throw new CompilationException('pow() arguments must be unitless');
        }

        return ['value' => $base['value'] ** $exponent['value'], 'unit' => ''];
    }

    public function sqrt(array $args): array
    {
        if (count($args) !== 1) {
            throw new CompilationException('sqrt() requires exactly one argument');
        }

        $val = $this->normalize($args[0]);
        if ($val === null) {
            throw new CompilationException('sqrt() argument must be a number');
        }

        if ($val['unit'] !== '') {
            throw new CompilationException('sqrt() argument must be unitless');
        }

        if ($val['value'] < 0) {
            throw new CompilationException('sqrt() argument must be non-negative');
        }

        return ['value' => sqrt($val['value']), 'unit' => ''];
    }

    public function cos(array $args): array
    {
        if (count($args) !== 1) {
            throw new CompilationException('cos() requires exactly one argument');
        }

        $val = $this->normalize($args[0]);
        if ($val === null) {
            throw new CompilationException('cos() argument must be a number');
        }

        $this->validateAngleUnit($val, 'cos');

        $value = $val['value'];
        if ($val['unit'] === 'deg') {
            $value = deg2rad($value);
        }

        return ['value' => cos($value), 'unit' => ''];
    }

    public function sin(array $args): array
    {
        if (count($args) !== 1) {
            throw new CompilationException('sin() requires exactly one argument');
        }

        $val = $this->normalize($args[0]);
        if ($val === null) {
            throw new CompilationException('sin() argument must be a number');
        }

        $this->validateAngleUnit($val, 'sin');

        $value = $val['value'];
        if ($val['unit'] === 'deg') {
            $value = deg2rad($value);
        }

        return ['value' => sin($value), 'unit' => ''];
    }

    public function tan(array $args): array
    {
        if (count($args) !== 1) {
            throw new CompilationException('tan() requires exactly one argument');
        }

        $val = $this->normalize($args[0]);
        if ($val === null) {
            throw new CompilationException('tan() argument must be a number');
        }

        $this->validateAngleUnit($val, 'tan');

        $value = $val['value'];
        if ($val['unit'] === 'deg') {
            $value = deg2rad($value);
        }

        return ['value' => tan($value), 'unit' => ''];
    }

    public function acos(array $args): array
    {
        if (count($args) !== 1) {
            throw new CompilationException('acos() requires exactly one argument');
        }

        $val = $this->normalize($args[0]);
        if ($val === null) {
            throw new CompilationException('acos() argument must be a number');
        }

        if ($val['unit'] !== '') {
            throw new CompilationException('acos() argument must be unitless');
        }

        if ($val['value'] < -1 || $val['value'] > 1) {
            throw new CompilationException('acos() argument must be between -1 and 1');
        }

        return ['value' => acos($val['value']) * 180 / M_PI, 'unit' => 'deg'];
    }

    public function asin(array $args): array
    {
        if (count($args) !== 1) {
            throw new CompilationException('asin() requires exactly one argument');
        }

        $val = $this->normalize($args[0]);
        if ($val === null) {
            throw new CompilationException('asin() argument must be a number');
        }

        if ($val['unit'] !== '') {
            throw new CompilationException('asin() argument must be unitless');
        }

        if ($val['value'] < -1 || $val['value'] > 1) {
            throw new CompilationException('asin() argument must be between -1 and 1');
        }

        return ['value' => asin($val['value']) * 180 / M_PI, 'unit' => 'deg'];
    }

    public function atan(array $args): array
    {
        if (count($args) !== 1) {
            throw new CompilationException('atan() requires exactly one argument');
        }

        $val = $this->normalize($args[0]);
        if ($val === null) {
            throw new CompilationException('atan() argument must be a number');
        }

        if ($val['unit'] !== '') {
            throw new CompilationException('atan() argument must be unitless');
        }

        return ['value' => atan($val['value']) * 180 / M_PI, 'unit' => 'deg'];
    }

    public function atan2(array $args): array
    {
        if (count($args) !== 2) {
            throw new CompilationException('atan2() requires exactly two arguments');
        }

        $y = $this->normalize($args[0]);
        $x = $this->normalize($args[1]);

        if ($y === null) {
            throw new CompilationException('atan2() first argument must be a number');
        }

        if ($x === null) {
            throw new CompilationException('atan2() second argument must be a number');
        }

        if ($y['unit'] !== '' || $x['unit'] !== '') {
            throw new CompilationException('atan2() arguments must be unitless');
        }

        return ['value' => atan2($y['value'], $x['value']) * 180 / M_PI, 'unit' => 'deg'];
    }

    public function compatible(array $args): array
    {
        if (count($args) !== 2) {
            throw new CompilationException('compatible() requires exactly two arguments');
        }

        $val1 = $this->normalize($args[0]);
        $val2 = $this->normalize($args[1]);

        if ($val1 === null || $val2 === null) {
            throw new CompilationException('compatible() arguments must be numbers');
        }

        return ['value' => $this->areUnitsCompatible($val1['unit'], $val2['unit']) ? 'true' : 'false', 'unit' => ''];
    }

    public function isUnitless(array $args): array
    {
        if (count($args) !== 1) {
            throw new CompilationException('isUnitless() requires exactly one argument');
        }

        $val = $this->normalize($args[0]);
        if ($val === null) {
            throw new CompilationException('isUnitless() argument must be a number');
        }

        // Return string 'true'/'false'
        return ['value' => ($val['unit'] === '') ? 'true' : 'false', 'unit' => ''];
    }

    public function unit(array $args): array
    {
        if (count($args) !== 1) {
            throw new CompilationException('unit() requires exactly one argument');
        }

        $val = $this->normalize($args[0]);
        if ($val === null) {
            throw new CompilationException('unit() argument must be a number');
        }

        // Return the unit wrapped in quotes as a string value
        return ['value' => '"' . $val['unit'] . '"', 'unit' => ''];
    }

    public function div(array $args): array
    {
        if (count($args) !== 2) {
            throw new CompilationException('div() requires exactly two arguments');
        }

        $dividend = $this->normalize($args[0]);
        $divisor  = $this->normalize($args[1]);

        if ($dividend === null) {
            throw new CompilationException('div() first argument must be a number');
        }

        if ($divisor === null) {
            throw new CompilationException('div() second argument must be a number');
        }

        if ($divisor['value'] == 0) {
            throw new CompilationException('div() second argument must not be zero');
        }

        // Handle unit division
        $resultUnit = '';
        if ($dividend['unit'] !== '' && $divisor['unit'] !== '') {
            if ($dividend['unit'] !== $divisor['unit']) {
                $resultUnit = $dividend['unit'] . '/' . $divisor['unit'];
            }
        } elseif ($dividend['unit'] !== '') {
            $resultUnit = $dividend['unit'];
        } elseif ($divisor['unit'] !== '') {
            $resultUnit = '/' . $divisor['unit'];
        }

        return ['value' => $dividend['value'] / $divisor['value'], 'unit' => $resultUnit];
    }

    public function percentage(array $args): array
    {
        if (count($args) !== 1) {
            throw new CompilationException('percentage() requires exactly one argument');
        }

        $val = $this->normalize($args[0]);
        if ($val === null) {
            throw new CompilationException('percentage() argument must be a number');
        }

        if ($val['unit'] !== '') {
            throw new CompilationException('percentage() argument must be unitless');
        }

        return ['value' => $val['value'] * 100, 'unit' => '%'];
    }

    public function random(array $args): array
    {
        if (count($args) > 1) {
            throw new CompilationException('random() requires zero or one argument');
        }

        // If no arguments, return a decimal between 0 and 1
        if (empty($args)) {
            return ['value' => mt_rand() / mt_getrandmax(), 'unit' => ''];
        }

        $val = $this->normalize($args[0]);
        if ($val === null) {
            throw new CompilationException('random() argument must be a number');
        }

        if ($val['unit'] !== '') {
            throw new CompilationException('random() argument must be unitless');
        }

        $limit = (int) $val['value'];
        if ($limit <= 0) {
            throw new CompilationException('random() argument must be greater than zero');
        }

        return ['value' => mt_rand(0, $limit - 1), 'unit' => ''];
    }

    public function calc(array $args): string
    {
        if (count($args) === 1) {
            $arg = $args[0];

            if (is_array($arg) && isset($arg['value'], $arg['unit'])) {
                return $arg['value'] . $arg['unit'];
            }

            if (is_numeric($arg)) {
                return (string) $arg;
            }

            return 'calc(' . $this->formatArg($arg) . ')';
        }

        $content = implode(', ', array_map($this->formatArg(...), $args));

        return "calc($content)";
    }

    private function normalize(mixed $item): ?array
    {
        // Handle numeric values
        if (is_numeric($item)) {
            return ['value' => (float) $item, 'unit' => ''];
        }

        // Handle array values with value/unit structure
        if (is_array($item) && isset($item['value'])) {
            return ['value' => (float) $item['value'], 'unit' => $item['unit'] ?? ''];
        }

        // Handle AST NumberNode objects
        if (is_object($item)) {
            // Handle NumberNode from AST
            if (str_ends_with($item::class, 'NumberNode')) {
                return ['value' => (float) $item->value, 'unit' => $item->unit ?? ''];
            }

            // Handle other AST nodes that might have numeric values
            if (property_exists($item, 'value') && is_numeric($item->value)) {
                $unit = property_exists($item, 'unit') ? $item->unit : '';

                return ['value' => (float) $item->value, 'unit' => $unit];
            }

            // Handle AST nodes with properties array
            if (property_exists($item, 'properties') && is_array($item->properties)) {
                $props = $item->properties;
                if (isset($props['value']) && is_numeric($props['value'])) {
                    $unit = $props['unit'] ?? '';

                    return ['value' => (float) $props['value'], 'unit' => $unit];
                }
            }
        }

        return null;
    }

    private function areUnitsCompatible(string $u1, string $u2): bool
    {
        if ($u1 === $u2) {
            return true;
        }

        if ($u1 === '' || $u2 === '') {
            return true;
        }

        return false;
    }

    private function handleMinMax(array $args, string $type): string|array
    {
        if (empty($args)) {
            throw new CompilationException("$type() requires at least one argument");
        }

        $normalized = [];

        foreach ($args as $arg) {
            $norm = $this->normalize($arg);

            if ($norm === null) {
                return $this->createCssFunction($type, $args);
            }

            $normalized[] = $norm;
        }

        $first = $normalized[0];
        $resultUnit = $first['unit'];
        $values = [];

        foreach ($normalized as $norm) {
            if (! $this->areUnitsCompatible($resultUnit, $norm['unit'])) {
                return $this->createCssFunction($type, $args);
            }

            if ($resultUnit === '' && $norm['unit'] !== '') {
                $resultUnit = $norm['unit'];
            }

            $values[] = $norm['value'];
        }

        $allHaveUnits = true;
        foreach ($normalized as $norm) {
            if ($norm['unit'] === '') {
                $allHaveUnits = false;
                break;
            }
        }

        $unit = $allHaveUnits ? $resultUnit : '';
        $result = ($type === 'min') ? min($values) : max($values);

        return ['value' => $result, 'unit' => $unit];
    }

    private function createCssFunction(string $name, array $args): string
    {
        $params = implode(', ', array_map($this->formatArg(...), $args));

        return "$name($params)";
    }

    private function formatArg(mixed $arg): string
    {
        return $this->valueFormatter->format($arg);
    }

    private function validateAngleUnit(array $val, string $functionName): void
    {
        if (in_array($val['unit'], ['', 'rad', 'deg'], true)) {
            return;
        }

        throw new CompilationException(
            sprintf('%s() argument must be unitless, or have rad or deg units', $functionName)
        );
    }
}
