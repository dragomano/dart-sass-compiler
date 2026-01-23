<?php

declare(strict_types=1);

namespace DartSass\Modules;

use DartSass\Exceptions\CompilationException;
use DartSass\Values\SassNumber;

use function abs;
use function acos;
use function asin;
use function atan;
use function atan2;
use function ceil;
use function cos;
use function deg2rad;
use function floor;
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

class MathModule extends AbstractModule
{
    public function ceil(array $args): SassNumber
    {
        [$val] = $this->validateArgs($args, 1, 'ceil');

        $val = $this->normalize($val);

        if ($val === null) {
            throw new CompilationException('ceil() argument must be a number');
        }

        return new SassNumber(ceil($val['value']), $val['unit']);
    }

    public function clamp(array $args): SassNumber|array
    {
        [$arg1, $arg2, $arg3] = $this->validateArgs($args, 3, 'clamp');

        $v1 = $this->normalize($arg1);
        $v2 = $this->normalize($arg2);
        $v3 = $this->normalize($arg3);

        if (
            $v1 && $v2 && $v3
            && $this->areUnitsCompatible($v1['unit'], $v2['unit'])
            && $this->areUnitsCompatible($v1['unit'], $v3['unit'])
        ) {
            $val1 = $v1['value'];
            $val2 = $v2['value'];
            $val3 = $v3['value'];

            if ($v1['unit'] === $v2['unit'] && $v2['unit'] === $v3['unit']) {
                $clamped = min(max($val1, $val2), $val3);

                return new SassNumber($clamped, $v1['unit']);
            }
        }

        return ['css', $args];
    }

    public function floor(array $args): SassNumber
    {
        [$val] = $this->validateArgs($args, 1, 'floor');

        $val = $this->normalize($val);

        if ($val === null) {
            throw new CompilationException('floor() argument must be a number');
        }

        return new SassNumber(floor($val['value']), $val['unit']);
    }

    public function max(array $args): SassNumber|array
    {
        return $this->handleMinMax($args, 'max');
    }

    public function min(array $args): SassNumber|array
    {
        return $this->handleMinMax($args, 'min');
    }

    public function round(array $args): SassNumber
    {
        $this->validateArgRange($args, 1, 2, 'round');

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

        return new SassNumber(round($val['value'], $precision), $val['unit']);
    }

    public function abs(array $args): SassNumber
    {
        [$val] = $this->validateArgs($args, 1, 'abs');

        $val = $this->normalize($val);

        if ($val === null) {
            throw new CompilationException('abs() argument must be a number');
        }

        return new SassNumber(abs($val['value']), $val['unit']);
    }

    public function hypot(array $args): SassNumber
    {
        $this->validateArgs($args, 1, 'hypot', true);

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

        return new SassNumber(sqrt($sumOfSquares), $unit);
    }

    public function log(array $args): SassNumber
    {
        $this->validateArgRange($args, 1, 2, 'log');

        $number = $this->normalize($args[0]);

        if ($number === null) {
            throw new CompilationException('log() first argument must be a number');
        }

        if ($number['unit'] !== '') {
            throw new CompilationException('log() arguments must be unitless');
        }

        $base = M_E;

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

        return new SassNumber(log($number['value'], $base), '');
    }

    public function pow(array $args): SassNumber
    {
        [$base, $exponent] = $this->validateArgs($args, 2, 'pow');

        $base     = $this->normalize($base);
        $exponent = $this->normalize($exponent);

        if ($base === null) {
            throw new CompilationException('pow() first argument must be a number');
        }

        if ($exponent === null) {
            throw new CompilationException('pow() second argument must be a number');
        }

        if ($base['unit'] !== '' || $exponent['unit'] !== '') {
            throw new CompilationException('pow() arguments must be unitless');
        }

        return new SassNumber($base['value'] ** $exponent['value'], '');
    }

    public function sqrt(array $args): SassNumber
    {
        [$val] = $this->validateArgs($args, 1, 'sqrt');

        $val = $this->normalize($val);

        if ($val === null) {
            throw new CompilationException('sqrt() argument must be a number');
        }

        if ($val['unit'] !== '') {
            throw new CompilationException('sqrt() argument must be unitless');
        }

        if ($val['value'] < 0) {
            throw new CompilationException('sqrt() argument must be non-negative');
        }

        return new SassNumber(sqrt($val['value']), '');
    }

    public function cos(array $args): SassNumber
    {
        [$val] = $this->validateArgs($args, 1, 'cos');

        $val = $this->normalize($val);

        if ($val === null) {
            throw new CompilationException('cos() argument must be a number');
        }

        $this->validateAngleUnit($val, 'cos');

        $value = $val['value'];

        if ($val['unit'] === 'deg') {
            $value = deg2rad($value);
        }

        return new SassNumber(cos($value), null);
    }

    public function sin(array $args): SassNumber
    {
        [$val] = $this->validateArgs($args, 1, 'sin');

        $val = $this->normalize($val);

        if ($val === null) {
            throw new CompilationException('sin() argument must be a number');
        }

        $this->validateAngleUnit($val, 'sin');

        $value = $val['value'];

        if ($val['unit'] === 'deg') {
            $value = deg2rad($value);
        }

        return new SassNumber(sin($value), null);
    }

    public function tan(array $args): SassNumber
    {
        [$val] = $this->validateArgs($args, 1, 'tan');

        $val = $this->normalize($val);

        if ($val === null) {
            throw new CompilationException('tan() argument must be a number');
        }

        $this->validateAngleUnit($val, 'tan');

        $value = $val['value'];

        if ($val['unit'] === 'deg') {
            $value = deg2rad($value);
        }

        return new SassNumber(tan($value), null);
    }

    public function acos(array $args): SassNumber
    {
        [$val] = $this->validateArgs($args, 1, 'acos');

        $val = $this->normalize($val);

        if ($val === null) {
            throw new CompilationException('acos() argument must be a number');
        }

        if ($val['unit'] !== '') {
            throw new CompilationException('acos() argument must be unitless');
        }

        if ($val['value'] < -1 || $val['value'] > 1) {
            throw new CompilationException('acos() argument must be between -1 and 1');
        }

        return new SassNumber(acos($val['value']) * 180 / M_PI, 'deg');
    }

    public function asin(array $args): SassNumber
    {
        [$val] = $this->validateArgs($args, 1, 'asin');

        $val = $this->normalize($val);

        if ($val === null) {
            throw new CompilationException('asin() argument must be a number');
        }

        if ($val['unit'] !== '') {
            throw new CompilationException('asin() argument must be unitless');
        }

        if ($val['value'] < -1 || $val['value'] > 1) {
            throw new CompilationException('asin() argument must be between -1 and 1');
        }

        return new SassNumber(asin($val['value']) * 180 / M_PI, 'deg');
    }

    public function atan(array $args): SassNumber
    {
        [$val] = $this->validateArgs($args, 1, 'atan');

        $val = $this->normalize($val);

        if ($val === null) {
            throw new CompilationException('atan() argument must be a number');
        }

        if ($val['unit'] !== '') {
            throw new CompilationException('atan() argument must be unitless');
        }

        return new SassNumber(atan($val['value']) * 180 / M_PI, 'deg');
    }

    public function atan2(array $args): SassNumber
    {
        [$y, $x] = $this->validateArgs($args, 2, 'atan2');

        $y = $this->normalize($y);
        $x = $this->normalize($x);

        if ($y === null) {
            throw new CompilationException('atan2() first argument must be a number');
        }

        if ($x === null) {
            throw new CompilationException('atan2() second argument must be a number');
        }

        if ($y['unit'] !== '' || $x['unit'] !== '') {
            throw new CompilationException('atan2() arguments must be unitless');
        }

        return new SassNumber(atan2($y['value'], $x['value']) * 180 / M_PI, 'deg');
    }

    public function compatible(array $args): array
    {
        [$val1, $val2] = $this->validateArgs($args, 2, 'compatible');

        $val1 = $this->normalize($val1);
        $val2 = $this->normalize($val2);

        if ($val1 === null || $val2 === null) {
            throw new CompilationException('compatible() arguments must be numbers');
        }

        return ['value' => $this->areUnitsCompatible($val1['unit'], $val2['unit']) ? 'true' : 'false', 'unit' => ''];
    }

    public function isUnitless(array $args): array
    {
        [$val] = $this->validateArgs($args, 1, 'is-unitless');

        $val = $this->normalize($val);

        if ($val === null) {
            throw new CompilationException('isUnitless() argument must be a number');
        }

        // Return string 'true'/'false'
        return ['value' => ($val['unit'] === '') ? 'true' : 'false', 'unit' => ''];
    }

    public function unit(array $args): array
    {
        [$val] = $this->validateArgs($args, 1, 'unit');

        $val = $this->normalize($val);

        if ($val === null) {
            throw new CompilationException('unit() argument must be a number');
        }

        // Return the unit wrapped in quotes as a string value
        return ['value' => '"' . $val['unit'] . '"', 'unit' => ''];
    }

    public function div(array $args): SassNumber
    {
        [$dividend, $divisor] = $this->validateArgs($args, 2, 'div');

        $dividend = $this->normalize($dividend);
        $divisor  = $this->normalize($divisor);

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

        return new SassNumber($dividend['value'] / $divisor['value'], $resultUnit);
    }

    public function percentage(array $args): SassNumber
    {
        [$val] = $this->validateArgs($args, 1, 'percentage');

        $val = $this->normalize($val);

        if ($val === null) {
            throw new CompilationException('percentage() argument must be a number');
        }

        if ($val['unit'] !== '') {
            throw new CompilationException('percentage() argument must be unitless');
        }

        return new SassNumber($val['value'] * 100, '%');
    }

    public function random(array $args): SassNumber
    {
        $this->validateArgRange($args, 0, 1, 'random');

        // If no arguments, return a decimal between 0 and 1
        if (empty($args)) {
            return new SassNumber(mt_rand() / mt_getrandmax(), null);
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

        return new SassNumber(mt_rand(0, $limit - 1), null);
    }

    private function normalize(mixed $item): ?array
    {
        if (is_numeric($item)) {
            return ['value' => (float) $item, 'unit' => ''];
        }

        if ($item instanceof SassNumber) {
            return ['value' => $item->getValue(), 'unit' => $item->getUnit() ?? ''];
        }

        if (is_array($item) && isset($item['value'])) {
            return ['value' => (float) $item['value'], 'unit' => $item['unit'] ?? ''];
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

    private function handleMinMax(array $args, string $type): SassNumber|array
    {
        $this->validateArgs($args, 1, $type, true);

        $normalized = [];

        foreach ($args as $arg) {
            $norm = $this->normalize($arg);

            if ($norm === null) {
                return ['css', $args];
            }

            $normalized[] = $norm;
        }

        $first  = $normalized[0];
        $values = [];

        $resultUnit = $first['unit'];

        foreach ($normalized as $norm) {
            if (! $this->areUnitsCompatible($resultUnit, $norm['unit'])) {
                return ['css', $args];
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

        $unit   = $allHaveUnits ? $resultUnit : '';
        $result = ($type === 'min') ? min($values) : max($values);

        return new SassNumber($result, $unit);
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
