<?php

declare(strict_types=1);

namespace DartSass\Utils;

use DartSass\Exceptions\CompilationException;

use function abs;
use function array_map;
use function ceil;
use function count;
use function floor;
use function implode;
use function is_array;
use function is_numeric;
use function max;
use function min;
use function round;

readonly class MathFunctions
{
    public function __construct(private ValueFormatter $valueFormatter) {}

    public function abs(array $args): array
    {
        if (count($args) !== 1) {
            throw new CompilationException('abs() expects exactly one argument');
        }

        $val = $this->normalize($args[0]);

        if ($val === null) {
            throw new CompilationException('abs() argument must be a number');
        }

        return ['value' => abs($val['value']), 'unit' => $val['unit']];
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

    public function min(array $args): string|array
    {
        return $this->handleMinMax($args, 'min');
    }

    public function max(array $args): string|array
    {
        return $this->handleMinMax($args, 'max');
    }

    public function clamp(array $args): string|array
    {
        if (count($args) !== 3) {
            throw new CompilationException('clamp() expects exactly three arguments');
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

    public function ceil(array $args): array
    {
        if (count($args) !== 1) {
            throw new CompilationException('ceil() expects exactly one argument');
        }

        $val = $this->normalize($args[0]);

        if ($val === null) {
            throw new CompilationException('ceil() argument must be a number');
        }

        return ['value' => ceil($val['value']), 'unit' => $val['unit']];
    }

    public function floor(array $args): array
    {
        if (count($args) !== 1) {
            throw new CompilationException('floor() expects exactly one argument');
        }

        $val = $this->normalize($args[0]);

        if ($val === null) {
            throw new CompilationException('floor() argument must be a number');
        }

        return ['value' => floor($val['value']), 'unit' => $val['unit']];
    }

    public function round(array $args): array
    {
        if (count($args) < 1 || count($args) > 2) {
            throw new CompilationException('round() expects one or two arguments');
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

    private function normalize(mixed $item): ?array
    {
        if (is_numeric($item)) {
            return ['value' => (float) $item, 'unit' => ''];
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

    private function formatArg(mixed $arg): string
    {
        return $this->valueFormatter->format($arg);
    }
}
