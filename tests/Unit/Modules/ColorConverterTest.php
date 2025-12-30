<?php

declare(strict_types=1);

use DartSass\Modules\ColorConverter;
use Tests\ReflectionAccessor;

beforeEach(function () {
    $this->accessor = new ReflectionAccessor(ColorConverter::class);
});

describe('ColorConverter RGB ↔ HSL', function () {
    dataset('rgb to hsl', [
        'red' => [[255, 0, 0], ['h' => 0.0, 's' => 100.0, 'l' => 50.0]],
    ]);

    dataset('hsl to rgb', [
        'red' => [[0, 100, 50], ['r' => 255.0, 'g' => 0.0, 'b' => 0.0]],
    ]);

    it('converts RGB to HSL', function (array $rgb, array $expected) {
        [$r, $g, $b] = $rgb;
        $hsl = ColorConverter::RGB->toHsl($r, $g, $b);

        expect($hsl)->toMatchArray($expected);
    })->with('rgb to hsl');

    it('converts HSL to RGB', function (array $hsl, array $expected) {
        [$h, $s, $l] = $hsl;
        $rgb = ColorConverter::HSL->toRgb($h, $s, $l);

        expect($rgb)->toMatchArray($expected);
    })->with('hsl to rgb');
})->covers(ColorConverter::class);

describe('ColorConverter RGB ↔ HWB', function () {
    dataset('rgb to hwb exact', [
        'white'      => [[255, 255, 255], ['h' => 0.0, 'w' => 100.0, 'bl' => 0.0]],
        'pure green' => [[0, 255, 0],     ['h' => 120.0, 'w' => 0.0, 'bl' => 0.0]],
        'pure blue'  => [[0, 0, 255],     ['h' => 240.0, 'w' => 0.0, 'bl' => 0.0]],
    ]);

    dataset('rgb to hwb range', [
        'green dominant mixed' => [[100, 200, 50], ['h' => [90, 140], 'w' => [0, null], 'bl' => [0, null]]],
        'blue dominant mixed'  => [[50, 100, 200], ['h' => [200, 280], 'w' => [0, null], 'bl' => [0, null]]],
    ]);

    dataset('hwb to rgb', [
        'pure white'         => [[0, 100.0, 0],   ['r' => 255.0, 'g' => 255.0, 'b' => 255.0]],
        'pure red (w=0,b=0)' => [[0, 0.0, 0.0],   ['r' => 255.0, 'g' => 0.0, 'b' => 0.0]],
        'full white'         => [[0, 100.0, 0.0], ['r' => 255.0, 'g' => 255.0, 'b' => 255.0]],
        'full black'         => [[0, 0.0, 100.0], ['r' => '<5', 'g' => '<5', 'b' => '<5']],
    ]);

    it('converts RGB to HWB (exact values)', function (array $rgb, array $expected) {
        [$r, $g, $b] = $rgb;
        $hwb = ColorConverter::RGB->toHwb($r, $g, $b);

        expect($hwb)->toMatchArray($expected);
    })->with('rgb to hwb exact');

    it('converts RGB to HWB (range checks)', function (array $rgb, array $expected) {
        [$r, $g, $b] = $rgb;
        $hwb = ColorConverter::RGB->toHwb($r, $g, $b);

        foreach ($expected as $key => $range) {
            if (is_array($range)) {
                [$min, $max] = $range;
                expect($hwb[$key])->toBeGreaterThan($min);
                if ($max !== null) {
                    expect($hwb[$key])->toBeLessThan($max);
                }
            }
        }
    })->with('rgb to hwb range');

    it('converts HWB to RGB', function (array $hwb, array $expected) {
        [$h, $w, $bl] = $hwb;
        $rgb = ColorConverter::HWB->toRgb($h, $w, $bl);

        foreach ($expected as $key => $value) {
            if ($value === '<5') {
                expect($rgb[$key])->toBeLessThan(5);
            } else {
                expect($rgb[$key])->toBe($value);
            }
        }
    })->with('hwb to rgb');
})->covers(ColorConverter::class);

describe('ColorConverter RGB ↔ OKLCH', function () {
    dataset('rgb to oklch exact', [
        'white' => [[255, 255, 255], ['l' => 100.0, 'c' => 0.0]],
        'black' => [[0, 0, 0],       ['l' => 0.0,   'c' => 0.0]],
    ]);

    dataset('rgb to oklch range', [
        'red'   => [[255, 0, 0],     ['l' => [50, 80], 'c' => [0.2, 0.4], 'h' => [20, 40]]],
        'green' => [[0, 255, 0],     ['l' => [80, 95], 'c' => [0.2, 0.4], 'h' => [130, 150]]],
        'blue'  => [[0, 0, 255],     ['l' => [40, 50], 'c' => [0.2, 0.4], 'h' => [250, 280]]],
        'gray'  => [[128, 128, 128], ['l' => [50, 60], 'c' => [0, 0.01]]],
    ]);

    dataset('oklch to rgb', [
        'red'   => [[62.8, 0.2577, 29.2],  ['r' => 255.0, 'g' => 0.0, 'b' => 0.0]],
        'green' => [[86.6, 0.2948, 142.5], ['r' => 0.0, 'g' => 255.0, 'b' => 0.0]],
        'blue'  => [[45.2, 0.313, 264.05], ['r' => 0.0, 'g' => 0.0, 'b' => 255.0]],
        'white' => [[100, 0, 0],           ['r' => 255.0, 'g' => 255.0, 'b' => 255.0]],
        'black' => [[0, 0, 0],             ['r' => 0.0, 'g' => 0.0, 'b' => 0.0]],
        'gray'  => [[53.2, 0, 0],          ['r' => 108.0, 'g' => 108.0, 'b' => 108.0]],
    ]);

    dataset('rgb round trip via oklch', [
        'red'    => [[255, 0, 0]],
        'green'  => [[0, 255, 0]],
        'blue'   => [[0, 0, 255]],
        'white'  => [[255, 255, 255]],
        'black'  => [[0, 0, 0]],
        'gray'   => [[128, 128, 128]],
        'orange' => [[255, 128, 0]],
        'purple' => [[128, 0, 255]],
    ]);

    it('converts RGB to OKLCH (exact)', function (array $rgb, array $expected) {
        [$r, $g, $b] = $rgb;
        $oklch = ColorConverter::RGB->toOklch($r, $g, $b);

        expect($oklch['l'])->toBeCloseTo($expected['l'], 0.1)
            ->and($oklch['c'])->toBeCloseTo($expected['c'], 0.01);
    })->with('rgb to oklch exact');

    it('converts RGB to OKLCH (range)', function (array $rgb, array $expected) {
        [$r, $g, $b] = $rgb;
        $oklch = ColorConverter::RGB->toOklch($r, $g, $b);

        foreach ($expected as $key => [$min, $max]) {
            expect($oklch[$key])->toBeGreaterThan($min);
            if ($max !== null) {
                expect($oklch[$key])->toBeLessThan($max);
            }
        }
    })->with('rgb to oklch range');

    it('converts OKLCH to RGB', function (array $oklch, array $expected) {
        [$l, $c, $h] = $oklch;
        $rgb = ColorConverter::OKLCH->toRgb($l, $c, $h);

        expect($rgb['r'])->toBeCloseTo($expected['r'], 1.0)
            ->and($rgb['g'])->toBeCloseTo($expected['g'], 1.0)
            ->and($rgb['b'])->toBeCloseTo($expected['b'], 1.0);
    })->with('oklch to rgb');

    it('round trips RGB → OKLCH → RGB', function (array $rgb) {
        [$r, $g, $b] = $rgb;
        $oklch = ColorConverter::RGB->toOklch($r, $g, $b);
        $rgbBack = ColorConverter::OKLCH->toRgb($oklch['l'], $oklch['c'], $oklch['h']);

        expect($rgbBack['r'])->toBeCloseTo($r, 5.0)
            ->and($rgbBack['g'])->toBeCloseTo($g, 5.0)
            ->and($rgbBack['b'])->toBeCloseTo($b, 5.0);
    })->with('rgb round trip via oklch');
})->covers(ColorConverter::class);

describe('ColorConverter RGB ↔ LCH', function () {
    dataset('rgb to lch exact', [
        'white' => [[255, 255, 255], ['l' => 100.0, 'c' => 0.0]],
        'black' => [[0, 0, 0],       ['l' => 0.0,   'c' => 0.0]],
    ]);

    dataset('rgb to lch range', [
        'red'   => [[255, 0, 0],     ['l' => [50, 70], 'c' => [100, 150], 'h' => [35, 45]]],
        'green' => [[0, 255, 0],     ['l' => [80, 95], 'c' => [100, 150], 'h' => [130, 140]]],
        'blue'  => [[0, 0, 255],     ['l' => [25, 40], 'c' => [100, 150], 'h' => [280, 320]]],
        'gray'  => [[128, 128, 128], ['l' => [50, 60], 'c' => [0, 0.1]]],
    ]);

    dataset('lch to rgb', [
        'red'   => [[53.24, 104.55, 39.95],  ['r' => 255.0, 'g' => 0.0, 'b' => 0.0]],
        'green' => [[87.73, 119.78, 136.02], ['r' => 0.0, 'g' => 255.0, 'b' => 0.0]],
        'blue'  => [[32.3, 133.81, 306.28],  ['r' => 0.0, 'g' => 0.0, 'b' => 255.0]],
        'white' => [[100, 0, 0],             ['r' => 255.0, 'g' => 255.0, 'b' => 255.0]],
        'black' => [[0, 0, 0],               ['r' => 0.0, 'g' => 0.0, 'b' => 0.0]],
        'gray'  => [[53.59, 0, 0],           ['r' => 128.0, 'g' => 128.0, 'b' => 128.0]],
    ]);

    dataset('rgb round trip via lch', [
        'red'    => [[255, 0, 0]],
        'green'  => [[0, 255, 0]],
        'blue'   => [[0, 0, 255]],
        'white'  => [[255, 255, 255]],
        'black'  => [[0, 0, 0]],
        'gray'   => [[128, 128, 128]],
        'orange' => [[255, 128, 0]],
        'purple' => [[128, 0, 255]],
    ]);

    it('converts RGB to LCH (exact)', function (array $rgb, array $expected) {
        [$r, $g, $b] = $rgb;
        $lch = ColorConverter::RGB->toLch($r, $g, $b);

        expect($lch['l'])->toBeCloseTo($expected['l'], 0.1)
            ->and($lch['c'])->toBeCloseTo($expected['c'], 0.1);
    })->with('rgb to lch exact');

    it('converts RGB to LCH (range)', function (array $rgb, array $expected) {
        [$r, $g, $b] = $rgb;
        $lch = ColorConverter::RGB->toLch($r, $g, $b);

        foreach ($expected as $key => [$min, $max]) {
            expect($lch[$key])->toBeGreaterThan($min);
            if ($max !== null) {
                expect($lch[$key])->toBeLessThan($max);
            }
        }
    })->with('rgb to lch range');

    it('converts LCH to RGB', function (array $lch, array $expected) {
        [$l, $c, $h] = $lch;
        $rgb = ColorConverter::LCH->toRgb($l, $c, $h);

        expect($rgb['r'])->toBeCloseTo($expected['r'], 1.0)
            ->and($rgb['g'])->toBeCloseTo($expected['g'], 1.0)
            ->and($rgb['b'])->toBeCloseTo($expected['b'], 1.0);
    })->with('lch to rgb');

    it('round trips RGB → LCH → RGB', function (array $rgb) {
        [$r, $g, $b] = $rgb;
        $lch = ColorConverter::RGB->toLch($r, $g, $b);
        $rgbBack = ColorConverter::LCH->toRgb($lch['l'], $lch['c'], $lch['h']);

        expect($rgbBack['r'])->toBeCloseTo($r, 5.0)
            ->and($rgbBack['g'])->toBeCloseTo($g, 5.0)
            ->and($rgbBack['b'])->toBeCloseTo($b, 5.0);
    })->with('rgb round trip via lch');
})->covers(ColorConverter::class);

describe('ColorConverter RGB ↔ XYZ', function () {
    dataset('rgb to xyz', [
        'red'   => [[255, 0, 0],     ['x' => 41.24, 'y' => 21.26, 'z' => 1.93]],
        'white' => [[255, 255, 255], ['x' => 95.05, 'y' => 100.0, 'z' => 108.9]],
    ]);

    dataset('xyz to rgb', [
        'red'   => [[41.24, 21.26, 1.93],  ['r' => 255.0, 'g' => 0.0, 'b' => 0.0]],
        'white' => [[95.05, 100.0, 108.9], ['r' => 255.0, 'g' => 255.0, 'b' => 255.0]],
    ]);

    it('converts RGB to XYZ', function (array $rgb, array $expected) {
        [$r, $g, $b] = $rgb;
        $xyz = ColorConverter::RGB->toXyz($r, $g, $b);

        expect($xyz['x'])->toBeCloseTo($expected['x'], 0.01)
            ->and($xyz['y'])->toBeCloseTo($expected['y'], 0.01)
            ->and($xyz['z'])->toBeCloseTo($expected['z'], 0.01);
    })->with('rgb to xyz');

    it('converts XYZ to RGB', function (array $xyz, array $expected) {
        [$x, $y, $z] = $xyz;
        $rgb = ColorConverter::XYZ->toRgb($x, $y, $z);

        expect($rgb['r'])->toBeCloseTo($expected['r'], 1.0)
            ->and($rgb['g'])->toBeCloseTo($expected['g'], 1.0)
            ->and($rgb['b'])->toBeCloseTo($expected['b'], 1.0);
    })->with('xyz to rgb');
})->covers(ColorConverter::class);

describe('ColorConverter RGB ↔ Lab', function () {
    dataset('rgb to lab exact', [
        'white' => [[255, 255, 255], ['lab_l' => 100.0, 'lab_a' => 0.0, 'lab_b' => 0.0]],
        'black' => [[0, 0, 0],       ['lab_l' => 0.0,   'lab_a' => 0.0, 'lab_b' => 0.0]],
    ]);

    dataset('rgb to lab range', [
        'red'   => [[255, 0, 0],     ['lab_l' => [50, 70], 'lab_a' => [75, 85], 'lab_b' => [40, 70]]],
        'green' => [[0, 255, 0],     ['lab_l' => [80, 95], 'lab_a' => [-90, -80], 'lab_b' => [80, 120]]],
        'blue'  => [[0, 0, 255],     ['lab_l' => [25, 40], 'lab_a' => [75, 85], 'lab_b' => [-120, -80]]],
        'gray'  => [[128, 128, 128], ['lab_l' => [50, 60], 'lab_a' => [-1, 1], 'lab_b' => [-1, 1]]],
    ]);

    dataset('lab to rgb', [
        'red'   => [[53.24, 80.09, 67.22],  ['r' => 255.0, 'g' => 0.0, 'b' => 0.0]],
        'green' => [[87.73, -86.18, 83.18], ['r' => 0.0, 'g' => 255.0, 'b' => 0.0]],
        'blue'  => [[32.3, 79.19, -107.86], ['r' => 0.0, 'g' => 0.0, 'b' => 255.0]],
        'white' => [[100, 0, 0],            ['r' => 255.0, 'g' => 255.0, 'b' => 255.0]],
        'black' => [[0, 0, 0],              ['r' => 0.0, 'g' => 0.0, 'b' => 0.0]],
        'gray'  => [[53.59, 0, 0],          ['r' => 128.0, 'g' => 128.0, 'b' => 128.0]],
    ]);

    dataset('rgb round trip via lab', [
        'red'    => [[255, 0, 0]],
        'green'  => [[0, 255, 0]],
        'blue'   => [[0, 0, 255]],
        'white'  => [[255, 255, 255]],
        'black'  => [[0, 0, 0]],
        'gray'   => [[128, 128, 128]],
        'orange' => [[255, 128, 0]],
        'purple' => [[128, 0, 255]],
    ]);

    it('converts RGB to Lab (exact)', function (array $rgb, array $expected) {
        [$r, $g, $b] = $rgb;
        $lab = ColorConverter::RGB->toLab($r, $g, $b);

        expect($lab['lab_l'])->toBeCloseTo($expected['lab_l'], 0.1)
            ->and($lab['lab_a'])->toBeCloseTo($expected['lab_a'], 0.1)
            ->and($lab['lab_b'])->toBeCloseTo($expected['lab_b'], 0.1);
    })->with('rgb to lab exact');

    it('converts RGB to Lab (range)', function (array $rgb, array $expected) {
        [$r, $g, $b] = $rgb;
        $lab = ColorConverter::RGB->toLab($r, $g, $b);

        foreach ($expected as $key => [$min, $max]) {
            expect($lab[$key])->toBeGreaterThan($min)
                ->and($lab[$key])->toBeLessThan($max);
        }
    })->with('rgb to lab range');

    it('converts Lab to RGB', function (array $lab, array $expected) {
        [$l, $a, $b] = $lab;
        $rgb = ColorConverter::LAB->toRgb($l, $a, $b);

        expect($rgb['r'])->toBeCloseTo($expected['r'], 1.0)
            ->and($rgb['g'])->toBeCloseTo($expected['g'], 1.0)
            ->and($rgb['b'])->toBeCloseTo($expected['b'], 1.0);
    })->with('lab to rgb');

    it('round trips RGB → Lab → RGB', function (array $rgb) {
        [$r, $g, $b] = $rgb;
        $lab = ColorConverter::RGB->toLab($r, $g, $b);
        $rgbBack = ColorConverter::LAB->toRgb($lab['lab_l'], $lab['lab_a'], $lab['lab_b']);

        expect($rgbBack['r'])->toBeCloseTo($r, 5.0)
            ->and($rgbBack['g'])->toBeCloseTo($g, 5.0)
            ->and($rgbBack['b'])->toBeCloseTo($b, 5.0);
    })->with('rgb round trip via lab');
})->covers(ColorConverter::class);

describe('ColorConverter HSL ↔ Lab', function () {
    dataset('hsl to lab', [
        'red'   => [[0, 100, 50],    ['lab_l' => [50, 70], 'lab_a' => [75, 85], 'lab_b' => [40, 70]]],
        'green' => [[120, 100, 50],  ['lab_l' => [80, 95], 'lab_a' => [-90, -80], 'lab_b' => [80, 120]]],
        'blue'  => [[240, 100, 50],  ['lab_l' => [25, 40], 'lab_a' => [75, 85], 'lab_b' => [-120, -80]]],
    ]);

    it('converts HSL to Lab', function (array $hsl, array $expected) {
        [$h, $s, $l] = $hsl;
        $lab = ColorConverter::HSL->toLab($h, $s, $l);

        foreach ($expected as $key => [$min, $max]) {
            expect($lab[$key])->toBeGreaterThan($min)
                ->and($lab[$key])->toBeLessThan($max);
        }
    })->with('hsl to lab');
})->covers(ColorConverter::class);

describe('ColorConverter HWB ↔ Lab', function () {
    dataset('hwb to lab', [
        'red'   => [[0, 0, 0],       ['lab_l' => [50, 70], 'lab_a' => [75, 85], 'lab_b' => [40, 70]]],
        'green' => [[120, 0, 0],     ['lab_l' => [80, 95], 'lab_a' => [-90, -80], 'lab_b' => [80, 120]]],
        'blue'  => [[240, 0, 0],     ['lab_l' => [25, 40], 'lab_a' => [75, 85], 'lab_b' => [-120, -80]]],
    ]);

    it('converts HWB to Lab', function (array $hwb, array $expected) {
        [$h, $w, $bl] = $hwb;
        $lab = ColorConverter::HWB->toLab($h, $w, $bl);

        foreach ($expected as $key => [$min, $max]) {
            expect($lab[$key])->toBeGreaterThan($min)
                ->and($lab[$key])->toBeLessThan($max);
        }
    })->with('hwb to lab');
})->covers(ColorConverter::class);

describe('ColorConverter Lab Utility Methods', function () {
    dataset('lab function', [
        'low value'  => [0.001, 'less than epsilon'],
        'medium'     => [0.5, 'greater than epsilon'],
        'high value' => [2.0, 'greater than epsilon'],
    ]);

    dataset('lab inverse function', [
        'low value'  => [0.5, 'less than epsilon'],
        'medium'     => [0.8, 'greater than epsilon'],
        'high value' => [2.0, 'greater than epsilon'],
    ]);

    dataset('linearize channel', [
        'zero'        => [0.0, 0.0],
        'low value'   => [0.04, 0.04 / 12.92],
        'threshold'   => [0.04045, 0.04045 / 12.92],
        'medium'      => [0.5, 0.214],
        'high value'  => [1.0, 1.0],
    ]);

    dataset('unLinearize channel', [
        'zero'        => [0.0, 0.0],
        'low value'   => [0.003, 0.038],
        'medium'      => [0.5, 0.735],
        'high value'  => [1.0, 1.0],
    ]);

    it('clamps values correctly', function () {
        $result = $this->accessor->callMethod('clamp', [-10.0, 0.0]);
        expect($result)->toBe(0.0);

        $result = $this->accessor->callMethod('clamp', [100.0, 0.0]);
        expect($result)->toBe(100.0);

        $result = $this->accessor->callMethod('clamp', [300.0, 0.0]);
        expect($result)->toBe(255.0);

        $result = $this->accessor->callMethod('clamp', [50.0, 0.0]);
        expect($result)->toBe(50.0);
    });

    it('handles lab function with different ranges', function (float $input, string $type) {
        $result = $this->accessor->callMethod('labFunction', [$input]);
        expect($result)->toBeGreaterThan(0);

        if ($type === 'less than epsilon') {
            expect($result)->toBeLessThan(1);
        } else {
            expect($result)->toBeGreaterThan(0.1);
        }
    })->with('lab function');

    it('handles lab inverse function with different ranges', function (float $input, string $type) {
        $result = $this->accessor->callMethod('labInverseFunction', [$input]);

        if ($type === 'less than epsilon') {
            expect($result)->toBeGreaterThan(-1);
        } else {
            expect($result)->toBeGreaterThan(0.1);
        }
    })->with('lab inverse function');

    it('linearizes channels correctly', function (float $input, float $expected) {
        $result = $this->accessor->callMethod('linearizeChannel', [$input]);
        expect($result)->toBeCloseTo($expected, 0.01);
    })->with('linearize channel');

    it('unLinearizes channels correctly', function (float $input, float $expected) {
        $result = $this->accessor->callMethod('unLinearizeChannel', [$input]);
        expect($result)->toBeCloseTo($expected, 0.01);
    })->with('unLinearize channel');
})->covers(ColorConverter::class);

describe('ColorConverter Direct Conversions', function () {
    dataset('hsl to hwb', [
        'red'   => [[0, 100, 50], ['h' => 0.0, 'w' => 0.0, 'bl' => 0.0]],
        'white' => [[0, 0, 100], ['h' => 0.0, 'w' => 100.0, 'bl' => 0.0]],
        'black' => [[0, 0, 0],   ['h' => 0.0, 'w' => 0.0, 'bl' => 100.0]],
    ]);

    dataset('hsl to lch', [
        'red'   => [[0, 100, 50], ['l' => [50, 70], 'c' => [100, 150], 'h' => [35, 45]]],
        'green' => [[120, 100, 50], ['l' => [80, 95], 'c' => [100, 150], 'h' => [130, 140]]],
        'blue'  => [[240, 100, 50], ['l' => [25, 40], 'c' => [100, 150], 'h' => [280, 320]]],
    ]);

    dataset('hsl to oklch', [
        'red'   => [[0, 100, 50], ['l' => [50, 80], 'c' => [0.2, 0.4], 'h' => [20, 40]]],
        'green' => [[120, 100, 50], ['l' => [80, 95], 'c' => [0.2, 0.4], 'h' => [130, 150]]],
        'blue'  => [[240, 100, 50], ['l' => [40, 50], 'c' => [0.2, 0.4], 'h' => [250, 280]]],
    ]);

    dataset('hwb to lch', [
        'red'   => [[0, 0, 0], ['l' => [50, 70], 'c' => [100, 150], 'h' => [35, 45]]],
        'green' => [[120, 0, 0], ['l' => [80, 95], 'c' => [100, 150], 'h' => [130, 140]]],
        'blue'  => [[240, 0, 0], ['l' => [25, 40], 'c' => [100, 150], 'h' => [280, 320]]],
    ]);

    dataset('hwb to oklch', [
        'red'   => [[0, 0, 0], ['l' => [50, 80], 'c' => [0.2, 0.4], 'h' => [20, 40]]],
        'green' => [[120, 0, 0], ['l' => [80, 95], 'c' => [0.2, 0.4], 'h' => [130, 150]]],
        'blue'  => [[240, 0, 0], ['l' => [40, 50], 'c' => [0.2, 0.4], 'h' => [250, 280]]],
    ]);

    dataset('xyz to hsl', [
        'red'   => [[41.24, 21.26, 1.93], ['h' => [0, 30], 's' => [80, 100], 'l' => [40, 60]]],
        'green' => [[35.76, 71.52, 7.22], ['h' => [90, 140], 's' => [80, 100], 'l' => [40, 101]]],
        'blue'  => [[18.05, 7.22, 95.05], ['h' => [200, 260], 's' => [80, 100], 'l' => [40, 101]]],
    ]);

    dataset('xyz to hwb', [
        'red'   => [[41.24, 21.26, 1.93], ['h' => [0, 30], 'w' => [0, 15], 'bl' => [0, 15]]],
        'white' => [[95.05, 100.0, 108.9], ['h' => [-1, 20], 'w' => [90, 100], 'bl' => [0, 10]]],
    ]);

    it('converts HSL to HWB directly', function (array $hsl, array $expected) {
        [$h, $s, $l] = $hsl;
        $hwb = ColorConverter::HSL->toHwb($h, $s, $l);

        foreach ($expected as $key => $value) {
            if (is_array($value)) {
                [$min, $max] = $value;
                expect($hwb[$key])->toBeGreaterThan($min);
                if ($max !== null) {
                    expect($hwb[$key])->toBeLessThan($max);
                }
            } else {
                expect($hwb[$key])->toBe($value);
            }
        }
    })->with('hsl to hwb');

    it('converts HSL to LCH directly', function (array $hsl, array $expected) {
        [$h, $s, $l] = $hsl;
        $lch = ColorConverter::HSL->toLch($h, $s, $l);

        foreach ($expected as $key => [$min, $max]) {
            expect($lch[$key])->toBeGreaterThan($min)
                ->and($lch[$key])->toBeLessThan($max);
        }
    })->with('hsl to lch');

    it('converts HSL to OKLCH directly', function (array $hsl, array $expected) {
        [$h, $s, $l] = $hsl;
        $oklch = ColorConverter::HSL->toOklch($h, $s, $l);

        foreach ($expected as $key => [$min, $max]) {
            expect($oklch[$key])->toBeGreaterThan($min)
                ->and($oklch[$key])->toBeLessThan($max);
        }
    })->with('hsl to oklch');

    it('converts HWB to LCH directly', function (array $hwb, array $expected) {
        [$h, $w, $bl] = $hwb;
        $lch = ColorConverter::HWB->toLch($h, $w, $bl);

        foreach ($expected as $key => [$min, $max]) {
            expect($lch[$key])->toBeGreaterThan($min)
                ->and($lch[$key])->toBeLessThan($max);
        }
    })->with('hwb to lch');

    it('converts HWB to OKLCH directly', function (array $hwb, array $expected) {
        [$h, $w, $bl] = $hwb;
        $oklch = ColorConverter::HWB->toOklch($h, $w, $bl);

        foreach ($expected as $key => [$min, $max]) {
            expect($oklch[$key])->toBeGreaterThan($min)
                ->and($oklch[$key])->toBeLessThan($max);
        }
    })->with('hwb to oklch');

    it('converts XYZ to HSL directly', function (array $xyz, array $expected) {
        [$x, $y, $z] = $xyz;
        $hsl = ColorConverter::XYZ->toHsl($x, $y, $z);

        foreach ($expected as $key => [$min, $max]) {
            expect($hsl[$key])->toBeGreaterThanOrEqual($min)
                ->and($hsl[$key])->toBeLessThanOrEqual($max);
        }
    })->with('xyz to hsl');

    it('converts XYZ to HWB directly', function (array $xyz, array $expected) {
        [$x, $y, $z] = $xyz;
        $hwb = ColorConverter::XYZ->toHwb($x, $y, $z);

        foreach ($expected as $key => $value) {
            if (is_array($value)) {
                [$min, $max] = $value;
                expect($hwb[$key])->toBeGreaterThanOrEqual($min);
                if ($max !== null) {
                    expect($hwb[$key])->toBeLessThanOrEqual($max);
                }
            } else {
                expect($hwb[$key])->toBeGreaterThanOrEqual($value);
            }
        }
    })->with('xyz to hwb');
})->covers(ColorConverter::class);

describe('ColorConverter Edge Cases', function () {
    dataset('extreme rgb values', [
        'max rgb'     => [[255, 255, 255]],
        'zero rgb'    => [[0, 0, 0]],
        'single max'  => [[255, 0, 0]],
        'single zero' => [[0, 255, 0]],
    ]);

    dataset('boundary channel values', [
        'linearize threshold' => [0.04045],
        'linearize just below' => [0.04],
        'linearize just above' => [0.05],
        'unLinearize threshold' => [0.0031308],
        'unLinearize just below' => [0.003],
        'unLinearize just above' => [0.004],
    ]);

    it('handles extreme RGB values correctly', function (array $rgb) {
        [$r, $g, $b] = $rgb;

        // Test conversion to all color spaces doesn't throw exceptions
        $hsl = ColorConverter::RGB->toHsl($r, $g, $b);
        $hwb = ColorConverter::RGB->toHwb($r, $g, $b);
        $lab = ColorConverter::RGB->toLab($r, $g, $b);
        $lch = ColorConverter::RGB->toLch($r, $g, $b);
        $oklch = ColorConverter::RGB->toOklch($r, $g, $b);
        $xyz = ColorConverter::RGB->toXyz($r, $g, $b);

        expect($hsl)->toBeArray()->not->toBeEmpty()
            ->and($hwb)->toBeArray()->not->toBeEmpty()
            ->and($lab)->toBeArray()->not->toBeEmpty()
            ->and($lch)->toBeArray()->not->toBeEmpty()
            ->and($oklch)->toBeArray()->not->toBeEmpty()
            ->and($xyz)->toBeArray()->not->toBeEmpty();
    })->with('extreme rgb values');

    it('handles boundary channel values in linearization', function (float $value) {
        $linearized = $this->accessor->callMethod('linearizeChannel', [$value]);
        expect($linearized)->toBeGreaterThanOrEqual(0);

        $unLinearized = $this->accessor->callMethod('unLinearizeChannel', [$linearized]);
        expect($unLinearized)->toBeGreaterThanOrEqual($value - 0.001)
            ->and($unLinearized)->toBeLessThanOrEqual($value + 0.001);

        $unLinearizedDirect = $this->accessor->callMethod('unLinearizeChannel', [$value]);
        expect($unLinearizedDirect)->toBeGreaterThanOrEqual(0);
    })->with('boundary channel values');

    it('preserves round trip accuracy for all color spaces', function () {
        $testColors = [
            [255, 0, 0],     // Pure red
            [0, 255, 0],     // Pure green
            [0, 0, 255],     // Pure blue
            [128, 128, 128], // Gray
            [255, 255, 255], // White
            [0, 0, 0],       // Black
        ];

        foreach ($testColors as [$r, $g, $b]) {
            // Test all round trip conversions
            $conversions = [
                ['RGB', ColorConverter::HSL, 'toHsl', 'toRgb'],
                ['RGB', ColorConverter::HWB, 'toHwb', 'toRgb'],
                ['RGB', ColorConverter::LAB, 'toLab', 'toRgb'],
                ['RGB', ColorConverter::LCH, 'toLch', 'toRgb'],
                ['RGB', ColorConverter::OKLCH, 'toOklch', 'toRgb'],
                ['RGB', ColorConverter::XYZ, 'toXyz', 'toRgb'],
            ];

            foreach ($conversions as [$from, $toConverter, $toMethod, $backMethod]) {
                $fromConverter = ColorConverter::RGB;

                $converted = $fromConverter->$toMethod($r, $g, $b);
                $values = array_values($converted);
                $rgbBack = $toConverter->$backMethod(...$values);

                expect($rgbBack['r'])->toBeCloseTo($r, 5.0)
                    ->and($rgbBack['g'])->toBeCloseTo($g, 5.0)
                    ->and($rgbBack['b'])->toBeCloseTo($b, 5.0);
            }
        }
    });
})->covers(ColorConverter::class);

describe('ColorConverter Private Methods Direct Tests', function () {
    dataset('hwb private conversion data', [
        'pure red hwb'   => [[0, 0, 0], ['h' => 0.0, 's' => 100.0, 'l' => 50.0]],
        'white hwb'      => [[0, 100, 0], ['h' => 0.0, 's' => 0.0, 'l' => 100.0]],
        'black hwb'      => [[0, 0, 100], ['h' => 0.0, 's' => 0.0, 'l' => 0.0]],
        'green hwb'      => [[120, 0, 0], ['h' => 120.0, 's' => 100.0, 'l' => 50.0]],
        'blue hwb'       => [[240, 0, 0], ['h' => 240.0, 's' => 100.0, 'l' => 50.0]],
    ]);

    dataset('hwb to lab conversion data', [
        'pure red hwb'   => [[0, 0, 0], ['lab_l' => 53.24, 'lab_a' => 80.09, 'lab_b' => 67.22]],
        'white hwb'      => [[0, 100, 0], ['lab_l' => 100.0, 'lab_a' => 0.0, 'lab_b' => 0.0]],
        'black hwb'      => [[0, 0, 100], ['lab_l' => 0.0, 'lab_a' => 0.0, 'lab_b' => 0.0]],
        'green hwb'      => [[120, 0, 0], ['lab_l' => 87.73, 'lab_a' => -86.18, 'lab_b' => 83.18]],
        'blue hwb'       => [[240, 0, 0], ['lab_l' => 32.3, 'lab_a' => 79.19, 'lab_b' => -107.86]],
    ]);

    dataset('hsl private conversion data', [
        'red hsl'        => [[0, 100, 50], 'lab', ['lab_l' => 53.24, 'lab_a' => 80.09, 'lab_b' => 67.22]],
        'green hsl'      => [[120, 100, 50], 'lab', ['lab_l' => 87.73, 'lab_a' => -86.18, 'lab_b' => 83.18]],
        'blue hsl'       => [[240, 100, 50], 'lab', ['lab_l' => 32.3, 'lab_a' => 79.19, 'lab_b' => -107.86]],
    ]);

    dataset('lab private conversion data', [
        'red lab'        => [[53.24, 80.09, 67.22], 'oklch'],
        'green lab'      => [[87.73, -86.18, 83.18], 'oklch'],
        'blue lab'       => [[32.3, 79.19, -107.86], 'oklch'],
    ]);

    dataset('lch private conversion data', [
        'red lch'        => [[53.24, 104.55, 39.95], 'oklch'],
        'green lch'      => [[87.73, 119.78, 136.02], 'oklch'],
        'blue lch'       => [[32.3, 133.81, 306.28], 'oklch'],
    ]);

    dataset('oklch private conversion data', [
        'red oklch'      => [[62.8, 0.2577, 29.2], 'lab'],
        'green oklch'    => [[86.6, 0.2948, 142.5], 'lab'],
        'blue oklch'     => [[45.2, 0.313, 264.05], 'lab'],
    ]);

    dataset('xyz private conversion data', [
        'red xyz'        => [[41.24, 21.26, 1.93], 'lab'],
        'white xyz'      => [[95.05, 100.0, 108.9], 'lab'],
    ]);

    // Priority tests: HWB direct conversions
    it('converts HWB to HSL directly (private method)', function (array $hwb, array $expected) {
        $result = $this->accessor->callMethod('hwbToHsl', $hwb);

        expect($result['h'])->toBeCloseTo($expected['h'], 1.0)
            ->and($result['s'])->toBeCloseTo($expected['s'], 1.0)
            ->and($result['l'])->toBeCloseTo($expected['l'], 1.0);
    })->with('hwb private conversion data');

    it('converts HWB to Lab directly (private method)', function (array $hwb, array $expected) {
        $result = $this->accessor->callMethod('hwbToLab', $hwb);

        expect($result['lab_l'])->toBeCloseTo($expected['lab_l'], 1.0)
            ->and($result['lab_a'])->toBeCloseTo($expected['lab_a'], 1.0)
            ->and($result['lab_b'])->toBeCloseTo($expected['lab_b'], 1.0);
    })->with('hwb to lab conversion data');

    it('converts HWB to LCH directly (private method)', function () {
        $result = $this->accessor->callMethod('hwbToLch', [0, 0, 0]); // Pure red

        expect($result['l'])->toBeCloseTo(53.24, 1.0)
            ->and($result['c'])->toBeCloseTo(104.55, 1.0)
            ->and($result['h'])->toBeCloseTo(39.95, 1.0);
    });

    it('converts HWB to OKLCH directly (private method)', function () {
        $result = $this->accessor->callMethod('hwbToOklch', [0, 0, 0]); // Pure red

        expect($result['l'])->toBeCloseTo(62.8, 1.0)
            ->and($result['c'])->toBeCloseTo(0.2577, 0.01)
            ->and($result['h'])->toBeCloseTo(29.2, 1.0);
    });

    it('converts HWB to XYZ directly (private method)', function () {
        $result = $this->accessor->callMethod('hwbToXyz', [0, 0, 0]); // Pure red

        expect($result['x'])->toBeCloseTo(41.24, 0.5)
            ->and($result['y'])->toBeCloseTo(21.26, 0.5)
            ->and($result['z'])->toBeCloseTo(1.93, 0.5);
    });

    // HSL direct conversions
    it('converts HSL to Lab directly (private method)', function (array $hsl, string $target, array $expected) {
        $result = $this->accessor->callMethod('hslToLab', $hsl);

        expect($result['lab_l'])->toBeCloseTo($expected['lab_l'], 1.0)
            ->and($result['lab_a'])->toBeCloseTo($expected['lab_a'], 1.0)
            ->and($result['lab_b'])->toBeCloseTo($expected['lab_b'], 1.0);
    })->with('hsl private conversion data');

    it('converts HSL to LCH directly (private method)', function () {
        $result = $this->accessor->callMethod('hslToLch', [0, 100, 50]); // Pure red

        expect($result['l'])->toBeCloseTo(53.24, 1.0)
            ->and($result['c'])->toBeCloseTo(104.55, 1.0)
            ->and($result['h'])->toBeCloseTo(39.95, 1.0);
    });

    it('converts HSL to OKLCH directly (private method)', function () {
        $result = $this->accessor->callMethod('hslToOklch', [0, 100, 50]); // Pure red

        expect($result['l'])->toBeCloseTo(62.8, 1.0)
            ->and($result['c'])->toBeCloseTo(0.2577, 0.01)
            ->and($result['h'])->toBeCloseTo(29.2, 1.0);
    });

    it('converts HSL to XYZ directly (private method)', function () {
        $result = $this->accessor->callMethod('hslToXyz', [0, 100, 50]); // Pure red

        expect($result['x'])->toBeCloseTo(41.24, 0.5)
            ->and($result['y'])->toBeCloseTo(21.26, 0.5)
            ->and($result['z'])->toBeCloseTo(1.93, 0.5);
    });

    // Lab direct conversions
    it('converts Lab to LCH directly (private method)', function () {
        $result = $this->accessor->callMethod('labToLch', [53.24, 80.09, 67.22]); // Red

        expect($result['l'])->toBeCloseTo(53.24, 0.1)
            ->and($result['c'])->toBeCloseTo(104.55, 0.1)
            ->and($result['h'])->toBeCloseTo(39.95, 1.0);
    });

    it('converts Lab to HSL directly (private method)', function () {
        $result = $this->accessor->callMethod('labToHsl', [53.24, 80.09, 67.22]); // Red

        // Hue can be close to 360 which is equivalent to 0
        $hue = $result['h'];
        if ($hue > 300) {
            $hue = $hue - 360;
        }

        expect($hue)->toBeCloseTo(0.0, 5.0)
            ->and($result['s'])->toBeCloseTo(100.0, 5.0)
            ->and($result['l'])->toBeCloseTo(50.0, 5.0);
    });

    it('converts Lab to HWB directly (private method)', function () {
        $result = $this->accessor->callMethod('labToHwb', [53.24, 80.09, 67.22]); // Red

        expect($result)
            ->toHaveKeys(['h', 'w', 'bl'])
            ->and($result['h'])->toBeGreaterThanOrEqual(0)->toBeLessThan(360)
            ->and($result['w'])->toBeCloseTo(0.0, 2.0)
            ->and($result['bl'])->toBeCloseTo(0.0, 2.0);
    });

    it('converts Lab to OKLCH directly (private method)', function (array $lab) {
        $result = $this->accessor->callMethod('labToOklch', $lab);

        expect($result['l'])->toBeGreaterThan(0)
            ->and($result['c'])->toBeGreaterThan(0)
            ->and($result['h'])->toBeGreaterThanOrEqual(0);
    })->with('lab private conversion data');

    it('converts Lab to XYZ directly (private method)', function () {
        $result = $this->accessor->callMethod('labToXyz', [53.24, 80.09, 67.22]); // Red

        expect($result['x'])->toBeCloseTo(41.24, 0.5)
            ->and($result['y'])->toBeCloseTo(21.26, 0.5)
            ->and($result['z'])->toBeCloseTo(1.93, 0.5);
    });

    // LCH direct conversions
    it('converts LCH to HSL directly (private method)', function () {
        $result = $this->accessor->callMethod('lchToHsl', [53.24, 104.55, 39.95]); // Red

        // Hue can be close to 360 which is equivalent to 0
        $hue = $result['h'];
        if ($hue > 300) {
            $hue = $hue - 360;
        }

        expect($hue)->toBeCloseTo(0.0, 5.0)
            ->and($result['s'])->toBeCloseTo(100.0, 5.0)
            ->and($result['l'])->toBeCloseTo(50.0, 5.0);
    });

    it('converts LCH to HWB directly (private method)', function () {
        $result = $this->accessor->callMethod('lchToHwb', [53.24, 104.55, 39.95]); // Red

        expect($result)
            ->toHaveKeys(['h', 'w', 'bl'])
            ->and($result['h'])->toBeGreaterThanOrEqual(0)->toBeLessThan(360)
            ->and($result['w'])->toBeCloseTo(0.0, 2.0)
            ->and($result['bl'])->toBeCloseTo(0.0, 2.0);
    });

    it('converts LCH to OKLCH directly (private method)', function (array $lch) {
        $result = $this->accessor->callMethod('lchToOklch', $lch);

        expect($result['l'])->toBeGreaterThan(0)
            ->and($result['c'])->toBeGreaterThan(0)
            ->and($result['h'])->toBeGreaterThanOrEqual(0);
    })->with('lch private conversion data');

    it('converts LCH to XYZ directly (private method)', function () {
        $result = $this->accessor->callMethod('lchToXyz', [53.24, 104.55, 39.95]); // Red

        expect($result['x'])->toBeCloseTo(41.24, 0.5)
            ->and($result['y'])->toBeCloseTo(21.26, 0.5)
            ->and($result['z'])->toBeCloseTo(1.93, 0.5);
    });

    // OKLCH direct conversions
    it('converts OKLCH to HSL directly (private method)', function () {
        $result = $this->accessor->callMethod('oklchToHsl', [62.8, 0.2577, 29.2]); // Red

        // Hue can be close to 360 which is equivalent to 0
        $hue = $result['h'];
        if ($hue > 300) {
            $hue = $hue - 360;
        }

        expect($hue)->toBeCloseTo(0.0, 5.0)
            ->and($result['s'])->toBeGreaterThan(90.0)
            ->and($result['l'])->toBeGreaterThan(40.0);
    });

    it('converts OKLCH to HWB directly (private method)', function () {
        $result = $this->accessor->callMethod('oklchToHwb', [62.8, 0.2577, 29.2]); // Red

        expect($result)->toBeArray()
            ->toHaveKeys(['h', 'w', 'bl'])
            ->and($result['h'])->toBeGreaterThanOrEqual(0)->toBeLessThan(360)
            ->and($result['w'])->toBeCloseTo(0.0, 2.0)
            ->and($result['bl'])->toBeCloseTo(0.0, 2.0);
    });

    it('converts OKLCH to Lab directly (private method)', function (array $oklch) {
        $result = $this->accessor->callMethod('oklchToLab', $oklch);

        expect($result['lab_l'])->toBeGreaterThan(0)
            ->and($result['lab_a'])->not->toBe(0)
            ->and($result['lab_b'])->not->toBe(0);
    })->with('oklch private conversion data');

    it('converts OKLCH to LCH directly (private method)', function () {
        $result = $this->accessor->callMethod('oklchToLch', [62.8, 0.2577, 29.2]); // Red

        expect($result['l'])->toBeGreaterThan(50.0)
            ->and($result['c'])->toBeGreaterThan(100.0)
            ->and($result['h'])->toBeCloseTo(39.95, 2.0);
    });

    it('converts OKLCH to XYZ directly (private method)', function () {
        $result = $this->accessor->callMethod('oklchToXyz', [62.8, 0.2577, 29.2]); // Red

        expect($result['x'])->toBeCloseTo(41.24, 1.0)
            ->and($result['y'])->toBeCloseTo(21.26, 1.0)
            ->and($result['z'])->toBeCloseTo(1.93, 1.0);
    });

    // XYZ direct conversions
    it('converts XYZ to HSL directly (private method)', function () {
        $result = $this->accessor->callMethod('xyzToHsl', [41.24, 21.26, 1.93]); // Red

        expect($result['h'])->toBeCloseTo(0.0, 2.0)
            ->and($result['s'])->toBeGreaterThan(90.0)
            ->and($result['l'])->toBeGreaterThan(40.0);
    });

    it('converts XYZ to HWB directly (private method)', function () {
        $result = $this->accessor->callMethod('xyzToHwb', [41.24, 21.26, 1.93]); // Red

        expect($result['h'])->toBeCloseTo(0.0, 2.0)
            ->and($result['w'])->toBeLessThan(5.0)
            ->and($result['bl'])->toBeLessThan(5.0);
    });

    it('converts XYZ to Lab directly (private method)', function (array $xyz) {
        $result = $this->accessor->callMethod('xyzToLab', $xyz);

        expect($result['lab_l'])->toBeGreaterThan(0)
            ->and($result['lab_a'])->not->toBe(0)
            ->and($result['lab_b'])->not->toBe(0);
    })->with('xyz private conversion data');

    it('converts XYZ to LCH directly (private method)', function () {
        $result = $this->accessor->callMethod('xyzToLch', [41.24, 21.26, 1.93]); // Red

        expect($result['l'])->toBeGreaterThan(50.0)
            ->and($result['c'])->toBeGreaterThan(100.0)
            ->and($result['h'])->toBeCloseTo(39.95, 2.0);
    });

    it('converts XYZ to OKLCH directly (private method)', function () {
        $result = $this->accessor->callMethod('xyzToOklch', [41.24, 21.26, 1.93]); // Red

        expect($result['l'])->toBeGreaterThan(50.0)
            ->and($result['c'])->toBeGreaterThan(0.2)
            ->and($result['h'])->toBeCloseTo(29.2, 2.0);
    });

    // Edge cases for private methods
    dataset('extreme private conversion values', [
        'zero values'    => [[0, 0, 0]],
        'max values'     => [[360, 100, 100]],
        'gray values'    => [[50, 0, 0]],
    ]);

    it('handles extreme values in HWB to HSL conversion', function (array $hwb) {
        $result = $this->accessor->callMethod('hwbToHsl', $hwb);

        expect($result['h'])->toBeGreaterThanOrEqual(0)
            ->and($result['h'])->toBeLessThan(360)
            ->and($result['s'])->toBeGreaterThanOrEqual(0)
            ->and($result['s'])->toBeLessThanOrEqual(100)
            ->and($result['l'])->toBeGreaterThanOrEqual(0)
            ->and($result['l'])->toBeLessThanOrEqual(105);
    })->with('extreme private conversion values');

    it('handles extreme values in Lab to XYZ conversion', function (array $lab) {
        $result = $this->accessor->callMethod('labToXyz', $lab);

        expect($result['x'])->toBeGreaterThanOrEqual(-1)
            ->and($result['y'])->toBeGreaterThanOrEqual(0)
            ->and($result['z'])->toBeGreaterThanOrEqual(-1);
    })->with('extreme private conversion values');
})->covers(ColorConverter::class);
