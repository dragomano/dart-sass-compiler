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

it('correctly clamps value', function () {
    expect($this->accessor->callProtectedMethod('clamp', [15, 0, 10]))->toBe(10.0)
        ->and($this->accessor->callProtectedMethod('clamp', [5, 0, 10]))->toBe(5.0)
        ->and($this->accessor->callProtectedMethod('clamp', [-5, 0, 10]))->toBe(0.0);
});

it('correctly scales color', function () {
    $result = $this->colorFunctions->scale('#ff0000', ['$lightness' => 20]);

    expect($result)->toBe('#ff3333');

    $result = $this->colorFunctions->scale('#ff0000', ['$saturation' => -50]);

    expect($result)->toBe('#bf4040');

    $result = $this->colorFunctions->scale('#ff0000', ['$red' => 50]);

    expect($result)->toBe('red');

    $result = $this->colorFunctions->scale('#ff0000', ['$hue' => 30]);

    expect($result)->toBe('#33ff00');
});

it('correctly changes color', function () {
    $result = $this->colorFunctions->change('#ff0000', ['$lightness' => 50]);

    expect($result)->toBe('red');

    $result = $this->colorFunctions->change('#ff0000', ['$hue' => 120]);

    expect($result)->toBe('lime');

    $result = $this->colorFunctions->change('#ff0000', ['$red' => 128]);

    expect($result)->toBe('maroon');

    $result = $this->colorFunctions->change('#ff0000', ['$alpha' => 0.5]);

    expect($result)->toBe('#ff000080');
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
