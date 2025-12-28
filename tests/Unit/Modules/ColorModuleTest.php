<?php

declare(strict_types=1);

use DartSass\Exceptions\CompilationException;
use DartSass\Modules\ColorModule;
use Tests\ReflectionAccessor;

beforeEach(function () {
    $this->colorModule = new ColorModule();
    $this->accessor    = new ReflectionAccessor($this->colorModule);
});

it('correctly parses hex3 color', function () {
    $result = $this->colorModule->parseColor('#f00');

    expect($result)->toEqual(['r' => 255, 'g' => 0, 'b' => 0, 'a' => 1.0, 'format' => 'rgb']);
});

it('correctly parses hex6 color', function () {
    $result = $this->colorModule->parseColor('#ff0000');

    expect($result)->toEqual(['r' => 255, 'g' => 0, 'b' => 0, 'a' => 1.0, 'format' => 'rgb']);
});

it('correctly parses hex8 color', function () {
    $result = $this->colorModule->parseColor('#ff000080');

    expect($result['r'])->toBe(255)
        ->and($result['g'])->toBe(0)
        ->and($result['b'])->toBe(0)
        ->and($result['a'])->toBe(128 / 255.0)
        ->and($result['format'])->toBe('rgba');
});

it('correctly parses rgb color', function () {
    $result = $this->colorModule->parseColor('rgb(255, 0, 0)');

    expect($result)->toEqual(['r' => 255, 'g' => 0, 'b' => 0, 'a' => 1.0, 'format' => 'rgb']);
});

it('correctly parses rgba color', function () {
    $result = $this->colorModule->parseColor('rgba(255, 0, 0, 0.5)');

    expect($result)->toEqual(['r' => 255, 'g' => 0, 'b' => 0, 'a' => 0.5, 'format' => 'rgba']);
});

it('correctly parses hsl color', function () {
    $result = $this->colorModule->parseColor('hsl(0, 100%, 50%)');

    expect($result)->toEqual(['h' => 0, 's' => 100, 'l' => 50, 'a' => 1.0, 'format' => 'hsl']);
});

it('correctly parses hsla color', function () {
    $result = $this->colorModule->parseColor('hsla(0, 100%, 50%, 0.5)');

    expect($result)->toEqual(['h' => 0, 's' => 100, 'l' => 50, 'a' => 0.5, 'format' => 'hsla']);
});

it('correctly parses hwb color', function () {
    $result = $this->colorModule->parseColor('hwb(0, 0%, 50%)');

    expect($result)->toEqual(['h' => 0, 'w' => 0, 'bl' => 50, 'a' => 1.0, 'format' => 'hwb']);
});

it('correctly parses lch color', function () {
    $result = $this->colorModule->parseColor('lch(60% 40 30deg)');

    expect($result)->toEqual(['l' => 60, 'c' => 40, 'h' => 30, 'a' => 1.0, 'format' => 'lch']);
});

it('correctly parses lch color with alpha', function () {
    $result = $this->colorModule->parseColor('lch(60% 40 30deg / 0.5)');

    expect($result)->toEqual(['l' => 60, 'c' => 40, 'h' => 30, 'a' => 0.5, 'format' => 'lch']);
});

it('correctly parses oklch color', function () {
    $result = $this->colorModule->parseColor('oklch(60% 0.15 30)');

    expect($result)->toEqual(['l' => 60, 'c' => 0.15, 'h' => 30, 'a' => 1.0, 'format' => 'oklch']);
});

it('correctly parses oklch color with alpha', function () {
    $result = $this->colorModule->parseColor('oklch(60% 0.15 30 / 0.5)');

    expect($result)->toEqual(['l' => 60, 'c' => 0.15, 'h' => 30, 'a' => 0.5, 'format' => 'oklch']);
});

it('correctly parses named colors', function () {
    $result = $this->colorModule->parseColor('red');

    expect($result)->toEqual(['r' => 255, 'g' => 0, 'b' => 0, 'a' => 1.0, 'format' => 'rgb']);

    $result = $this->colorModule->parseColor('blue');

    expect($result)->toEqual(['r' => 0, 'g' => 0, 'b' => 255, 'a' => 1.0, 'format' => 'rgb']);
});

it('throws exception for invalid color', function () {
    expect(fn() => $this->colorModule->parseColor('invalid'))
        ->toThrow(CompilationException::class);
});

it('throws exception for invalid hex color', function () {
    expect(fn() => $this->colorModule->parseColor('#ff'))
        ->toThrow(CompilationException::class);
});

it('throws exception for invalid rgb with negative values', function () {
    expect(fn() => $this->colorModule->parseColor('rgb(-1, 0, 0)'))
        ->toThrow(CompilationException::class);
});

it('throws exception for invalid hsl with out-of-range saturation', function () {
    expect(fn() => $this->colorModule->parseColor('hsl(0, 150%, 50%)'))
        ->toThrow(CompilationException::class);
});

it('throws exception for invalid rgba with out-of-range alpha', function () {
    expect(fn() => $this->colorModule->parseColor('rgba(255, 0, 0, 1.5)'))
        ->toThrow(CompilationException::class);
});

it('throws exception for invalid hsl with out-of-range lightness', function () {
    expect(fn() => $this->colorModule->parseColor('hsl(0, 100%, 150%)'))
        ->toThrow(CompilationException::class);
});

it('throws exception for invalid hsla with out-of-range saturation', function () {
    expect(fn() => $this->colorModule->parseColor('hsla(0, 150%, 50%, 0.5)'))
        ->toThrow(CompilationException::class);
});

it('throws exception for invalid hsla with out-of-range lightness', function () {
    expect(fn() => $this->colorModule->parseColor('hsla(0, 100%, 150%, 0.5)'))
        ->toThrow(CompilationException::class);
});

it('throws exception for invalid hsla with out-of-range alpha', function () {
    expect(fn() => $this->colorModule->parseColor('hsla(0, 100%, 50%, 1.5)'))
        ->toThrow(CompilationException::class);
});

it('correctly formats rgb', function () {
    $colorData = ['r' => 255, 'g' => 0, 'b' => 0, 'a' => 1.0, 'format' => 'rgb'];

    $result = $this->colorModule->formatColor($colorData);
    expect($result)->toBe('red');
});

it('correctly formats rgba', function () {
    $colorData = ['r' => 255, 'g' => 0, 'b' => 0, 'a' => 0.5, 'format' => 'rgba'];

    $result = $this->colorModule->formatColor($colorData);
    expect($result)->toBe('#ff000080');
});

it('correctly formats hsl', function () {
    $colorData = ['h' => 0, 's' => 100, 'l' => 50, 'a' => 1.0, 'format' => 'hsl'];

    $result = $this->colorModule->formatColor($colorData);
    expect($result)->toBe('hsl(0, 100%, 50%)');
});

it('correctly formats hsla', function () {
    $colorData = ['h' => 0, 's' => 100, 'l' => 50, 'a' => 0.5, 'format' => 'hsla'];

    $result = $this->colorModule->formatColor($colorData);
    expect($result)->toBe('hsla(0, 100%, 50%, 0.5)');
});

it('correctly converts rgb to hsl', function () {
    $hsl = $this->accessor->callMethod('rgbToHsl', [255, 0, 0]);

    expect($hsl['h'])->toBe(0.0)
        ->and($hsl['s'])->toBe(100.0)
        ->and($hsl['l'])->toBe(50.0);
});

it('correctly converts hsl to rgb', function () {
    $rgb = $this->accessor->callMethod('hslToRgb', [0, 100, 50]);

    expect($rgb['r'])->toBe(255.0)
        ->and($rgb['g'])->toBe(0.0)
        ->and($rgb['b'])->toBe(0.0);
});

it('correctly converts rgb to hwb', function () {
    $hwb = $this->accessor->callMethod('rgbToHwb', [255, 255, 255]);

    expect($hwb['h'])->toBe(0.0)
        ->and($hwb['w'])->toBe(100.0)
        ->and($hwb['bl'])->toBe(0.0);
});

it('correctly converts rgb to hwb when green component dominates', function () {
    // Pure green color - G > R and G > B
    $hwb = $this->accessor->callMethod('rgbToHwb', [0, 255, 0]);

    expect($hwb['h'])->toBe(120.0)
        ->and($hwb['w'])->toBe(0.0)
        ->and($hwb['bl'])->toBe(0.0);
});

it('correctly converts rgb to hwb when blue component dominates', function () {
    // Pure blue color - B > R and B > G
    $hwb = $this->accessor->callMethod('rgbToHwb', [0, 0, 255]);

    expect($hwb['h'])->toBe(240.0)
        ->and($hwb['w'])->toBe(0.0)
        ->and($hwb['bl'])->toBe(0.0);
});

it('correctly converts rgb to hwb with green dominance and mixed colors', function () {
    // Green-dominant color - G is max, but R and B are not max
    $hwb = $this->accessor->callMethod('rgbToHwb', [100, 200, 50]);

    // Green should dominate with hue around 120 degrees
    expect($hwb['h'])->toBeGreaterThan(90.0)->toBeLessThan(140.0)
        ->and($hwb['w'])->toBeGreaterThan(0.0)
        ->and($hwb['bl'])->toBeGreaterThan(0.0);
});

it('correctly converts rgb to hwb with blue dominance and mixed colors', function () {
    // Blue-dominant color - B is max, but R and G are not max
    $hwb = $this->accessor->callMethod('rgbToHwb', [50, 100, 200]);

    // Blue should dominate with hue around 240 degrees
    expect($hwb['h'])->toBeGreaterThan(200.0)->toBeLessThan(280.0)
        ->and($hwb['w'])->toBeGreaterThan(0.0)
        ->and($hwb['bl'])->toBeGreaterThan(0.0);
});

it('correctly converts hwb to rgb', function () {
    $rgb = $this->accessor->callMethod('hwbToRgb', [0, 100.0, 0]);

    expect($rgb['r'])->toBe(255.0)
        ->and($rgb['g'])->toBe(255.0)
        ->and($rgb['b'])->toBe(255.0);
});

it('correctly adjusts color', function () {
    $result = $this->colorModule->adjust('#ff0000', ['$red' => -50]);

    expect($result)->toBe('#cd0000');
});

it('throws exception for unknown adjustment parameter', function () {
    expect(fn() => $this->colorModule->adjust('#ff0000', ['$unknown' => 10]))
        ->toThrow(CompilationException::class, 'Unknown adjustment parameter');
});

it('correctly changes color', function () {
    $result = $this->colorModule->change('#ff0000', ['$red' => 128]);
    expect($result)->toBe('maroon');

    $result = $this->colorModule->change('#ff0000', ['$green' => 128]);
    expect($result)->toBe('#ff8000');

    $result = $this->colorModule->change('#ff0000', ['$blue' => 128]);
    expect($result)->toBe('#ff0080');

    $result = $this->colorModule->change('#ff0000', ['$alpha' => 0.5]);
    expect($result)->toBe('#ff000080');

    $result = $this->colorModule->change('#ff0000', ['$hue' => 120]);
    expect($result)->toBe('lime');

    $result = $this->colorModule->change('#ff0000', ['$saturation' => 50]);
    expect($result)->toBe('#bf4040');

    $result = $this->colorModule->change('#ff0000', ['$lightness' => 50]);
    expect($result)->toBe('red');
});

it('throws exception for unknown changing parameter', function () {
    expect(fn() => $this->colorModule->change('#ff0000', ['$unknown' => 10]))
        ->toThrow(CompilationException::class, 'Unknown changing parameter');
});

it('correctly returns color channel', function () {
    $result = $this->colorModule->channel('#ff0000', 'red');
    expect($result)->toBe('255');

    $result = $this->colorModule->channel('#ff0000', 'green');
    expect($result)->toBe('0');

    $result = $this->colorModule->channel('#ff0000', 'blue');
    expect($result)->toBe('0');

    $result = $this->colorModule->channel('#ff0000', 'alpha');
    expect($result)->toBe('1');

    $result = $this->colorModule->channel('hsl(0, 100%, 50%)', 'hue');
    expect($result)->toBe('0');

    $result = $this->colorModule->channel('hsl(0, 100%, 50%)', 'saturation');
    expect($result)->toBe('100%');

    $result = $this->colorModule->channel('hsl(0, 100%, 50%)', 'lightness');
    expect($result)->toBe('50%');
});

it('correctly returns complement color', function () {
    $result = $this->colorModule->complement('#ff0000'); // red
    expect($result)->toBe('cyan');

    $result = $this->colorModule->complement('#008000'); // green
    expect($result)->toBe('purple');
});

it('correctly converts color to grayscale', function () {
    $result = $this->colorModule->grayscale('#ff0000'); // red
    expect($result)->toBe('grey');

    $result = $this->colorModule->grayscale('#ffffff'); // white
    expect($result)->toBe('white');

    $result = $this->colorModule->grayscale('#000000'); // black
    expect($result)->toBe('black');
});

it('correctly returns IE hex string', function () {
    $result = $this->colorModule->ieHexStr('#ff0000');
    expect($result)->toBe('#FFFF0000'); // ARGB format for IE

    $result = $this->colorModule->ieHexStr('rgba(255, 0, 0, 0.5)');
    expect($result)->toBe('#80FF0000'); // ARGB format with alpha
});

it('correctly inverts color', function () {
    $result = $this->colorModule->invert('#ff0000'); // full invert
    expect($result)->toBe('cyan'); // inverted red is cyan

    $result = $this->colorModule->invert('#ffffff'); // invert white
    expect($result)->toBe('black'); // inverted white is black

    $result = $this->colorModule->invert('#000000'); // invert black
    expect($result)->toBe('white'); // inverted black is white
});

it('correctly checks if color is legacy', function () {
    $result = $this->colorModule->isLegacy('#ff0000');

    expect($result)->toBe('true');
});

it('correctly checks if channel is missing', function () {
    $result = $this->colorModule->isMissing('#ff0000', 'red');

    expect($result)->toBe('false');
});

it('correctly checks if hue channel is powerless', function () {
    $result = $this->colorModule->isPowerless('#808080', 'hue');
    expect($result)->toBe('true'); // gray: hsl(0, 0%, 50%) → hue powerless (s=0%)

    $result = $this->colorModule->isPowerless('#ff0000', 'hue');
    expect($result)->toBe('false'); // red: hsl(0, 100%, 50%) → hue has effect (s=100%)

    $result = $this->colorModule->isPowerless('hsl(180deg 0% 40%)', 'hue');
    expect($result)->toBe('true'); // hsl(180deg 0% 40%) → hue powerless (s=0%)
});

it('correctly mixes colors', function () {
    $result = $this->colorModule->mix('#ff0000', '#0000ff');

    expect($result)->toBe('purple');
});

it('correctly mixes colors with percentage weight', function () {
    // Mix with 75% weight should be same as 0.75 weight
    $resultPercent = $this->colorModule->mix('#ff0000', '#0000ff', 75);
    $resultDecimal = $this->colorModule->mix('#ff0000', '#0000ff', 0.75);

    expect($resultPercent)->toBe($resultDecimal);
});

it('correctly clamps weight to valid range', function () {
    // Weight > 1 is normalized by dividing by 100, then clamped
    $resultPercent = $this->colorModule->mix('#ff0000', '#0000ff', 50);
    $resultDecimal = $this->colorModule->mix('#ff0000', '#0000ff');

    expect($resultPercent)->toBe($resultDecimal);

    // Weight < 0 should be clamped to 0
    $resultLow = $this->colorModule->mix('#ff0000', '#0000ff', -1);
    $resultMin = $this->colorModule->mix('#ff0000', '#0000ff', 0);

    expect($resultLow)->toBe($resultMin);
});

it('correctly checks if two colors are the same', function () {
    $result = $this->colorModule->same('#ff0000', '#ff0000');
    expect($result)->toBe('true');

    $result = $this->colorModule->same('#ff0000', '#00ff00');
    expect($result)->toBe('false');

    $result = $this->colorModule->same('red', '#ff0000');
    expect($result)->toBe('true');
});

it('correctly scales color', function () {
    $result = $this->colorModule->scale('#ff0000', ['$red' => 50]);
    expect($result)->toBe('red');

    $result = $this->colorModule->scale('#ff0000', ['$green' => 50]);
    expect($result)->toBe('#ff8000');

    $result = $this->colorModule->scale('#ff0000', ['$blue' => 50]);
    expect($result)->toBe('#ff0080');

    $result = $this->colorModule->scale('#ff0000', ['$hue' => 30]);
    expect($result)->toBe('#33ff00');

    $result = $this->colorModule->scale('#ff0000', ['$saturation' => -50]);
    expect($result)->toBe('#bf4040');

    $result = $this->colorModule->scale('#ff0000', ['$lightness' => 20]);
    expect($result)->toBe('#ff3333');

    $result = $this->colorModule->scale('#ffd700', ['$alpha' => 20]);
    expect($result)->toBe('gold');
});

it('throws exception for unknown scaling parameter', function () {
    expect(fn() => $this->colorModule->scale('#ff0000', ['$unknown' => 10]))
        ->toThrow(CompilationException::class, 'Unknown scaling parameter');
});

it('correctly returns color space', function () {
    $result = $this->colorModule->space('#ff0000');
    expect($result)->toBe('rgb');

    $result = $this->colorModule->space('hsl(0, 100%, 50%)');
    expect($result)->toBe('hsl');

    $result = $this->colorModule->space('hwb(0, 0%, 0%)');
    expect($result)->toBe('hwb');
});

it('correctly converts color to gamut', function () {
    $result = $this->colorModule->toGamut('#ff0000');
    expect($result)->toBe('red');
});

it('correctly converts color to space', function () {
    $result = $this->colorModule->toSpace('#ff0000', 'hsl');
    expect($result)->toBe('hsl(0, 100%, 50%)');

    $result = $this->colorModule->toSpace('#ff0000', 'rgb');
    expect($result)->toBe('red');

    $result = $this->colorModule->toSpace('hsl(0, 100%, 50%)', 'rgb');
    expect($result)->toBe('red');
});

it('adjusts hue correctly', function () {
    expect($this->colorModule->adjustHue('#ff0000', 60))->toBe('yellow')
        ->and($this->colorModule->adjustHue('hsl(0, 100%, 50%)', 120))->toBe('lime')
        ->and($this->colorModule->adjustHue('#0000ff', -120))->toBe('lime');
});

it('returns alpha channel', function () {
    expect($this->colorModule->alpha('#ff0000'))->toBe('1')
        ->and($this->colorModule->alpha('rgba(255, 0, 0, 0.5)'))->toBe('0.5')
        ->and($this->colorModule->alpha('hsla(0, 100%, 50%, 0.3)'))->toBe('0.3');
});

it('returns opacity (alias for alpha)', function () {
    expect($this->colorModule->opacity('#ff0000'))->toBe('1')
        ->and($this->colorModule->opacity('rgba(255, 0, 0, 0.7)'))->toBe('0.7');
});

it('returns red channel', function () {
    expect($this->colorModule->red('#ff0000'))->toBe('255')
        ->and($this->colorModule->red('rgb(128, 64, 32)'))->toBe('128')
        ->and($this->colorModule->red('#00ff00'))->toBe('0');
});

it('returns green channel', function () {
    expect($this->colorModule->green('#00ff00'))->toBe('255')
        ->and($this->colorModule->green('rgb(128, 64, 32)'))->toBe('64')
        ->and($this->colorModule->green('#ff0000'))->toBe('0');
});

it('returns blue channel', function () {
    expect($this->colorModule->blue('#0000ff'))->toBe('255')
        ->and($this->colorModule->blue('rgb(128, 64, 32)'))->toBe('32')
        ->and($this->colorModule->blue('#ff0000'))->toBe('0');
});

it('returns hue channel', function () {
    expect($this->colorModule->hue('#ff0000'))->toBe('0')
        ->and($this->colorModule->hue('hsl(120, 100%, 50%)'))->toBe('120deg')
        ->and($this->colorModule->hue('#00ff00'))->toBe('120deg')
        ->and($this->colorModule->hue('#0000ff'))->toBe('240deg');
});

it('returns blackness channel', function () {
    expect($this->colorModule->blackness('hwb(0, 0%, 50%)'))->toBe('50%')
        ->and($this->colorModule->blackness('#ffffff'))->toBe('0%')
        ->and($this->colorModule->blackness('#000000'))->toBe('100%');
});

it('returns lightness channel', function () {
    expect($this->colorModule->lightness('hsl(0, 100%, 50%)'))->toBe('50%')
        ->and($this->colorModule->lightness('#ffffff'))->toBe('100%')
        ->and($this->colorModule->lightness('#000000'))->toBe('0%')
        ->and($this->colorModule->lightness('#808080'))->toBe('50.1960784314%');
});

it('returns whiteness channel', function () {
    expect($this->colorModule->whiteness('hwb(0, 50%, 0%)'))->toBe('50%')
        ->and($this->colorModule->whiteness('#ffffff'))->toBe('100%')
        ->and($this->colorModule->whiteness('#000000'))->toBe('0%');
});

it('returns saturation channel', function () {
    expect($this->colorModule->saturation('hsl(0, 100%, 50%)'))->toBe('100%')
        ->and($this->colorModule->saturation('hsl(0, 50%, 50%)'))->toBe('50%')
        ->and($this->colorModule->saturation('#808080'))->toBe('0%');
});

it('correctly lightens color', function () {
    $result = $this->colorModule->lighten('#007bff', 10);

    expect($result)->toBe('#3395ff');
});

it('correctly darkens color', function () {
    $result = $this->colorModule->darken('#b37399', 20);

    expect($result)->toBe('#7c4465');
});

it('correctly saturates color', function () {
    $result = $this->colorModule->saturate('#0e4982', 30);

    expect($result)->toBe('#004990');
});

it('correctly opacifies color', function () {
    $result = $this->colorModule->opacify('rgba(255, 0, 0, 0.5)', 0.5);

    expect($result)->toBe('red');
});

it('correctly transparentizes color', function () {
    $result = $this->colorModule->transparentize('#ff0000', 1.0);

    expect($result)->toBe('#ff000000');
});

it('correctly fades in color', function () {
    $result = $this->colorModule->fadeIn('rgba(255, 0, 0, 0.5)', 0.3);

    expect($result)->toBe('#ff0000cc');
});

it('correctly fades out color', function () {
    $result = $this->colorModule->fadeOut('rgba(255, 0, 0, 0.8)', 0.3);

    expect($result)->toBe('#ff000080');
});

it('correctly creates hsl color', function () {
    $result = $this->colorModule->hsl(0, 100, 50);
    expect($result)->toBe('hsl(0, 100%, 50%)');

    $result = $this->colorModule->hsl(120, 100, 50);
    expect($result)->toBe('hsl(120, 100%, 50%)');

    $result = $this->colorModule->hsl(240, 100, 50);
    expect($result)->toBe('hsl(240, 100%, 50%)');

    $result = $this->colorModule->hsl(0, 100, 50, 0.5);
    expect($result)->toBe('hsla(0, 100%, 50%, 0.5)');
});

it('correctly creates hwb color', function () {
    $result = $this->colorModule->hwb(0, 0, 0);
    expect($result)->toBe('hwb(0 0% 0%)');

    $result = $this->colorModule->hwb(0, 100, 0);
    expect($result)->toBe('hwb(0 100% 0%)');

    $result = $this->colorModule->hwb(0, 0, 100);
    expect($result)->toBe('hwb(0 0% 100%)');

    $result = $this->colorModule->hwb(120, 20, 30);
    expect($result)->toBe('hwb(120 20% 30%)');
});

it('correctly adjusts color with whiteness parameter', function () {
    $result = $this->colorModule->adjust('#ff0000', ['$whiteness' => 20]);

    expect($result)->toBe('#ff3333');
});

it('correctly adjusts color with blackness parameter', function () {
    $result = $this->colorModule->adjust('#ff0000', ['$blackness' => 20]);

    expect($result)->toBe('#cc0000');
});

it('correctly adjusts color with x parameter', function () {
    $result = $this->colorModule->adjust('#ff0000', ['$x' => 30, '$space' => 'xyz']);

    expect($result)->toBe('#ff0023');
});

it('correctly adjusts color with y parameter', function () {
    $result = $this->colorModule->adjust('#ff0000', ['$y' => 20, '$space' => 'xyz']);

    expect($result)->toBe('#d9a500');
});

it('correctly adjusts color with z parameter', function () {
    $result = $this->colorModule->adjust('#ff0000', ['$z' => 10, '$space' => 'xyz']);

    expect($result)->toBe('#f90d5b');
});

it('correctly adjusts color with chroma parameter', function () {
    $result = $this->colorModule->adjust('#ff0000', ['$chroma' => 20, '$space' => 'lch']);

    expect($result)->toBe('#ff3333');
});

it('correctly combines multiple rgb adjustment parameters', function () {
    $result = $this->colorModule->adjust('#ff0000', [
        '$red'   => 15,
        '$green' => 5,
        '$blue'  => 25,
    ]);

    expect($result)->toBe('#ff0519');
});

it('correctly converts rgb to xyz', function () {
    // Red color
    $xyz = $this->accessor->callMethod('rgbToXyz', [255, 0, 0]);

    expect($xyz['x'])->toBeCloseTo(41.24, 0.01)
        ->and($xyz['y'])->toBeCloseTo(21.26, 0.01)
        ->and($xyz['z'])->toBeCloseTo(1.93, 0.01);

    // White color
    $xyz = $this->accessor->callMethod('rgbToXyz', [255, 255, 255]);

    expect($xyz['x'])->toBeCloseTo(95.05, 0.01)
        ->and($xyz['y'])->toBeCloseTo(100.0, 0.01)
        ->and($xyz['z'])->toBeCloseTo(108.9, 0.01);
});

it('correctly converts xyz to rgb', function () {
    // Red color approximation
    $rgb = $this->accessor->callMethod('xyzToRgb', [41.24, 21.26, 1.93]);

    expect($rgb['r'])->toBeCloseTo(255.0, 1.0)
        ->and($rgb['g'])->toBeCloseTo(0.0, 1.0)
        ->and($rgb['b'])->toBeCloseTo(0.0, 1.0);

    // White color approximation
    $rgb = $this->accessor->callMethod('xyzToRgb', [95.05, 100.0, 108.9]);

    expect($rgb['r'])->toBeCloseTo(255.0, 1.0)
        ->and($rgb['g'])->toBeCloseTo(255.0, 1.0)
        ->and($rgb['b'])->toBeCloseTo(255.0, 1.0);
});

it('correctly ensures rgb format from hwb', function () {
    // HWB values w and bl are in percentage (0-100), not fractions (0-1)
    $colorData = ['h' => 0, 'w' => 0.0, 'bl' => 50.0, 'a' => 1.0, 'format' => 'hwb'];
    $result = $this->accessor->callMethod('ensureRgbFormat', [$colorData]);

    expect($result['format'])->toBe('rgb')
        ->and($result['a'])->toBe(1.0)
        ->and($result['r'])->toBeGreaterThan(0)
        ->and($result['g'])->toBe(0.0)
        ->and($result['b'])->toBe(0.0);
});

it('correctly ensures rgb format from hwb with alpha', function () {
    // HWB values w and bl are in percentage (0-100), not fractions (0-1)
    $colorData = ['h' => 120, 'w' => 20.0, 'bl' => 30.0, 'a' => 0.75, 'format' => 'hwb'];
    $result = $this->accessor->callMethod('ensureRgbFormat', [$colorData]);

    expect($result['format'])->toBe('rgb')
        ->and($result['a'])->toBe(0.75)
        ->and($result['r'])->toBeGreaterThanOrEqual(0)->toBeLessThanOrEqual(255)
        ->and($result['g'])->toBeGreaterThanOrEqual(0)->toBeLessThanOrEqual(255)
        ->and($result['b'])->toBeGreaterThanOrEqual(0)->toBeLessThanOrEqual(255);
});

it('correctly ensures rgb format from hwb with maximum whiteness', function () {
    // HWB values w and bl are in percentage (0-100), not fractions (0-1)
    $colorData = ['h' => 0, 'w' => 100.0, 'bl' => 0.0, 'a' => 1.0, 'format' => 'hwb'];
    $result = $this->accessor->callMethod('ensureRgbFormat', [$colorData]);

    expect($result['format'])->toBe('rgb')
        ->and($result['a'])->toBe(1.0)
        ->and($result['r'])->toBe(255.0)
        ->and($result['g'])->toBe(255.0)
        ->and($result['b'])->toBe(255.0);
});

it('correctly ensures rgb format from hwb with maximum blackness', function () {
    // HWB values w and bl are in percentage (0-100), not fractions (0-1)
    $colorData = ['h' => 0, 'w' => 0.0, 'bl' => 100.0, 'a' => 1.0, 'format' => 'hwb'];
    $result = $this->accessor->callMethod('ensureRgbFormat', [$colorData]);

    expect($result['format'])->toBe('rgb')
        ->and($result['a'])->toBe(1.0)
        ->and($result['r'])->toBeLessThan(10)
        ->and($result['g'])->toBeLessThan(10)
        ->and($result['b'])->toBeLessThan(10);
});

it('correctly ensures rgb format from hwb with balanced whiteness and blackness', function () {
    // HWB values w and bl are in percentage (0-100), not fractions (0-1)
    $colorData = ['h' => 240, 'w' => 40.0, 'bl' => 40.0, 'a' => 0.9, 'format' => 'hwb'];
    $result = $this->accessor->callMethod('ensureRgbFormat', [$colorData]);

    expect($result['format'])->toBe('rgb')
        ->and($result['a'])->toBe(0.9)
        ->and($result['r'])->toBeLessThanOrEqual(255)
        ->and($result['g'])->toBeLessThanOrEqual(255)
        ->and($result['b'])->toBeLessThanOrEqual(255);
});

it('correctly clamps value', function () {
    expect($this->accessor->callMethod('clamp', [15, 0, 10]))->toBe(10.0)
        ->and($this->accessor->callMethod('clamp', [5, 0, 10]))->toBe(5.0)
        ->and($this->accessor->callMethod('clamp', [-5, 0, 10]))->toBe(0.0);
});

it('correctly inverts color with hwb space', function () {
    $result = $this->colorModule->invert('#ff0000', 100, 'hwb');
    expect($result)->toBe('cyan');
});

it('correctly inverts color with hsl space', function () {
    $result = $this->colorModule->invert('#ff0000', 100, 'hsl');
    expect($result)->toBe('cyan');
});

it('correctly returns complement color with different spaces', function () {
    expect($this->colorModule->complement('#ff0000', 'hwb'))->toBe('cyan')
        ->and($this->colorModule->complement('#008000', 'hwb'))->toBe('purple')
        ->and($this->colorModule->complement('#ff0000', 'lch'))->toBe('cyan')
        ->and($this->colorModule->complement('#ff0000', 'oklch'))->toBe('cyan');
});

it('throws exception for unsupported color space in complement', function () {
    expect(fn() => $this->colorModule->complement('#ff0000', 'xyz'))
        ->toThrow(CompilationException::class, 'not a polar color space');
});

it('correctly converts color to gamut with different spaces', function () {
    expect($this->colorModule->toGamut('#ff0000', 'hsl'))->toBe('red')
        ->and($this->colorModule->toGamut('#ff0000', 'hwb'))->toBe('red')
        ->and($this->colorModule->toGamut('#ff0000', 'rgb'))->toBe('red');
});

it('throws exception for unsupported method in toGamut', function () {
    expect(fn() => $this->colorModule->toGamut('#ff0000', null, 'invalid'))
        ->toThrow(CompilationException::class, 'Only \'clip\' method is currently supported');
});

it('correctly handles oklch color with different formats', function () {
    $result = $this->colorModule->toSpace('oklch(60% 0.15 30)', 'rgb');
    expect($result)->toBe('#ca5747');

    $result = $this->colorModule->toSpace('#ff0000', 'oklch');
    expect($result)->toMatch('/^oklch\(/');
});

it('correctly parses hwb with alpha', function () {
    $result = $this->colorModule->parseColor('hwb(0 0% 50% / 0.5)');
    expect($result)->toEqual(['h' => 0, 'w' => 0, 'bl' => 50, 'a' => 0.5, 'format' => 'hwb']);
});

it('correctly checks if hue channel is powerless with different spaces', function () {
    expect($this->colorModule->isPowerless('#808080', 'hue', 'hsl'))->toBe('true')
        ->and($this->colorModule->isPowerless('#808080', 'hue', 'hwb'))->toBe('true');
});

it('correctly checks if other channels are powerless', function () {
    expect($this->colorModule->isPowerless('#808080', 'saturation'))->toBe('false')
        ->and($this->colorModule->isPowerless('#ff0000', 'hue'))->toBe('false');
});

it('throws exception for unknown channel in isPowerless', function () {
    expect(fn() => $this->colorModule->isPowerless('#808080', 'unknown'))
        ->toThrow(CompilationException::class, 'Unknown channel');
});

it('correctly handles different channel formats in isMissing', function () {
    expect($this->colorModule->isMissing('#ff0000', 'r'))->toBe('false')
        ->and($this->colorModule->isMissing('#ff0000', 'red'))->toBe('false')
        ->and($this->colorModule->isMissing('hsl(0, 100%, 50%)', 'h'))->toBe('false')
        ->and($this->colorModule->isMissing('hsl(0, 100%, 50%)', 'hue'))->toBe('false')
        ->and($this->colorModule->isMissing('#ff0000', 'h'))->toBe('true')
        ->and($this->colorModule->isMissing('#ff0000', 'hue'))->toBe('true');
});

it('throws exception for unknown channel in isMissing', function () {
    expect(fn() => $this->colorModule->isMissing('#808080', 'unknown'))
        ->toThrow(CompilationException::class, 'Unknown channel');
});

it('correctly adjusts color with oklch parameters', function () {
    $result = $this->colorModule->adjust('#ff0000', ['$space' => 'oklch', '$chroma' => 0.1]);
    expect($result)->toBe('red');
});

it('correctly handles linearize and unlinearize channel methods', function () {
    expect($this->accessor->callMethod('linearizeChannel', [0.04045]))->toBeCloseTo(0.04045 / 12.92, 5)
        ->and($this->accessor->callMethod('linearizeChannel', [0.5]))->toBeGreaterThan(0.04045 / 12.92)
        ->and($this->accessor->callMethod('unLinearizeChannel', [0.0031308]))->toBeCloseTo(12.92 * 0.0031308, 5)
        ->and($this->accessor->callMethod('unLinearizeChannel', [0.5]))->toBeGreaterThan(12.92 * 0.0031308);
});

it('correctly handles scale channel with positive and negative amounts', function () {
    // Test scaleChannel with positive amount
    expect($this->accessor->callMethod('scaleChannel', [100, 50, 0, 255]))->toBeGreaterThan(100)
        ->and($this->accessor->callMethod('scaleChannel', [100, -50, 0, 255]))->toBeLessThan(100);
});

it('correctly handles edge cases in parseOklchColor', function () {
    // Test oklch with percentage lightness
    $result = $this->colorModule->parseColor('oklch(60% 0.15 30)');
    expect($result['l'])->toBe(60.0);

    // Test oklch with non-percentage lightness (should be multiplied by 100)
    $result = $this->colorModule->parseColor('oklch(0.6 0.15 30)');
    expect($result['l'])->toBe(60.0);
});

it('throws exception for invalid alpha in parseOklchColor', function () {
    expect(fn() => $this->colorModule->parseColor('oklch(60% 0.15 30 / 1.5)'))
        ->toThrow(CompilationException::class, 'Invalid alpha value');
});

it('correctly formats lch color', function () {
    $colorData = ['l' => 60, 'c' => 40, 'h' => 30, 'a' => 1.0, 'format' => 'lch'];
    $result = $this->colorModule->formatColor($colorData);
    expect($result)->toBe('lch(60% 40 30)');

    $colorData = ['l' => 60, 'c' => 40, 'h' => 30, 'a' => 0.5, 'format' => 'lch'];
    $result = $this->colorModule->formatColor($colorData);
    expect($result)->toBe('lch(60% 40 30 / 0.5)');
});

it('correctly formats oklch color', function () {
    $colorData = ['l' => 60, 'c' => 0.15, 'h' => 30, 'a' => 1.0, 'format' => 'oklch'];
    $result = $this->colorModule->formatColor($colorData);
    expect($result)->toBe('oklch(60% 0.15 30)');

    $colorData = ['l' => 60, 'c' => 0.15, 'h' => 30, 'a' => 0.5, 'format' => 'oklch'];
    $result = $this->colorModule->formatColor($colorData);
    expect($result)->toBe('oklch(60% 0.15 30 / 0.5)');
});

it('correctly handles key to channel conversion', function () {
    expect($this->accessor->callMethod('keyToChannel', ['$red']))->toBe('r')
        ->and($this->accessor->callMethod('keyToChannel', ['$green']))->toBe('g')
        ->and($this->accessor->callMethod('keyToChannel', ['$blue']))->toBe('b');
});

it('throws exception for invalid key in keyToChannel', function () {
    expect(fn() => $this->accessor->callMethod('keyToChannel', ['$invalid']))
        ->toThrow(InvalidArgumentException::class, 'Invalid RGB key');
});

it('correctly handles key to hsl channel conversion', function () {
    expect($this->accessor->callMethod('keyToHslChannel', ['$hue']))->toBe('h')
        ->and($this->accessor->callMethod('keyToHslChannel', ['$saturation']))->toBe('s')
        ->and($this->accessor->callMethod('keyToHslChannel', ['$lightness']))->toBe('l');
});

it('throws exception for invalid key in keyToHslChannel', function () {
    expect(fn() => $this->accessor->callMethod('keyToHslChannel', ['$invalid']))
        ->toThrow(InvalidArgumentException::class, 'Invalid HSL key');
});

it('correctly handles color space detection', function () {
    expect($this->accessor->callMethod('getColorSpace', ['hsl']))->toBe('hsl')
        ->and($this->accessor->callMethod('getColorSpace', ['hsla']))->toBe('hsl')
        ->and($this->accessor->callMethod('getColorSpace', ['hwb']))->toBe('hwb')
        ->and($this->accessor->callMethod('getColorSpace', ['lch']))->toBe('lch')
        ->and($this->accessor->callMethod('getColorSpace', ['oklch']))->toBe('oklch')
        ->and($this->accessor->callMethod('getColorSpace', ['rgb']))->toBe('rgb')
        ->and($this->accessor->callMethod('getColorSpace', ['rgba']))->toBe('rgb');
});

it('correctly handles hwb to rgb conversion with edge cases', function () {
    // Test with 0 whiteness and 0 blackness
    $rgb = $this->accessor->callMethod('hwbToRgb', [0, 0.0, 0.0]);
    expect($rgb['r'])->toBe(255.0)
        ->and($rgb['g'])->toBe(0.0)
        ->and($rgb['b'])->toBe(0.0);

    // Test with 100 whiteness
    $rgb = $this->accessor->callMethod('hwbToRgb', [0, 100.0, 0.0]);
    expect($rgb['r'])->toBe(255.0)
        ->and($rgb['g'])->toBe(255.0)
        ->and($rgb['b'])->toBe(255.0);

    // Test with 100 blackness
    $rgb = $this->accessor->callMethod('hwbToRgb', [0, 0.0, 100.0]);
    expect($rgb['r'])->toBeLessThan(5)
        ->and($rgb['g'])->toBeLessThan(5)
        ->and($rgb['b'])->toBeLessThan(5);
});

it('correctly handles edge cases in parseHwbColor', function () {
    // Test with valid whiteness and blackness
    $result = $this->accessor->callMethod('parseHwbColor', [['hwb(0, 50%, 30%)', '0', '50', '30']]);
    expect($result['h'])->toBeCloseTo(0, 0)
        ->and($result['w'])->toBe(50)
        ->and($result['bl'])->toBe(30);

    $result = $this->accessor->callMethod('parseHwbColor', [['hwb(0, 50%, 30%, 0.5)', '0', '50', '30', '0.5']]);
    expect($result['a'])->toBe(0.5)
        ->and(fn() => $this->accessor->callMethod('parseHwbColor', [['hwb(0, 150%, 30%)', '0', '150', '30']]))
        ->toThrow(CompilationException::class, 'Invalid whiteness value')
        ->and(fn() => $this->accessor->callMethod('parseHwbColor', [['hwb(0, 50%, 150%)', '0', '50', '150']]))
        ->toThrow(CompilationException::class, 'Invalid blackness value');
});

it('correctly handles edge cases in parseHexColor', function () {
    // Test with 3-digit hex
    $result = $this->accessor->callMethod('parseHexColor', ['f00']);
    expect($result['r'])->toBe(255)
        ->and($result['g'])->toBe(0)
        ->and($result['b'])->toBe(0);

    // Test with 4-digit hex (with alpha)
    $result = $this->accessor->callMethod('parseHexColor', ['f008']); // 50% alpha
    expect($result['a'])->toBeCloseTo(136 / 255, 3);

    // Test with 6-digit hex
    $result = $this->accessor->callMethod('parseHexColor', ['ff0000']);
    expect($result['r'])->toBe(255)
        ->and($result['g'])->toBe(0)
        ->and($result['b'])->toBe(0);

    // Test with 8-digit hex (with alpha)
    $result = $this->accessor->callMethod('parseHexColor', ['ff000080']); // 50% alpha
    expect($result['a'])->toBeCloseTo(128 / 255, 3);
});

it('correctly handles edge cases in parseRgbColor', function () {
    // Test with values in range
    $result = $this->accessor->callMethod('parseRgbColor', [['rgb(255, 0, 0)', '255', '0', '0']]);
    expect($result['r'])->toBeCloseTo(255, 0)
        ->and($result['g'])->toBeCloseTo(0, 0)
        ->and($result['b'])->toBeCloseTo(0, 0);

    // Test with values clamped to range
    $result = $this->accessor->callMethod('parseRgbColor', [['rgb(300, 0, 0)', '300', '0', '0']]);
    expect($result['r'])->toBe(255);

    $result = $this->accessor->callMethod('parseRgbColor', [['rgb(-10, 0, 0)', '-10', '0', '0']]);
    expect($result['r'])->toBe(0);
});

it('correctly handles edge cases in parseRgbaColor', function () {
    $result = $this->accessor->callMethod('parseRgbaColor', [['rgba(255, 0, 0, 0.5)', '255', '0', '0', '0.5']]);
    expect($result['a'])->toBeCloseTo(0.5, 1)
        ->and(fn() => $this->accessor->callMethod('parseRgbaColor', [['rgba(255, 0, 0, 1.5)', '255', '0', '0', '1.5']]))
        ->toThrow(CompilationException::class);
});

it('correctly handles edge cases in parseHslColor', function () {
    $result = $this->accessor->callMethod('parseHslColor', [['hsl(0, 100%, 50%)', '0', '100', '50']]);
    expect($result['h'])->toBeCloseTo(0, 0)
        ->and($result['s'])->toBe(100.0)
        ->and($result['l'])->toBe(50.0)
        ->and(fn() => $this->accessor->callMethod('parseHslColor', [['hsl(0, 150%, 50%)', '0', '150', '50']]))
        ->toThrow(CompilationException::class, 'Invalid saturation value')
        ->and(fn() => $this->accessor->callMethod('parseHslColor', [['hsl(0, 100%, 150%)', '0', '100', '150']]))
        ->toThrow(CompilationException::class, 'Invalid lightness value');
});

it('correctly handles edge cases in parseHslaColor', function () {
    $result = $this->accessor->callMethod('parseHslaColor', [['hsla(0, 100%, 50%, 0.5)', '0', '100', '50', '0.5']]);
    expect($result['a'])->toBeCloseTo(0.5, 1)
        ->and(fn() => $this->accessor->callMethod('parseHslaColor', [['hsla(0, 100%, 50%, 1.5)', '0', '100', '50', '1.5']]))
        ->toThrow(CompilationException::class);
});

it('correctly handles apply scaling with hue, saturation and lightness', function () {
    $colorData = ['r' => 255, 'g' => 0, 'b' => 0, 'a' => 1.0, 'format' => 'rgb'];
    $result = $this->accessor->callMethod('applyScaling', [$colorData, ['$hue' => 30]]);
    expect($result['format'])->toBe('rgb');

    $result = $this->accessor->callMethod('applyScaling', [$colorData, ['$saturation' => 20]]);
    expect($result['format'])->toBe('rgb');

    $result = $this->accessor->callMethod('applyScaling', [$colorData, ['$lightness' => 20]]);
    expect($result['format'])->toBe('rgb');
});

it('correctly handles apply adjustments with various parameters', function () {
    $colorData = ['r' => 255, 'g' => 0, 'b' => 0, 'a' => 1.0, 'format' => 'rgb'];

    // Test with hue adjustment
    $result = $this->accessor->callMethod('applyAdjustments', [$colorData, ['$hue' => 60]]);
    expect($result['format'])->toBe('rgb');

    // Test with saturation adjustment
    $result = $this->accessor->callMethod('applyAdjustments', [$colorData, ['$saturation' => 20]]);
    expect($result['format'])->toBe('rgb');

    // Test with lightness adjustment
    $result = $this->accessor->callMethod('applyAdjustments', [$colorData, ['$lightness' => 20]]);
    expect($result['format'])->toBe('rgb');

    // Test with alpha adjustment
    $result = $this->accessor->callMethod('applyAdjustments', [$colorData, ['$alpha' => 0.5]]);
    expect($result['a'])->toBeCloseTo(0.5, 1);
});

it('correctly handles toSpace with unsupported space', function () {
    $result = $this->colorModule->toSpace('#ff0000', 'xyz'); // Should return original color
    expect($result)->toBe('red');
});

it('correctly handles channel method with space parameter', function () {
    expect($this->colorModule->channel('#ff0000', 'hue', 'hsl'))->toBe('0')
        ->and($this->colorModule->channel('hsl(0, 100%, 50%)', 'red', 'rgb'))->toBe('255');
});

it('correctly handles desaturate and darken methods (aliases)', function () {
    expect($this->colorModule->desaturate('#ff0000', 20))->toBe('#e61a1a')
        ->and($this->colorModule->darken('#ff0000', 20))->toBe('#990000');
});

it('correctly handles fade in and fade out methods (aliases)', function () {
    expect($this->colorModule->fadeIn('rgba(255, 0, 0, 0.3)', 0.2))->toBe('#ff000080')
        ->and($this->colorModule->fadeOut('rgba(255, 0, 0, 0.8)', 0.3))->toBe('#ff000080');
});

it('correctly handles lch creation method', function () {
    $result = $this->colorModule->lch(60, 40, 30);
    expect($result)->toBe('lch(60% 40 30)');

    $result = $this->colorModule->lch(60, 40, 30, 0.5);
    expect($result)->toBe('lch(60% 40 30 / 0.5)');
});

it('correctly handles oklch creation method', function () {
    $result = $this->colorModule->oklch(0.6, 0.15, 30);
    expect($result)->toBe('oklch(60% 0.15 30)');

    $result = $this->colorModule->oklch(0.6, 0.15, 30, 0.5);
    expect($result)->toBe('oklch(60% 0.15 30 / 0.5)');
});

it('correctly handles toGamut with clip method and different spaces', function () {
    // Test RGB clipping
    $result = $this->colorModule->toGamut('#ff0000', 'rgb');
    expect($result)->toBe('red');

    // Test HSL clipping
    $result = $this->colorModule->toGamut('hsl(0, 100%, 50%)', 'hsl');
    expect($result)->toBe('hsl(0, 100%, 50%)');

    // Test HWB clipping
    $result = $this->colorModule->toGamut('hwb(0, 0%, 0%)', 'hwb');
    expect($result)->toBe('hwb(0 0% 0%)');
});

it('correctly handles edge cases in ensureRgbFormat', function () {
    // Test with RGB format (should return as-is)
    $colorData = ['r' => 255, 'g' => 0, 'b' => 0, 'a' => 1.0, 'format' => 'rgb'];
    $result = $this->accessor->callMethod('ensureRgbFormat', [$colorData]);
    expect($result)->toEqual($colorData);

    // Test with RGBA format (should return as-is)
    $colorData = ['r' => 255, 'g' => 0, 'b' => 0, 'a' => 0.5, 'format' => 'rgba'];
    $result = $this->accessor->callMethod('ensureRgbFormat', [$colorData]);
    expect($result)->toEqual($colorData);

    // Test with HSL format (should convert to RGB)
    $colorData = ['h' => 0, 's' => 100, 'l' => 50, 'a' => 1.0, 'format' => 'hsl'];
    $result = $this->accessor->callMethod('ensureRgbFormat', [$colorData]);
    expect($result['format'])->toBe('rgb');

    // Test with HSLA format (should convert to RGB)
    $colorData = ['h' => 0, 's' => 100, 'l' => 50, 'a' => 0.5, 'format' => 'hsla'];
    $result = $this->accessor->callMethod('ensureRgbFormat', [$colorData]);
    expect($result['format'])->toBe('rgb');

    // Test with HWB format (should convert to RGB)
    $colorData = ['h' => 0, 'w' => 0, 'bl' => 50, 'a' => 1.0, 'format' => 'hwb'];
    $result = $this->accessor->callMethod('ensureRgbFormat', [$colorData]);
    expect($result['format'])->toBe('rgb');
});

it('correctly handles edge cases in formatColor with lch', function () {
    $colorData = ['l' => 60.55, 'c' => 40.77, 'h' => 30.77, 'a' => 1.0, 'format' => 'lch'];
    $result = $this->colorModule->formatColor($colorData);
    expect($result)->toBe('lch(60.55% 40.77 30.77)');

    $colorData = ['l' => 60.55, 'c' => 40.77, 'h' => 30.77, 'a' => 0.75, 'format' => 'lch'];
    $result = $this->colorModule->formatColor($colorData);
    expect($result)->toBe('lch(60.55% 40.77 30.77 / 0.75)');
});

it('correctly handles edge cases in formatColor with oklch', function () {
    $colorData = ['l' => 60.55, 'c' => 0.1545, 'h' => 30.77, 'a' => 1.0, 'format' => 'oklch'];
    $result = $this->colorModule->formatColor($colorData);
    expect($result)->toBe('oklch(60.55% 0.1545 30.77)');

    $colorData = ['l' => 60.55, 'c' => 0.1545, 'h' => 30.77, 'a' => 0.75, 'format' => 'oklch'];
    $result = $this->colorModule->formatColor($colorData);
    expect($result)->toBe('oklch(60.55% 0.1545 30.77 / 0.75)');
});

it('correctly handles edge cases in formatColor with hwb', function () {
    $colorData = ['h' => 120.7, 'w' => 25.3, 'bl' => 40.9, 'a' => 1.0, 'format' => 'hwb'];
    $result = $this->colorModule->formatColor($colorData);
    expect($result)->toBe('hwb(121 25% 41%)');

    $colorData = ['h' => 120.7, 'w' => 25.3, 'bl' => 40.9, 'a' => 0.5, 'format' => 'hwb'];
    $result = $this->colorModule->formatColor($colorData);
    expect($result)->toBe('hwb(121 25% 41% / 0.5)');
});

it('correctly handles getNamedColor method', function () {
    // Test with known color
    $result = $this->accessor->callMethod('getNamedColor', [255, 0, 0]);
    expect($result)->toBe('red');

    // Test with unknown color
    $result = $this->accessor->callMethod('getNamedColor', [254, 0, 0]);
    expect($result)->toBeNull();
});

it('correctly converts rgb to oklch', function () {
    // Red color - should have high lightness, medium chroma, hue around red
    $oklch = $this->accessor->callMethod('rgbToOklch', [255, 0, 0]);
    expect($oklch['l'])->toBeGreaterThan(50)->toBeLessThan(80)
        ->and($oklch['c'])->toBeGreaterThan(0.2)->toBeLessThan(0.4)
        ->and($oklch['h'])->toBeGreaterThan(20)->toBeLessThan(40);

    // Green color - should have high lightness, medium chroma, hue around green
    $oklch = $this->accessor->callMethod('rgbToOklch', [0, 255, 0]);
    expect($oklch['l'])->toBeGreaterThan(80)->toBeLessThan(95)
        ->and($oklch['c'])->toBeGreaterThan(0.2)->toBeLessThan(0.4)
        ->and($oklch['h'])->toBeGreaterThan(130)->toBeLessThan(150);

    // Blue color - should have medium lightness, medium chroma, hue around blue
    $oklch = $this->accessor->callMethod('rgbToOklch', [0, 0, 255]);
    expect($oklch['l'])->toBeGreaterThan(40)->toBeLessThan(50)
        ->and($oklch['c'])->toBeGreaterThan(0.2)->toBeLessThan(0.4)
        ->and($oklch['h'])->toBeGreaterThan(250)->toBeLessThan(280);

    // White color (edge case) - max lightness, zero chroma
    $oklch = $this->accessor->callMethod('rgbToOklch', [255, 255, 255]);
    expect($oklch['l'])->toBeCloseTo(100.0, 0.1)
        ->and($oklch['c'])->toBeCloseTo(0.0, 0.01);

    // Black color (edge case) - zero lightness, zero chroma
    $oklch = $this->accessor->callMethod('rgbToOklch', [0, 0, 0]);
    expect($oklch['l'])->toBeCloseTo(0.0, 0.1)
        ->and($oklch['c'])->toBeCloseTo(0.0, 0.01);

    // Gray color (edge case) - medium lightness, zero chroma
    $oklch = $this->accessor->callMethod('rgbToOklch', [128, 128, 128]);
    expect($oklch['l'])->toBeGreaterThan(50)->toBeLessThan(60)
        ->and($oklch['c'])->toBeCloseTo(0.0, 0.01);
});

it('correctly converts oklch to rgb', function () {
    // Red color conversion back (approximate values)
    $rgb = $this->accessor->callMethod('oklchToRgb', [62.8, 0.2577, 29.2]);
    expect($rgb['r'])->toBeCloseTo(255.0, 1.0)
        ->and($rgb['g'])->toBeCloseTo(0.0, 1.0)
        ->and($rgb['b'])->toBeCloseTo(0.0, 1.0);

    // Green color conversion back
    $rgb = $this->accessor->callMethod('oklchToRgb', [86.6, 0.2948, 142.5]);
    expect($rgb['r'])->toBeCloseTo(0.0, 1.0)
        ->and($rgb['g'])->toBeCloseTo(255.0, 1.0)
        ->and($rgb['b'])->toBeCloseTo(0.0, 1.0);

    // Blue color conversion back
    $rgb = $this->accessor->callMethod('oklchToRgb', [45.2, 0.313, 264.05]);
    expect($rgb['r'])->toBeCloseTo(0.0, 1.0)
        ->and($rgb['g'])->toBeCloseTo(0.0, 1.0)
        ->and($rgb['b'])->toBeCloseTo(255.0, 1.0);

    // White color (edge case)
    $rgb = $this->accessor->callMethod('oklchToRgb', [100, 0, 0]);
    expect($rgb['r'])->toBeCloseTo(255.0, 1.0)
        ->and($rgb['g'])->toBeCloseTo(255.0, 1.0)
        ->and($rgb['b'])->toBeCloseTo(255.0, 1.0);

    // Black color (edge case)
    $rgb = $this->accessor->callMethod('oklchToRgb', [0, 0, 0]);
    expect($rgb['r'])->toBeCloseTo(0.0, 1.0)
        ->and($rgb['g'])->toBeCloseTo(0.0, 1.0)
        ->and($rgb['b'])->toBeCloseTo(0.0, 1.0);

    // Gray color (edge case)
    $rgb = $this->accessor->callMethod('oklchToRgb', [53.2, 0, 0]);
    expect($rgb['r'])->toBeCloseTo(108.0, 2.0)
        ->and($rgb['g'])->toBeCloseTo(108.0, 2.0)
        ->and($rgb['b'])->toBeCloseTo(108.0, 2.0);
});

it('correctly round trips rgb to oklch to rgb', function () {
    // Test round-trip conversion for various colors
    $testColors = [
        [255, 0, 0],    // Red
        [0, 255, 0],    // Green
        [0, 0, 255],    // Blue
        [255, 255, 255], // White
        [0, 0, 0],      // Black
        [128, 128, 128], // Gray
        [255, 128, 0],  // Orange
        [128, 0, 255],  // Purple
    ];

    foreach ($testColors as $rgb) {
        $oklch = $this->accessor->callMethod('rgbToOklch', [$rgb[0], $rgb[1], $rgb[2]]);
        $rgbBack = $this->accessor->callMethod('oklchToRgb', [$oklch['l'], $oklch['c'], $oklch['h']]);

        expect($rgbBack['r'])->toBeCloseTo($rgb[0], 5.0)
            ->and($rgbBack['g'])->toBeCloseTo($rgb[1], 5.0)
            ->and($rgbBack['b'])->toBeCloseTo($rgb[2], 5.0);
    }
});

it('correctly converts rgb to lch', function () {
    // Red color
    $lch = $this->accessor->callMethod('rgbToLch', [255, 0, 0]);
    expect($lch['l'])->toBeGreaterThan(50)->toBeLessThan(70)
        ->and($lch['c'])->toBeGreaterThan(100)->toBeLessThan(150)
        ->and($lch['h'])->toBeGreaterThan(35)->toBeLessThan(45);

    // Green color
    $lch = $this->accessor->callMethod('rgbToLch', [0, 255, 0]);
    expect($lch['l'])->toBeGreaterThan(80)->toBeLessThan(95)
        ->and($lch['c'])->toBeGreaterThan(100)->toBeLessThan(150)
        ->and($lch['h'])->toBeGreaterThan(130)->toBeLessThan(140);

    // Blue color
    $lch = $this->accessor->callMethod('rgbToLch', [0, 0, 255]);
    expect($lch['l'])->toBeGreaterThan(25)->toBeLessThan(40)
        ->and($lch['c'])->toBeGreaterThan(100)->toBeLessThan(150)
        ->and($lch['h'])->toBeGreaterThan(280)->toBeLessThan(320);

    // White color (edge case) - max lightness, zero chroma
    $lch = $this->accessor->callMethod('rgbToLch', [255, 255, 255]);
    expect($lch['l'])->toBeCloseTo(100.0, 0.1)
        ->and($lch['c'])->toBeCloseTo(0.0, 0.1);

    // Black color (edge case) - zero lightness, zero chroma
    $lch = $this->accessor->callMethod('rgbToLch', [0, 0, 0]);
    expect($lch['l'])->toBeCloseTo(0.0, 0.1)
        ->and($lch['c'])->toBeCloseTo(0.0, 0.1);

    // Gray color (edge case) - medium lightness, zero chroma
    $lch = $this->accessor->callMethod('rgbToLch', [128, 128, 128]);
    expect($lch['l'])->toBeGreaterThan(50)->toBeLessThan(60)
        ->and($lch['c'])->toBeCloseTo(0.0, 0.1);
});

it('correctly converts lch to rgb', function () {
    // Red color conversion back (approximate values)
    $rgb = $this->accessor->callMethod('lchToRgb', [53.24, 104.55, 39.95]);
    expect($rgb['r'])->toBeCloseTo(255.0, 1.0)
        ->and($rgb['g'])->toBeCloseTo(0.0, 1.0)
        ->and($rgb['b'])->toBeCloseTo(0.0, 1.0);

    // Green color conversion back
    $rgb = $this->accessor->callMethod('lchToRgb', [87.73, 119.78, 136.02]);
    expect($rgb['r'])->toBeCloseTo(0.0, 1.0)
        ->and($rgb['g'])->toBeCloseTo(255.0, 1.0)
        ->and($rgb['b'])->toBeCloseTo(0.0, 1.0);

    // Blue color conversion back
    $rgb = $this->accessor->callMethod('lchToRgb', [32.3, 133.81, 306.28]);
    expect($rgb['r'])->toBeCloseTo(0.0, 1.0)
        ->and($rgb['g'])->toBeCloseTo(0.0, 1.0)
        ->and($rgb['b'])->toBeCloseTo(255.0, 1.0);

    // White color (edge case)
    $rgb = $this->accessor->callMethod('lchToRgb', [100, 0, 0]);
    expect($rgb['r'])->toBeCloseTo(255.0, 1.0)
        ->and($rgb['g'])->toBeCloseTo(255.0, 1.0)
        ->and($rgb['b'])->toBeCloseTo(255.0, 1.0);

    // Black color (edge case)
    $rgb = $this->accessor->callMethod('lchToRgb', [0, 0, 0]);
    expect($rgb['r'])->toBeCloseTo(0.0, 1.0)
        ->and($rgb['g'])->toBeCloseTo(0.0, 1.0)
        ->and($rgb['b'])->toBeCloseTo(0.0, 1.0);

    // Gray color (edge case)
    $rgb = $this->accessor->callMethod('lchToRgb', [53.59, 0, 0]);
    expect($rgb['r'])->toBeCloseTo(128.0, 2.0)
        ->and($rgb['g'])->toBeCloseTo(128.0, 2.0)
        ->and($rgb['b'])->toBeCloseTo(128.0, 2.0);
});

it('correctly round trips rgb to lch to rgb', function () {
    // Test round-trip conversion for various colors
    $testColors = [
        [255, 0, 0],    // Red
        [0, 255, 0],    // Green
        [0, 0, 255],    // Blue
        [255, 255, 255], // White
        [0, 0, 0],      // Black
        [128, 128, 128], // Gray
        [255, 128, 0],  // Orange
        [128, 0, 255],  // Purple
    ];

    foreach ($testColors as $rgb) {
        $lch = $this->accessor->callMethod('rgbToLch', [$rgb[0], $rgb[1], $rgb[2]]);
        $rgbBack = $this->accessor->callMethod('lchToRgb', [$lch['l'], $lch['c'], $lch['h']]);

        expect($rgbBack['r'])->toBeCloseTo($rgb[0], 5.0)
            ->and($rgbBack['g'])->toBeCloseTo($rgb[1], 5.0)
            ->and($rgbBack['b'])->toBeCloseTo($rgb[2], 5.0);
    }
});
