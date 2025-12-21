<?php declare(strict_types=1);

use DartSass\Utils\ColorFunctions;
use DartSass\Exceptions\CompilationException;
use Tests\ReflectionAccessor;

beforeEach(function () {
    $this->colorFunctions = new ColorFunctions();
    $this->accessor       = new ReflectionAccessor($this->colorFunctions);
});

it('correctly parses hex3 color', function () {
    $result = $this->accessor->callProtectedMethod('parseColor', ['#f00']);

    expect($result)->toEqual(['r' => 255, 'g' => 0, 'b' => 0, 'a' => 1.0, 'format' => 'rgb']);
});

it('correctly parses hex6 color', function () {
    $result = $this->accessor->callProtectedMethod('parseColor', ['#ff0000']);

    expect($result)->toEqual(['r' => 255, 'g' => 0, 'b' => 0, 'a' => 1.0, 'format' => 'rgb']);
});

it('correctly parses hex8 color', function () {
    $result = $this->accessor->callProtectedMethod('parseColor', ['#ff000080']);

    expect($result['r'])->toBe(255)
        ->and($result['g'])->toBe(0)
        ->and($result['b'])->toBe(0)
        ->and($result['a'])->toBe(128 / 255.0)
        ->and($result['format'])->toBe('rgba');
});

it('correctly parses rgb color', function () {
    $result = $this->accessor->callProtectedMethod('parseColor', ['rgb(255, 0, 0)']);

    expect($result)->toEqual(['r' => 255, 'g' => 0, 'b' => 0, 'a' => 1.0, 'format' => 'rgb']);
});

it('correctly parses rgba color', function () {
    $result = $this->accessor->callProtectedMethod('parseColor', ['rgba(255, 0, 0, 0.5)']);

    expect($result)->toEqual(['r' => 255, 'g' => 0, 'b' => 0, 'a' => 0.5, 'format' => 'rgba']);
});

it('correctly parses hsl color', function () {
    $result = $this->accessor->callProtectedMethod('parseColor', ['hsl(0, 100%, 50%)']);

    expect($result)->toEqual(['h' => 0, 's' => 100, 'l' => 50, 'a' => 1.0, 'format' => 'hsl']);
});

it('correctly parses hsla color', function () {
    $result = $this->accessor->callProtectedMethod('parseColor', ['hsla(0, 100%, 50%, 0.5)']);

    expect($result)->toEqual(['h' => 0, 's' => 100, 'l' => 50, 'a' => 0.5, 'format' => 'hsla']);
});

it('correctly parses hwb color', function () {
    $result = $this->accessor->callProtectedMethod('parseColor', ['hwb(0, 0%, 50%)']);

    expect($result)->toEqual(['h' => 0, 'w' => 0.0, 'bl' => 0.5, 'a' => 1.0, 'format' => 'hwb']);
});

it('correctly parses named colors', function () {
    $result = $this->accessor->callProtectedMethod('parseColor', ['red']);

    expect($result)->toEqual(['r' => 255, 'g' => 0, 'b' => 0, 'a' => 1.0, 'format' => 'rgb']);

    $result = $this->accessor->callProtectedMethod('parseColor', ['blue']);

    expect($result)->toEqual(['r' => 0, 'g' => 0, 'b' => 255, 'a' => 1.0, 'format' => 'rgb']);
});

it('throws exception for invalid color', function () {
    expect(fn() => $this->accessor->callProtectedMethod('parseColor', ['invalid']))
        ->toThrow(CompilationException::class);
});

it('throws exception for invalid hex color', function () {
    expect(fn() => $this->accessor->callProtectedMethod('parseColor', ['#ff']))
        ->toThrow(CompilationException::class);
});

it('throws exception for invalid rgb with negative values', function () {
    expect(fn() => $this->accessor->callProtectedMethod('parseColor', ['rgb(-1, 0, 0)']))
        ->toThrow(CompilationException::class);
});

it('throws exception for invalid hsl with out-of-range saturation', function () {
    expect(fn() => $this->accessor->callProtectedMethod('parseColor', ['hsl(0, 150%, 50%)']))
        ->toThrow(CompilationException::class);
});

it('throws exception for invalid rgba with out-of-range alpha', function () {
    expect(fn() => $this->accessor->callProtectedMethod('parseColor', ['rgba(255, 0, 0, 1.5)']))
        ->toThrow(CompilationException::class);
});

it('throws exception for invalid hsl with out-of-range lightness', function () {
    expect(fn() => $this->accessor->callProtectedMethod('parseColor', ['hsl(0, 100%, 150%)']))
        ->toThrow(CompilationException::class);
});

it('throws exception for invalid hsla with out-of-range saturation', function () {
    expect(fn() => $this->accessor->callProtectedMethod('parseColor', ['hsla(0, 150%, 50%, 0.5)']))
        ->toThrow(CompilationException::class);
});

it('throws exception for invalid hsla with out-of-range lightness', function () {
    expect(fn() => $this->accessor->callProtectedMethod('parseColor', ['hsla(0, 100%, 150%, 0.5)']))
        ->toThrow(CompilationException::class);
});

it('throws exception for invalid hsla with out-of-range alpha', function () {
    expect(fn() => $this->accessor->callProtectedMethod('parseColor', ['hsla(0, 100%, 50%, 1.5)']))
        ->toThrow(CompilationException::class);
});

it('correctly formats rgb', function () {
    $colorData = ['r' => 255, 'g' => 0, 'b' => 0, 'a' => 1.0, 'format' => 'rgb'];
    $result = $this->accessor->callProtectedMethod('formatColor', [$colorData]);

    expect($result)->toBe('red');
});

it('correctly formats rgba', function () {
    $colorData = ['r' => 255, 'g' => 0, 'b' => 0, 'a' => 0.5, 'format' => 'rgba'];
    $result = $this->accessor->callProtectedMethod('formatColor', [$colorData]);

    expect($result)->toBe('#ff000080');
});

it('correctly formats hsl', function () {
    $colorData = ['h' => 0, 's' => 100, 'l' => 50, 'a' => 1.0, 'format' => 'hsl'];
    $result = $this->accessor->callProtectedMethod('formatColor', [$colorData]);

    expect($result)->toBe('red');
});

it('correctly formats hsla', function () {
    $colorData = ['h' => 0, 's' => 100, 'l' => 50, 'a' => 0.5, 'format' => 'hsla'];
    $result = $this->accessor->callProtectedMethod('formatColor', [$colorData]);

    expect($result)->toBe('#ff000080');
});

it('correctly converts rgb to hsl', function () {
    $hsl = $this->accessor->callProtectedMethod('rgbToHsl', [255, 0, 0]);

    expect($hsl['h'])->toBe(0.0)
        ->and($hsl['s'])->toBe(100.0)
        ->and($hsl['l'])->toBe(50.0);
});

it('correctly converts hsl to rgb', function () {
    $rgb = $this->accessor->callProtectedMethod('hslToRgb', [0, 100, 50]);

    expect($rgb['r'])->toBe(255.0)
        ->and($rgb['g'])->toBe(0.0)
        ->and($rgb['b'])->toBe(0.0);
});

it('correctly converts rgb to hwb', function () {
    $hwb = $this->accessor->callProtectedMethod('rgbToHwb', [255, 255, 255]);

    expect($hwb['h'])->toBe(0.0)
        ->and($hwb['w'])->toBe(100.0)
        ->and($hwb['bl'])->toBe(0.0);
});

it('correctly converts rgb to hwb when green component dominates', function () {
    // Pure green color - G > R and G > B
    $hwb = $this->accessor->callProtectedMethod('rgbToHwb', [0, 255, 0]);

    expect($hwb['h'])->toBe(120.0)
        ->and($hwb['w'])->toBe(0.0)
        ->and($hwb['bl'])->toBe(0.0);
});

it('correctly converts rgb to hwb when blue component dominates', function () {
    // Pure blue color - B > R and B > G
    $hwb = $this->accessor->callProtectedMethod('rgbToHwb', [0, 0, 255]);

    expect($hwb['h'])->toBe(240.0)
        ->and($hwb['w'])->toBe(0.0)
        ->and($hwb['bl'])->toBe(0.0);
});

it('correctly converts rgb to hwb with green dominance and mixed colors', function () {
    // Green-dominant color - G is max, but R and B are not max
    $hwb = $this->accessor->callProtectedMethod('rgbToHwb', [100, 200, 50]);

    // Green should dominate with hue around 120 degrees
    expect($hwb['h'])->toBeGreaterThan(90.0)->toBeLessThan(140.0)
        ->and($hwb['w'])->toBeGreaterThan(0.0)
        ->and($hwb['bl'])->toBeGreaterThan(0.0);
});

it('correctly converts rgb to hwb with blue dominance and mixed colors', function () {
    // Blue-dominant color - B is max, but R and G are not max
    $hwb = $this->accessor->callProtectedMethod('rgbToHwb', [50, 100, 200]);

    // Blue should dominate with hue around 240 degrees
    expect($hwb['h'])->toBeGreaterThan(200.0)->toBeLessThan(280.0)
        ->and($hwb['w'])->toBeGreaterThan(0.0)
        ->and($hwb['bl'])->toBeGreaterThan(0.0);
});

it('correctly converts hwb to rgb', function () {
    $rgb = $this->accessor->callProtectedMethod('hwbToRgb', [0, 100.0, 0]);

    expect($rgb['r'])->toBe(255.0)
        ->and($rgb['g'])->toBe(255.0)
        ->and($rgb['b'])->toBe(255.0);
});

it('correctly mixes colors', function () {
    $result = $this->colorFunctions->mix('#ff0000', '#0000ff');

    expect($result)->toBe('purple');
});

it('correctly lightens color', function () {
    $result = $this->colorFunctions->lighten('#007bff', 10);

    expect($result)->toBe('#3395ff');
});

it('correctly darkens color', function () {
    $result = $this->colorFunctions->darken('#b37399', 20);

    expect($result)->toBe('#7c4465');
});

it('correctly saturates color', function () {
    $result = $this->colorFunctions->saturate('#0e4982', 30);

    expect($result)->toBe('#004990');
});

it('correctly opacifies color', function () {
    $result = $this->colorFunctions->opacify('rgba(255, 0, 0, 0.5)', 0.5);

    expect($result)->toBe('red');
});

it('correctly transparentizes color', function () {
    $result = $this->colorFunctions->transparentize('#ff0000', 1.0);

    expect($result)->toBe('#ff000000');
});

it('correctly adjusts color', function () {
    $result = $this->colorFunctions->adjust('#ff0000', ['$red' => -50]);

    expect($result)->toBe('#cd0000');
});

it('throws exception for unknown adjustment parameter', function () {
    expect(fn() => $this->colorFunctions->adjust('#ff0000', ['$unknown' => 10]))
        ->toThrow(CompilationException::class, 'Unknown adjustment parameter');
});

it('correctly clamps value', function () {
    expect($this->accessor->callProtectedMethod('clamp', [15, 0, 10]))->toBe(10.0)
        ->and($this->accessor->callProtectedMethod('clamp', [5, 0, 10]))->toBe(5.0)
        ->and($this->accessor->callProtectedMethod('clamp', [-5, 0, 10]))->toBe(0.0);
});

it('correctly scales color', function () {
    $result = $this->colorFunctions->scale('#ff0000', ['$red' => 50]);

    expect($result)->toBe('red');

    $result = $this->colorFunctions->scale('#ff0000', ['$green' => 50]);

    expect($result)->toBe('#ff8000');

    $result = $this->colorFunctions->scale('#ff0000', ['$blue' => 50]);

    expect($result)->toBe('#ff0080');

    $result = $this->colorFunctions->scale('#ff0000', ['$hue' => 30]);

    expect($result)->toBe('#33ff00');

    $result = $this->colorFunctions->scale('#ff0000', ['$saturation' => -50]);

    expect($result)->toBe('#bf4040');

    $result = $this->colorFunctions->scale('#ff0000', ['$lightness' => 20]);

    expect($result)->toBe('#ff3333');

    $result = $this->colorFunctions->scale('#ffd700', ['$alpha' => 20]);

    expect($result)->toBe('gold');
});

it('throws exception for unknown scaling parameter', function () {
  expect(fn() => $this->colorFunctions->scale('#ff0000', ['$unknown' => 10]))
    ->toThrow(CompilationException::class, 'Unknown scaling parameter');
});

it('correctly changes color', function () {
    $result = $this->colorFunctions->change('#ff0000', ['$red' => 128]);

    expect($result)->toBe('maroon');

    $result = $this->colorFunctions->change('#ff0000', ['$green' => 128]);

    expect($result)->toBe('#ff8000');

    $result = $this->colorFunctions->change('#ff0000', ['$blue' => 128]);

    expect($result)->toBe('#ff0080');

    $result = $this->colorFunctions->change('#ff0000', ['$alpha' => 0.5]);

    expect($result)->toBe('#ff000080');

    $result = $this->colorFunctions->change('#ff0000', ['$hue' => 120]);

    expect($result)->toBe('lime');

    $result = $this->colorFunctions->change('#ff0000', ['$saturation' => 50]);

    expect($result)->toBe('#bf4040');

    $result = $this->colorFunctions->change('#ff0000', ['$lightness' => 50]);

    expect($result)->toBe('red');
});

it('throws exception for unknown changing parameter', function () {
  expect(fn() => $this->colorFunctions->change('#ff0000', ['$unknown' => 10]))
    ->toThrow(CompilationException::class, 'Unknown changing parameter');
});

it('correctly creates hsl color', function () {
    $result = $this->colorFunctions->hsl(0, 100, 50);

    expect($result)->toBe('red');

    $result = $this->colorFunctions->hsl(120, 100, 50);

    expect($result)->toBe('lime');

    $result = $this->colorFunctions->hsl(240, 100, 50);

    expect($result)->toBe('blue');

    $result = $this->colorFunctions->hsl(0, 100, 50, 0.5);

    expect($result)->toBe('#ff000080');
});

it('correctly creates hwb color', function () {
    $result = $this->colorFunctions->hwb(0, 0, 0);

    expect($result)->toBe('red');

    $result = $this->colorFunctions->hwb(0, 100, 0);

    expect($result)->toBe('white');

    $result = $this->colorFunctions->hwb(0, 0, 100);

    expect($result)->toBe('black');

    $result = $this->colorFunctions->hwb(120, 20, 30);

    expect($result)->toBe('#33b333');
});

it('correctly adjusts color with whiteness parameter', function () {
    $result = $this->colorFunctions->adjust('#ff0000', ['$whiteness' => 20]);

    expect($result)->toBe('#ff3333');
});

it('correctly adjusts color with blackness parameter', function () {
    $result = $this->colorFunctions->adjust('#ff0000', ['$blackness' => 20]);

    expect($result)->toBe('#cc0000');
});

it('correctly adjusts color with x parameter', function () {
    $result = $this->colorFunctions->adjust('#ff0000', ['$x' => 30, '$space' => 'xyz']);

    expect($result)->toBe('#ff0023');
});

it('correctly adjusts color with y parameter', function () {
    $result = $this->colorFunctions->adjust('#ff0000', ['$y' => 20, '$space' => 'xyz']);

    expect($result)->toBe('#d9a500');
});

it('correctly adjusts color with z parameter', function () {
    $result = $this->colorFunctions->adjust('#ff0000', ['$z' => 10, '$space' => 'xyz']);

    expect($result)->toBe('#f90d5b');
});

it('correctly adjusts color with chroma parameter', function () {
    $result = $this->colorFunctions->adjust('#ff0000', ['$chroma' => 20, '$space' => 'lch']);

    expect($result)->toBe('#ff3333');
});

it('correctly combines multiple rgb adjustment parameters', function () {
    $result = $this->colorFunctions->adjust('#ff0000', [
        '$red'   => 15,
        '$green' => 5,
        '$blue'  => 25,
    ]);

    expect($result)->toBe('#ff0519');
});

it('correctly converts rgb to xyz', function () {
    // Red color
    $xyz = $this->accessor->callProtectedMethod('rgbToXyz', [255, 0, 0]);

    $this->assertEqualsWithDelta(41.24, $xyz['x'], 0.01);
    $this->assertEqualsWithDelta(21.26, $xyz['y'], 0.01);
    $this->assertEqualsWithDelta(1.93, $xyz['z'], 0.01);

    // White color
    $xyz = $this->accessor->callProtectedMethod('rgbToXyz', [255, 255, 255]);

    $this->assertEqualsWithDelta(95.05, $xyz['x'], 0.01);
    $this->assertEqualsWithDelta(100.0, $xyz['y'], 0.01);
    $this->assertEqualsWithDelta(108.9, $xyz['z'], 0.01);
});

it('correctly converts xyz to rgb', function () {
    // Red color approximation
    $rgb = $this->accessor->callProtectedMethod('xyzToRgb', [41.24, 21.26, 1.93]);

    $this->assertEqualsWithDelta(255.0, $rgb['r'], 1.0);
    $this->assertEqualsWithDelta(0.0, $rgb['g'], 1.0);
    $this->assertEqualsWithDelta(0.0, $rgb['b'], 1.0);

    // White color approximation
    $rgb = $this->accessor->callProtectedMethod('xyzToRgb', [95.05, 100.0, 108.9]);

    $this->assertEqualsWithDelta(255.0, $rgb['r'], 1.0);
    $this->assertEqualsWithDelta(255.0, $rgb['g'], 1.0);
    $this->assertEqualsWithDelta(255.0, $rgb['b'], 1.0);
});

it('correctly ensures rgb format from hwb', function () {
    // HWB values w and bl are in percentage (0-100), not fractions (0-1)
    $colorData = ['h' => 0, 'w' => 0.0, 'bl' => 50.0, 'a' => 1.0, 'format' => 'hwb'];
    $result = $this->accessor->callProtectedMethod('ensureRgbFormat', [$colorData]);

    expect($result['format'])->toBe('rgb')
      ->and($result['a'])->toBe(1.0)
      ->and($result['r'])->toBeGreaterThan(0)
      ->and($result['g'])->toBe(0.0)
      ->and($result['b'])->toBe(0.0);
});

it('correctly ensures rgb format from hwb with alpha', function () {
    // HWB values w and bl are in percentage (0-100), not fractions (0-1)
    $colorData = ['h' => 120, 'w' => 20.0, 'bl' => 30.0, 'a' => 0.75, 'format' => 'hwb'];
    $result = $this->accessor->callProtectedMethod('ensureRgbFormat', [$colorData]);

    expect($result['format'])->toBe('rgb')
      ->and($result['a'])->toBe(0.75)
      ->and($result['r'])->toBeGreaterThanOrEqual(0)->toBeLessThanOrEqual(255)
      ->and($result['g'])->toBeGreaterThanOrEqual(0)->toBeLessThanOrEqual(255)
      ->and($result['b'])->toBeGreaterThanOrEqual(0)->toBeLessThanOrEqual(255);
});

it('correctly ensures rgb format from hwb with maximum whiteness', function () {
    // HWB values w and bl are in percentage (0-100), not fractions (0-1)
    $colorData = ['h' => 0, 'w' => 100.0, 'bl' => 0.0, 'a' => 1.0, 'format' => 'hwb'];
    $result = $this->accessor->callProtectedMethod('ensureRgbFormat', [$colorData]);

    expect($result['format'])->toBe('rgb')
        ->and($result['a'])->toBe(1.0)
        ->and($result['r'])->toBe(255.0)
        ->and($result['g'])->toBe(255.0)
        ->and($result['b'])->toBe(255.0);
});

it('correctly ensures rgb format from hwb with maximum blackness', function () {
    // HWB values w and bl are in percentage (0-100), not fractions (0-1)
    $colorData = ['h' => 0, 'w' => 0.0, 'bl' => 100.0, 'a' => 1.0, 'format' => 'hwb'];
    $result = $this->accessor->callProtectedMethod('ensureRgbFormat', [$colorData]);

    expect($result['format'])->toBe('rgb')
        ->and($result['a'])->toBe(1.0)
        ->and($result['r'])->toBeLessThan(10)
        ->and($result['g'])->toBeLessThan(10)
        ->and($result['b'])->toBeLessThan(10);
});

it('correctly ensures rgb format from hwb with balanced whiteness and blackness', function () {
    // HWB values w and bl are in percentage (0-100), not fractions (0-1)
    $colorData = ['h' => 240, 'w' => 40.0, 'bl' => 40.0, 'a' => 0.9, 'format' => 'hwb'];
    $result = $this->accessor->callProtectedMethod('ensureRgbFormat', [$colorData]);

    expect($result['format'])->toBe('rgb')
      ->and($result['a'])->toBe(0.9)
      ->and($result['r'])->toBeLessThanOrEqual(255)
      ->and($result['g'])->toBeLessThanOrEqual(255)
      ->and($result['b'])->toBeLessThanOrEqual(255);
});
