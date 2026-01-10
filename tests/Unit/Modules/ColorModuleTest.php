<?php

declare(strict_types=1);

use DartSass\Exceptions\CompilationException;
use DartSass\Modules\ColorFormat;
use DartSass\Modules\ColorModule;
use Tests\ReflectionAccessor;

beforeEach(function () {
    $this->colorModule = new ColorModule();
    $this->accessor    = new ReflectionAccessor($this->colorModule);
});

describe('Color Parsing', function () {
    describe('Named colors', function () {
        it('parses named colors correctly', function () {
            $red = $this->colorModule->parseColor('red');

            expect($red['r'])->toBe(255)
                ->and($red['g'])->toBe(0)
                ->and($red['b'])->toBe(0)
                ->and($red['a'])->toBe(1.0)
                ->and($red['format'])->toBe(ColorFormat::RGB->value);
        });
    });

    describe('HEX format', function () {
        it('parses hex3 color', function () {
            $result = $this->colorModule->parseColor('#f00');

            expect($result['r'])->toBe(255)
                ->and($result['g'])->toBe(0)
                ->and($result['b'])->toBe(0)
                ->and($result['a'])->toBe(1.0)
                ->and($result['format'])->toBe(ColorFormat::RGB->value);
        });

        it('parses hex4 color with alpha', function () {
            $result = $this->colorModule->parseColor('#f00d');

            expect($result['r'])->toBe(255)
                ->and($result['g'])->toBe(0)
                ->and($result['b'])->toBe(0)
                ->and($result['a'])->toBe(221 / 255)
                ->and($result['format'])->toBe(ColorFormat::RGBA->value);
        });

        it('parses hex6 color', function () {
            $result = $this->colorModule->parseColor('#ff0000');

            expect($result['r'])->toBe(255)
                ->and($result['g'])->toBe(0)
                ->and($result['b'])->toBe(0)
                ->and($result['a'])->toBe(1.0)
                ->and($result['format'])->toBe(ColorFormat::RGB->value);
        });

        it('parses hex8 color with alpha', function () {
            $result = $this->colorModule->parseColor('#ff000080');

            expect($result['r'])->toBe(255)
                ->and($result['g'])->toBe(0)
                ->and($result['b'])->toBe(0)
                ->and($result['a'])->toBe(128 / 255.0)
                ->and($result['format'])->toBe(ColorFormat::RGBA->value);
        });
    });

    describe('RGB format', function () {
        it('parses rgb color', function () {
            $result = $this->colorModule->parseColor('rgb(255, 0, 0)');

            expect($result['r'])->toBe(255)
                ->and($result['g'])->toBe(0)
                ->and($result['b'])->toBe(0)
                ->and($result['a'])->toBe(1.0)
                ->and($result['format'])->toBe(ColorFormat::RGB->value);
        });

        it('parses rgba color with alpha', function () {
            $result = $this->colorModule->parseColor('rgba(255, 0, 0, 0.5)');

            expect($result['r'])->toBe(255)
                ->and($result['g'])->toBe(0)
                ->and($result['b'])->toBe(0)
                ->and($result['a'])->toBe(0.5)
                ->and($result['format'])->toBe(ColorFormat::RGBA->value);
        });
    });

    describe('HSL format', function () {
        it('parses hsl color', function () {
            $result = $this->colorModule->parseColor('hsl(0, 100%, 50%)');

            expect($result['h'])->toEqual(0)
                ->and($result['s'])->toEqual(100)
                ->and($result['l'])->toEqual(50)
                ->and($result['a'])->toBe(1.0)
                ->and($result['format'])->toBe(ColorFormat::HSL->value);
        });

        it('parses hsla color with alpha', function () {
            $result = $this->colorModule->parseColor('hsla(0, 100%, 50%, 0.5)');

            expect($result['h'])->toEqual(0)
                ->and($result['s'])->toEqual(100)
                ->and($result['l'])->toEqual(50)
                ->and($result['a'])->toBe(0.5)
                ->and($result['format'])->toBe(ColorFormat::HSLA->value);
        });
    });

    describe('HWB format', function () {
        it('parses hwb color with alpha', function () {
            $result = $this->colorModule->parseColor('hwb(0 0% 50% / 0.5)');

            expect($result['h'])->toEqual(0)
                ->and($result['w'])->toEqual(0)
                ->and($result['bl'])->toEqual(50)
                ->and($result['a'])->toEqual(0.5)
                ->and($result['format'])->toBe(ColorFormat::HWB->value);
        });
    });

    describe('LCH format', function () {
        it('parses lch color', function () {
            $result = $this->colorModule->parseColor('lch(60% 40 30deg)');

            expect($result['l'])->toEqual(60)
                ->and($result['c'])->toEqual(40)
                ->and($result['h'])->toEqual(30)
                ->and($result['a'])->toBe(1.0)
                ->and($result['format'])->toBe(ColorFormat::LCH->value);
        });

        it('parses lch color with alpha', function () {
            $result = $this->colorModule->parseColor('lch(60% 40 30deg / 0.5)');

            expect($result['l'])->toEqual(60)
                ->and($result['c'])->toEqual(40)
                ->and($result['h'])->toEqual(30)
                ->and($result['a'])->toBe(0.5)
                ->and($result['format'])->toBe(ColorFormat::LCH->value);
        });
    });

    describe('OKLCH format', function () {
        it('parses oklch color', function () {
            $result = $this->colorModule->parseColor('oklch(60% 0.15 30)');

            expect($result['l'])->toEqual(60)
                ->and($result['c'])->toEqual(0.15)
                ->and($result['h'])->toEqual(30)
                ->and($result['a'])->toBe(1.0)
                ->and($result['format'])->toBe(ColorFormat::OKLCH->value);
        });

        it('parses oklch color with alpha', function () {
            $result = $this->colorModule->parseColor('oklch(60% 0.15 30 / 0.5)');

            expect($result['l'])->toEqual(60)
                ->and($result['c'])->toEqual(0.15)
                ->and($result['h'])->toEqual(30)
                ->and($result['a'])->toBe(0.5)
                ->and($result['format'])->toBe(ColorFormat::OKLCH->value);
        });
    });

    describe('LAB format', function () {
        it('parses lab color', function () {
            $result = $this->colorModule->parseColor('lab(50% 20 -10)');

            expect($result['lab_l'])->toBe(50.0)
                ->and($result['lab_a'])->toBe(20.0)
                ->and($result['lab_b'])->toBe(-10.0)
                ->and($result['a'])->toBe(1.0)
                ->and($result['format'])->toBe(ColorFormat::LAB->value);
        });

        it('parses laba color with alpha', function () {
            $result = $this->colorModule->parseColor('lab(50% 20 -10 / 0.5)');

            expect($result['lab_l'])->toBe(50.0)
                ->and($result['lab_a'])->toBe(20.0)
                ->and($result['lab_b'])->toBe(-10.0)
                ->and($result['a'])->toBe(0.5)
                ->and($result['format'])->toBe(ColorFormat::LABA->value);
        });
    });

    describe('XYZ format', function () {
        it('parses xyz color with valid coordinates', function () {
            $result = $this->colorModule->parseColor('color(xyz 0.5 0.3 0.2)');

            expect($result['x'])->toBe(0.5)
                ->and($result['y'])->toBe(0.3)
                ->and($result['z'])->toBe(0.2)
                ->and($result['a'])->toBe(1.0)
                ->and($result['format'])->toBe(ColorFormat::XYZ->value);
        });

        it('handles parseXyzaColor method directly', function () {
            $result = $this->colorModule->parseColor('color(xyz 0.5 0.3 0.2 / 0.8)');

            expect($result['x'])->toBe(0.5)
                ->and($result['y'])->toBe(0.3)
                ->and($result['z'])->toBe(0.2)
                ->and($result['a'])->toBe(0.8)
                ->and($result['format'])->toBe(ColorFormat::XYZA->value);
        });
    });

    it('throws exception for invalid color name', function () {
        expect(fn() => $this->colorModule->parseColor('invalid'))
            ->toThrow(CompilationException::class);
    });
})->covers(ColorModule::class);

describe('Color Formatting', function () {
    it('formats rgb to named color', function () {
        $colorData = ['r' => 255, 'g' => 0, 'b' => 0, 'a' => 1.0, 'format' => ColorFormat::RGB->value];
        expect($this->colorModule->formatColor($colorData))->toBe('red');
    });

    it('formats rgba with alpha', function () {
        $colorData = ['r' => 255, 'g' => 0, 'b' => 0, 'a' => 0.5, 'format' => ColorFormat::RGBA->value];
        expect($this->colorModule->formatColor($colorData))->toBe('#ff000080');
    });

    it('formats hsl color', function () {
        $colorData = ['h' => 0, 's' => 100, 'l' => 50, 'a' => 1.0, 'format' => ColorFormat::HSL->value];
        expect($this->colorModule->formatColor($colorData))->toBe('hsl(0, 100%, 50%)');
    });

    it('formats hsla color with alpha', function () {
        $colorData = ['h' => 0, 's' => 100, 'l' => 50, 'a' => 0.5, 'format' => ColorFormat::HSLA->value];
        expect($this->colorModule->formatColor($colorData))->toBe('hsla(0, 100%, 50%, 0.5)');
    });

    it('formats hwb color', function () {
        $colorData = ['h' => 120.7, 'w' => 25.3, 'bl' => 40.9, 'a' => 1.0, 'format' => ColorFormat::HWB->value];
        expect($this->colorModule->formatColor($colorData))->toBe('hwb(121 25% 41%)');
    });

    it('formats lch color', function () {
        $colorData = ['l' => 60, 'c' => 40, 'h' => 30, 'a' => 1.0, 'format' => ColorFormat::LCH->value];
        expect($this->colorModule->formatColor($colorData))->toBe('lch(60% 40 30)');
    });

    it('formats lch color with alpha', function () {
        $colorData = ['l' => 60, 'c' => 40, 'h' => 30, 'a' => 0.5, 'format' => ColorFormat::LCH->value];
        expect($this->colorModule->formatColor($colorData))->toBe('lch(60% 40 30 / 0.5)');
    });

    it('formats oklch color', function () {
        $colorData = ['l' => 60, 'c' => 0.15, 'h' => 30, 'a' => 1.0, 'format' => ColorFormat::OKLCH->value];
        expect($this->colorModule->formatColor($colorData))->toBe('oklch(60% 0.15 30)');
    });

    it('formats oklch color with alpha', function () {
        $colorData = ['l' => 60, 'c' => 0.15, 'h' => 30, 'a' => 0.5, 'format' => ColorFormat::OKLCH->value];
        expect($this->colorModule->formatColor($colorData))->toBe('oklch(60% 0.15 30 / 0.5)');
    });

    it('formats lab color', function () {
        $colorData = ['lab_l' => 50.5, 'lab_a' => 20.3, 'lab_b' => -10.7, 'a' => 1.0, 'format' => ColorFormat::LAB->value];
        $result = $this->colorModule->formatColor($colorData);

        expect($result)->toBe('lab(50.5% 20.3 -10.7)');
    });

    it('formats laba color with alpha', function () {
        $colorData = ['lab_l' => 50.5, 'lab_a' => 20.3, 'lab_b' => -10.7, 'a' => 0.75, 'format' => ColorFormat::LABA->value];
        $result = $this->colorModule->formatColor($colorData);

        expect($result)->toBe('lab(50.5% 20.3 -10.7 / 0.75)');
    });

    it('formats xyz color', function () {
        $colorData = ['x' => 0.5, 'y' => 0.3, 'z' => 0.2, 'a' => 1.0, 'format' => ColorFormat::XYZ->value];
        expect($this->colorModule->formatColor($colorData))->toBe('color(xyz 0.5 0.3 0.2)');
    });

    describe('Round-trip parsing and formatting', function () {
        it('handles oklch round-trip', function () {
            $originalColor = 'oklch(60% 0.15 30)';
            $parsed = $this->colorModule->parseColor($originalColor);
            $formatted = $this->colorModule->formatColor($parsed);
            expect($formatted)->toBe($originalColor);
        });

        it('handles lab round-trip', function () {
            $originalColor = 'lab(50% 20 -10)';
            $parsed = $this->colorModule->parseColor($originalColor);
            $formatted = $this->colorModule->formatColor($parsed);

            expect($formatted)->toBe($originalColor);
        });

        it('handles laba round-trip', function () {
            $originalColor = 'lab(50% 20 -10 / 0.5)';
            $parsed = $this->colorModule->parseColor($originalColor);
            $formatted = $this->colorModule->formatColor($parsed);

            expect($formatted)->toBe($originalColor);
        });
    });
})->covers(ColorModule::class);

describe('Color Manipulation', function () {
    describe('adjust()', function () {
        it('adjusts red channel', function () {
            expect($this->colorModule->adjust('#ff0000', ['$red' => -50]))->toBe('#cd0000');
        });

        it('adjusts multiple RGB channels', function () {
            $result = $this->colorModule->adjust('#ff0000', [
                '$red'   => 15,
                '$green' => 5,
                '$blue'  => 25,
            ]);
            expect($result)->toBe('#ff0519');
        });

        it('adjusts HSL channels', function () {
            $colorData = ['r' => 255, 'g' => 0, 'b' => 0, 'a' => 1.0, 'format' => ColorFormat::RGB->value];
            $result = $this->accessor->callMethod('applyAdjustments', [$colorData, ['$hue' => 60]]);
            expect($result['format'])->toBe(ColorFormat::RGB->value);

            $result = $this->accessor->callMethod('applyAdjustments', [$colorData, ['$saturation' => 20]]);
            expect($result['format'])->toBe(ColorFormat::RGB->value);

            $result = $this->accessor->callMethod('applyAdjustments', [$colorData, ['$lightness' => 20]]);
            expect($result['format'])->toBe(ColorFormat::RGB->value);

            $result = $this->accessor->callMethod('applyAdjustments', [$colorData, ['$alpha' => 0.5]]);
            expect($result['a'])->toBeCloseTo(0.5, 1);
        });

        it('adjusts HWB channels', function () {
            expect($this->colorModule->adjust('#ff0000', ['$whiteness' => 20]))->toBe('#ff3333')
                ->and($this->colorModule->adjust('#ff0000', ['$blackness' => 20]))->toBe('#cc0000');
        });

        it('adjusts XYZ channels', function () {
            expect($this->colorModule->adjust('#ff0000', ['$x' => 30, '$space' => ColorFormat::XYZ->value]))->toBe('#ff0023')
                ->and($this->colorModule->adjust('#ff0000', ['$y' => 20, '$space' => ColorFormat::XYZ->value]))->toBe('#d9a500')
                ->and($this->colorModule->adjust('#ff0000', ['$z' => 10, '$space' => ColorFormat::XYZ->value]))->toBe('#f90d5b');
        });

        it('adjusts LCH chroma', function () {
            expect($this->colorModule->adjust('#ff0000', ['$chroma' => 20, '$space' => ColorFormat::LCH->value]))->toBe('#ff3333');
        });

        it('adjusts OKLCH parameters', function () {
            $result = $this->colorModule->adjust('#ff0000', ['$space' => ColorFormat::OKLCH->value, '$chroma' => 0.1]);
            expect($result)->toBe('red');
        });

        it('handles lab color adjustments', function () {
            $result = $this->colorModule->adjust('lab(50% 20 -10)', ['$alpha' => 0.5]);
            expect($result)->toMatch('/^#|red|rgb/');
        });

        it('adjusts all XYZ coordinates simultaneously', function () {
            $result = $this->colorModule->adjust('#ff0000', [
                '$x' => 10,
                '$y' => 10,
                '$z' => 10,
                '$space' => ColorFormat::XYZ->value,
            ]);
            expect($result)->toMatch('/^#[0-9a-f]{6}$/');
        });

        it('throws exception for unknown parameter', function () {
            expect(fn() => $this->colorModule->adjust('#ff0000', ['$unknown' => 10]))
                ->toThrow(CompilationException::class, 'Unknown adjustment parameter');
        });

        it('chroma adjustment triggers array_merge with alpha preservation', function () {
            $result = $this->colorModule->adjust('rgba(100, 150, 200, 0.4)', ['$chroma' => 20]);
            $parsed = $this->colorModule->parseColor($result);
            expect($parsed['a'])->toBeCloseTo(0.4)
                ->and($parsed['format'])->toBe(ColorFormat::RGBA->value);
        });

        it('chroma adjustment requires complete RGB result', function () {
            $result = $this->colorModule->adjust('#ff0000', ['$chroma' => -30]);
            $parsed = $this->colorModule->parseColor($result);
            expect($parsed)->toHaveKeys(['r', 'g', 'b', 'a', 'format'])
                ->and($parsed['format'])->toBe(ColorFormat::RGB->value);
        });
    });

    describe('change()', function () {
        it('changes RGB channels', function () {
            expect($this->colorModule->change('#ff0000', ['$red' => 128]))->toBe('maroon')
                ->and($this->colorModule->change('#ff0000', ['$green' => 128]))->toBe('#ff8000')
                ->and($this->colorModule->change('#ff0000', ['$blue' => 128]))->toBe('#ff0080');
        });

        it('changes HSL channels', function () {
            expect($this->colorModule->change('#ff0000', ['$hue' => 120]))->toBe('lime')
                ->and($this->colorModule->change('#ff0000', ['$saturation' => 50]))->toBe('#bf4040')
                ->and($this->colorModule->change('#ff0000', ['$lightness' => 50]))->toBe('red');
        });

        it('changes alpha channel', function () {
            expect($this->colorModule->change('#ff0000', ['$alpha' => 0.5]))->toBe('#ff000080');
        });

        it('handles lab color changes', function () {
            $result = $this->colorModule->change('lab(50% 20 -10)', ['$alpha' => 0.5]);
            expect($result)->toMatch('/^#|red|rgb/');
        });

        it('throws exception for unknown parameter', function () {
            expect(fn() => $this->colorModule->change('#ff0000', ['$unknown' => 10]))
                ->toThrow(CompilationException::class, 'Unknown changing parameter');
        });

        it('clamps red channel from 0 in applyChanges', function () {
            $result = $this->colorModule->change('#00ff00', ['$red' => -50]);
            $parsed = $this->colorModule->parseColor($result);
            expect($parsed['r'])->toBe(0);

            $result = $this->colorModule->change('#00ff00', ['$red' => 300]);
            $parsed = $this->colorModule->parseColor($result);
            expect($parsed['r'])->toBe(255);
        });

        it('clamps green channel from 0 in applyChanges', function () {
            $result = $this->colorModule->change('#ff0000', ['$green' => -50]);
            $parsed = $this->colorModule->parseColor($result);
            expect($parsed['g'])->toBe(0);

            $result = $this->colorModule->change('#ff0000', ['$green' => 300]);
            $parsed = $this->colorModule->parseColor($result);
            expect($parsed['g'])->toBe(255);
        });

        it('clamps blue channel from 0 in applyChanges', function () {
            $result = $this->colorModule->change('#ff0000', ['$blue' => -50]);
            $parsed = $this->colorModule->parseColor($result);
            expect($parsed['b'])->toBe(0);

            $result = $this->colorModule->change('#ff0000', ['$blue' => 300]);
            $parsed = $this->colorModule->parseColor($result);
            expect($parsed['b'])->toBe(255);
        });
    });

    describe('scale()', function () {
        it('scales RGB channels', function () {
            expect($this->colorModule->scale('#ff0000', ['$red' => 50]))->toBe('red')
                ->and($this->colorModule->scale('#ff0000', ['$green' => 50]))->toBe('#ff8000')
                ->and($this->colorModule->scale('#ff0000', ['$blue' => 50]))->toBe('#ff0080');
        });

        it('scales HSL channels', function () {
            expect($this->colorModule->scale('#ff0000', ['$hue' => 30]))->toBe('#33ff00')
                ->and($this->colorModule->scale('#ff0000', ['$saturation' => -50]))->toBe('#bf4040')
                ->and($this->colorModule->scale('#ff0000', ['$lightness' => 20]))->toBe('#ff3333');
        });

        it('scales alpha channel', function () {
            expect($this->colorModule->scale('#ffd700', ['$alpha' => 20]))->toBe('gold');
        });

        it('handles scale channel with positive and negative amounts', function () {
            expect($this->accessor->callMethod('scaleChannel', [100, 50, 0, 255]))->toBeGreaterThan(100)
                ->and($this->accessor->callMethod('scaleChannel', [100, -50, 0, 255]))->toBeLessThan(100);
        });

        it('handles apply scaling with hue, saturation and lightness', function () {
            $colorData = ['r' => 255, 'g' => 0, 'b' => 0, 'a' => 1.0, 'format' => ColorFormat::RGB->value];
            $result = $this->accessor->callMethod('applyScaling', [$colorData, ['$hue' => 30]]);
            expect($result['format'])->toBe(ColorFormat::RGB->value);

            $result = $this->accessor->callMethod('applyScaling', [$colorData, ['$saturation' => 20]]);
            expect($result['format'])->toBe(ColorFormat::RGB->value);

            $result = $this->accessor->callMethod('applyScaling', [$colorData, ['$lightness' => 20]]);
            expect($result['format'])->toBe(ColorFormat::RGB->value);
        });

        it('handles lab color scaling operations', function () {
            $result = $this->colorModule->scale('lab(50% 20 -10)', ['$alpha' => 20]);
            expect($result)->toMatch('/^#|red|rgb/');
        });

        it('throws exception for unknown parameter', function () {
            expect(fn() => $this->colorModule->scale('#ff0000', ['$unknown' => 10]))
                ->toThrow(CompilationException::class, 'Unknown scaling parameter');
        });

        it('scales green channel from 0 minimum in applyScaling', function () {
            $result = $this->colorModule->scale('#ff0000', ['$green' => -50]);
            $parsed = $this->colorModule->parseColor($result);
            expect($parsed['g'])->toBe(0);

            $result = $this->colorModule->scale('#ff0000', ['$green' => 50]);
            $parsed = $this->colorModule->parseColor($result);
            expect($parsed['g'])->toBeGreaterThan(0);
        });

        it('scales blue channel from 0 minimum in applyScaling', function () {
            $result = $this->colorModule->scale('#00ff00', ['$blue' => -50]);
            $parsed = $this->colorModule->parseColor($result);
            expect($parsed['b'])->toBe(0);

            $result = $this->colorModule->scale('#00ff00', ['$blue' => 50]);
            $parsed = $this->colorModule->parseColor($result);
            expect($parsed['b'])->toBeGreaterThan(0);
        });
    });

    describe('lighten() / darken()', function () {
        it('lightens color', function () {
            expect($this->colorModule->lighten('#007bff', 10))->toBe('#3395ff');
        });

        it('darkens color', function () {
            expect($this->colorModule->darken('#b37399', 20))->toBe('#7c4465');
        });
    });

    describe('saturate() / desaturate()', function () {
        it('saturates color', function () {
            expect($this->colorModule->saturate('#0e4982', 30))->toBe('#004990');
        });

        it('desaturates color', function () {
            expect($this->colorModule->desaturate('#ff0000', 20))->toBe('#e61a1a');
        });
    });

    describe('opacity operations', function () {
        it('opacifies color', function () {
            expect($this->colorModule->opacify('rgba(255, 0, 0, 0.5)', 0.5))->toBe('red');
        });

        it('transparentizes color', function () {
            expect($this->colorModule->transparentize('#ff0000', 1.0))->toBe('#ff000000');
        });

        it('fades in color', function () {
            expect($this->colorModule->fadeIn('rgba(255, 0, 0, 0.5)', 0.3))->toBe('#ff0000cc');
        });

        it('fades out color', function () {
            expect($this->colorModule->fadeOut('rgba(255, 0, 0, 0.8)', 0.3))->toBe('#ff000080');
        });
    });
})->covers(ColorModule::class);

describe('Color Transformations', function () {
    describe('toSpace()', function () {
        it('converts RGB to HSL', function () {
            expect($this->colorModule->toSpace('#ff0000', ColorFormat::HSL->value))->toBe('hsl(0, 100%, 50%)');
        });

        it('converts HSL to RGB', function () {
            expect($this->colorModule->toSpace('hsl(0, 100%, 50%)', ColorFormat::RGB->value))->toBe('red');
        });

        it('converts RGB to itself', function () {
            expect($this->colorModule->toSpace('#ff0000', ColorFormat::RGB->value))->toBe('red');
        });

        it('converts to OKLCH', function () {
            $result = $this->colorModule->toSpace('#ff0000', ColorFormat::OKLCH->value);
            expect($result)->toMatch('/^oklch\\(/');

            $result = $this->colorModule->toSpace('oklch(60% 0.15 30)', ColorFormat::RGB->value);
            expect($result)->toBe('#ca5747');
        });

        it('converts to XYZ', function () {
            $result = $this->colorModule->toSpace('#ff0000', ColorFormat::XYZ->value);
            expect($result)->toMatch('/^color\\(xyz /');
        });

        it('converts lab color to rgb space', function () {
            $result = $this->colorModule->toSpace('lab(50% 20 -10)', ColorFormat::RGB->value);
            expect($result)->toMatch('/^#|red|rgb/');
        });

        it('converts lab color to lab space (no change)', function () {
            $result = $this->colorModule->toSpace('lab(50% 20 -10)', ColorFormat::LAB->value);
            expect($result)->toBe('lab(50% 20 -10)');
        });

        it('converts rgb color to lab space', function () {
            $result = $this->colorModule->toSpace('#ff0000', ColorFormat::LAB->value);
            expect($result)->toMatch('/^lab\(/');
        });

        it('converts laba color to rgb space', function () {
            $result = $this->colorModule->toSpace('lab(50% 20 -10 / 0.5)', ColorFormat::RGB->value);
            expect($result)->toMatch('/^#|red|rgb/');
        });

        it('handles convertToSpace with lab target from lab source', function () {
            $colorData = ['lab_l' => 50, 'lab_a' => 20, 'lab_b' => -10, 'a' => 1.0, 'format' => ColorFormat::LAB->value];
            $result = $this->accessor->callMethod('convertToSpace', [$colorData, ColorFormat::LAB->value]);

            expect($result['lab_l'])->toBe(50)
                ->and($result['lab_a'])->toBe(20)
                ->and($result['lab_b'])->toBe(-10)
                ->and($result['format'])->toBe(ColorFormat::LAB->value);
        });

        it('handles convertToSpace with rgb target from lch source', function () {
            $colorData = ['l' => 60, 'c' => 40, 'h' => 30, 'a' => 1.0, 'format' => ColorFormat::LCH->value];
            $result = $this->accessor->callMethod('convertToSpace', [$colorData, ColorFormat::RGB->value]);

            expect($result)->toHaveKeys(['r', 'g', 'b', 'a'])
                ->and($result['format'])->toBe(ColorFormat::RGB->value)
                ->and($result['r'])->toBeGreaterThanOrEqual(0)
                ->and($result['r'])->toBeLessThanOrEqual(255)
                ->and($result['g'])->toBeGreaterThanOrEqual(0)
                ->and($result['g'])->toBeLessThanOrEqual(255)
                ->and($result['b'])->toBeGreaterThanOrEqual(0)
                ->and($result['b'])->toBeLessThanOrEqual(255);
        });

        it('handles convertToSpace with rgb target from lab source', function () {
            $colorData = ['lab_l' => 50, 'lab_a' => 20, 'lab_b' => -10, 'a' => 1.0, 'format' => ColorFormat::LAB->value];
            $result = $this->accessor->callMethod('convertToSpace', [$colorData, ColorFormat::RGB->value]);

            expect($result)->toHaveKeys(['r', 'g', 'b', 'a'])
                ->and($result['format'])->toBe(ColorFormat::RGB->value);
        });

        it('handles convertToSpace with lab target from rgb source', function () {
            $colorData = ['r' => 255, 'g' => 0, 'b' => 0, 'a' => 1.0, 'format' => ColorFormat::RGB->value];
            $result = $this->accessor->callMethod('convertToSpace', [$colorData, ColorFormat::LAB->value]);

            expect($result)->toHaveKeys(['lab_l', 'lab_a', 'lab_b', 'a'])
                ->and($result['format'])->toBe(ColorFormat::LAB->value);
        });

        it('handles toSpace with xyz space', function () {
            $result = $this->colorModule->toSpace('#ff0000', ColorFormat::XYZ->value);
            expect($result)->toMatch('/^color\\(xyz /');
        });

        it('handles toSpace without space', function () {
            $result = $this->colorModule->toSpace('#ff0000');
            expect($result)->toBe('red');
        });

        it('converts to all color spaces without fall-through', function () {
            $spaces = [
                ColorFormat::HSL->value,
                ColorFormat::HWB->value,
                ColorFormat::LAB->value,
                ColorFormat::LCH->value,
                ColorFormat::OKLCH->value,
                ColorFormat::RGB->value,
                ColorFormat::XYZ->value,
            ];

            foreach ($spaces as $targetSpace) {
                $result = $this->colorModule->toSpace('#ff0000', $targetSpace);
                expect($result)->not->toBeEmpty()
                    ->and($result)->not->toBe('#ff0000');
            }
        });
    });

    describe('toGamut()', function () {
        it('converts color to gamut', function () {
            expect($this->colorModule->toGamut('#ff0000'))->toBe('red');
        });

        it('supports different color spaces', function () {
            expect($this->colorModule->toGamut('#ff0000', ColorFormat::HSL->value))->toBe('red')
                ->and($this->colorModule->toGamut('#ff0000', ColorFormat::HWB->value))->toBe('red')
                ->and($this->colorModule->toGamut('#ff0000', ColorFormat::RGB->value))->toBe('red');
        });

        it('handles lab color to gamut conversion', function () {
            $result = $this->colorModule->toGamut('lab(50% 20 -10)');
            expect($result)->toMatch('/^lab\(/');
        });

        it('handles negative hue normalization in HSL space', function () {
            $colorData = ['h' => -60, 's' => 100, 'l' => 50, 'a' => 1.0, 'format' => ColorFormat::HSL->value];
            $result = $this->colorModule->toGamut($this->colorModule->formatColor($colorData), ColorFormat::HSL->value);
            expect($result)->toMatch('/^hsl\(/');
        });

        it('normalizes HWB with sum exceeding 100%', function () {
            $colorData = ['h' => 0, 'w' => 60, 'bl' => 60, 'a' => 1.0, 'format' => ColorFormat::HWB->value];
            $result = $this->colorModule->toGamut($this->colorModule->formatColor($colorData), ColorFormat::HWB->value);
            expect($result)->toMatch('/^hwb\(/');
        });

        it('normalizes HWB whiteness and blackness correctly in toGamut', function () {
            $color = 'hwb(0 80% 40%)';

            $result = $this->colorModule->toGamut($color, ColorFormat::HWB->value);

            $parsed = $this->colorModule->parseColor($result);

            expect($parsed['w'] + $parsed['bl'])->toEqual(100.0)
                ->and($parsed['w'])->toBeGreaterThan(50.0)
                ->and($parsed['bl'])->toBeGreaterThan(30.0);
        });

        it('throws exception for unsupported method', function () {
            expect(fn() => $this->colorModule->toGamut('#ff0000', null, 'invalid'))
                ->toThrow(CompilationException::class, 'Only \'clip\' method is currently supported');
        });
    });

    describe('complement()', function () {
        it('returns complement color', function () {
            expect($this->colorModule->complement('#ff0000'))->toBe('cyan')
                ->and($this->colorModule->complement('#008000'))->toBe('purple');
        });

        it('supports different color spaces', function () {
            expect($this->colorModule->complement('#6b717f', ColorFormat::HSL->value))->toBe('#7f796b')
                ->and($this->colorModule->complement('#008000', ColorFormat::HWB->value))->toBe('purple')
                ->and($this->colorModule->complement('#ff0000', ColorFormat::LCH->value))->toBe('#00a1f3')
                ->and($this->colorModule->complement('#6b717f', ColorFormat::OKLCH->value))->toBe('#777162');
        });

        it('handles lab color complement operation', function () {
            $result = $this->colorModule->complement('lab(50% 20 -10)');
            expect($result)->toMatch('/^#|red|rgb|lab\(/');
        });

        it('preserves alpha channel in complement with different spaces', function () {
            $result = $this->colorModule->complement('rgba(255, 0, 0, 0.5)', ColorFormat::HSL->value);
            expect($result)->toMatch('/^#/');

            $parsed = $this->colorModule->parseColor($result);
            expect($parsed['a'])->toBeCloseTo(0.5, 10);
        });

        it('handles complement with HWB space correctly', function () {
            $result = $this->colorModule->complement('#ff0000', ColorFormat::HWB->value);
            expect($result)->toBe('cyan');
        });

        it('handles complement with OKLCH space correctly', function () {
            $result = $this->colorModule->complement('#ff0000', ColorFormat::OKLCH->value);
            expect($result)->toBe('#00a9db');
        });

        it('handles complement with LCH space correctly', function () {
            $result = $this->colorModule->complement('#ff0000', ColorFormat::LCH->value);
            expect($result)->toBe('#00a1f3');
        });

        it('throws exception for non-polar space', function () {
            expect(fn() => $this->colorModule->complement('#ff0000', ColorFormat::XYZ->value))
                ->toThrow(CompilationException::class, 'not a polar color space');
        });
    });

    describe('invert()', function () {
        it('inverts color', function () {
            expect($this->colorModule->invert('#ff0000'))->toBe('cyan')
                ->and($this->colorModule->invert('#ffffff'))->toBe('black')
                ->and($this->colorModule->invert('#000000'))->toBe('white');
        });

        it('supports different color spaces', function () {
            expect($this->colorModule->invert('#ff0000', 100, ColorFormat::HWB->value))->toBe('cyan')
                ->and($this->colorModule->invert('#ff0000', 100, ColorFormat::HSL->value))->toBe('cyan');
        });

        it('handles lab color inversion', function () {
            $result = $this->colorModule->invert('lab(50% 20 -10)');
            expect($result)->toMatch('/^#|red|rgb|white|black/');
        });

        it('inverts color in HSL space correctly', function () {
            $result = $this->colorModule->invert('#ff0000', 100, ColorFormat::HSL->value);
            expect($result)->toBe('cyan');
        });

        it('inverts color in HWB space with all RGB channels', function () {
            $result = $this->colorModule->invert('#ff0000', 100, ColorFormat::HWB->value);

            expect($result)->toBe('cyan');

            $parsed = $this->colorModule->parseColor($result);
            expect($parsed)->toHaveKey('r')
                ->and($parsed)->toHaveKey('g')
                ->and($parsed)->toHaveKey('b');
        });
    });

    describe('grayscale()', function () {
        it('converts to grayscale', function () {
            expect($this->colorModule->grayscale('#ff0000'))->toBe('grey')
                ->and($this->colorModule->grayscale('#ffffff'))->toBe('white')
                ->and($this->colorModule->grayscale('#000000'))->toBe('black');
        });
    });

    describe('adjustHue()', function () {
        it('adjusts hue correctly', function () {
            expect($this->colorModule->adjustHue('#ff0000', 60))->toBe('yellow')
                ->and($this->colorModule->adjustHue('hsl(0, 100%, 50%)', 120))->toBe('lime')
                ->and($this->colorModule->adjustHue('#0000ff', -120))->toBe('lime');
        });
    });
})->covers(ColorModule::class);

describe('Color Mixing', function () {
    it('mixes two colors', function () {
        expect($this->colorModule->mix('#ff0000', '#0000ff'))->toBe('purple');
    });

    it('handles weight as percentage', function () {
        $resultPercent = $this->colorModule->mix('#ff0000', '#0000ff', 75);
        $resultDecimal = $this->colorModule->mix('#ff0000', '#0000ff', 0.75);
        expect($resultPercent)->toBe($resultDecimal);
    });

    it('clamps weight to valid range', function () {
        $resultPercent = $this->colorModule->mix('#ff0000', '#0000ff', 50);
        $resultDecimal = $this->colorModule->mix('#ff0000', '#0000ff');
        expect($resultPercent)->toBe($resultDecimal);

        $resultLow = $this->colorModule->mix('#ff0000', '#0000ff', -1);
        $resultMin = $this->colorModule->mix('#ff0000', '#0000ff', 0);
        expect($resultLow)->toBe($resultMin);
    });

    it('handles lab color mixing', function () {
        $result = $this->colorModule->mix('lab(50% 20 -10)', 'lab(60% 30 5)');
        expect($result)->toMatch('/^#|red|rgb/');
    });
})->covers(ColorModule::class);

describe('Color Channels', function () {
    describe('channel()', function () {
        it('returns RGB channels', function () {
            expect($this->colorModule->channel('#ff0000', 'red'))->toBe('255')
                ->and($this->colorModule->channel('#ff0000', 'green'))->toBe('0')
                ->and($this->colorModule->channel('#ff0000', 'blue'))->toBe('0');
        });

        it('returns HSL channels', function () {
            expect($this->colorModule->channel('hsl(0, 100%, 50%)', 'hue'))->toBe('0')
                ->and($this->colorModule->channel('hsl(0, 100%, 50%)', 'saturation'))->toBe('100%')
                ->and($this->colorModule->channel('hsl(0, 100%, 50%)', 'lightness'))->toBe('50%');
        });

        it('returns alpha channel', function () {
            expect($this->colorModule->channel('#ff0000', 'alpha'))->toBe('1');
        });

        it('supports space parameter', function () {
            expect($this->colorModule->channel('#ff0000', 'hue', ColorFormat::HSL->value))->toBe('0');
        });

        it('handles lab channel access', function () {
            $result = $this->colorModule->channel('lab(50% 20 -10)', 'lab_l');
            expect($result)->toBe('50%');

            $result = $this->colorModule->channel('lab(50% 20 -10)', 'lab_a');
            expect($result)->toBe('20');

            $result = $this->colorModule->channel('lab(50% 20 -10)', 'lab_b');
            expect($result)->toBe('-10');
        });

        it('handles laba channel access', function () {
            $result = $this->colorModule->channel('lab(50% 20 -10 / 0.5)', 'lab_l');
            expect($result)->toBe('50%');

            $result = $this->colorModule->channel('lab(50% 20 -10 / 0.5)', 'lab_a');
            expect($result)->toBe('20');

            $result = $this->colorModule->channel('lab(50% 20 -10 / 0.5)', 'lab_b');
            expect($result)->toBe('-10');

            $result = $this->colorModule->channel('lab(50% 20 -10 / 0.5)', 'alpha');
            expect($result)->toBe('0.5');
        });

        it('handles LCH channel access (chroma)', function () {
            $result = $this->colorModule->channel('lch(60% 40 30)', 'chroma');
            expect($result)->toBe('40');

            $result = $this->colorModule->channel('lch(60% 40 30)', 'c');
            expect($result)->toBe('40');

            $result = $this->colorModule->channel('lch(60% 0.15 30)', 'chroma');
            expect($result)->toBe('0.15');

            $result = $this->colorModule->channel('lch(60% 0.15 30)', 'c');
            expect($result)->toBe('0.15');
        });

        it('handles LCH channel access with space parameter', function () {
            $result = $this->colorModule->channel('#ff0000', 'chroma', 'lch');
            expect($result)->toBe('104.58');

            $result = $this->colorModule->channel('#ff0000', 'c', 'lch');
            expect($result)->toBe('104.58');
        });

        it('handles XYZ channel access', function () {
            $result = $this->colorModule->channel('color(xyz 0.5 0.3 0.2)', 'x');
            expect($result)->toBe('1');

            $result = $this->colorModule->channel('color(xyz 0.5 0.3 0.2)', 'y');
            expect($result)->toBe('0');

            $result = $this->colorModule->channel('color(xyz 0.5 0.3 0.2)', 'z');
            expect($result)->toBe('0');
        });

        it('handles XYZ channel access with alpha', function () {
            $result = $this->colorModule->channel('color(xyz 0.5 0.3 0.2 / 0.8)', 'x');
            expect($result)->toBe('1');

            $result = $this->colorModule->channel('color(xyz 0.5 0.3 0.2 / 0.8)', 'y');
            expect($result)->toBe('0');

            $result = $this->colorModule->channel('color(xyz 0.5 0.3 0.2 / 0.8)', 'z');
            expect($result)->toBe('0');
        });

        it('handles XYZ channel access with space parameter from RGB', function () {
            $result = $this->colorModule->channel('#ff0000', 'x', 'xyz');
            expect($result)->toBe('41');

            $result = $this->colorModule->channel('#ff0000', 'y', 'xyz');
            expect($result)->toBe('21');

            $result = $this->colorModule->channel('#ff0000', 'z', 'xyz');
            expect($result)->toBe('2');
        });

        it('formats saturation channel with percent sign correctly', function () {
            $result = $this->colorModule->channel('hsl(0, 100%, 50%)', 'saturation');
            expect($result)->toBe('100%');

            $result = $this->colorModule->channel('hsl(0, 0%, 50%)', 'saturation');
            expect($result)->toBe('0%');
        });

        it('formats hue channel with deg suffix when non-zero', function () {
            $result = $this->colorModule->channel('hsl(120, 100%, 50%)', 'hue');
            expect($result)->toBe('120deg');

            $result = $this->colorModule->channel('hsl(0, 100%, 50%)', 'hue');
            expect($result)->toBe('0');
        });
    });

    describe('individual channel accessors', function () {
        it('returns alpha', function () {
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

        it('returns hue', function () {
            expect($this->colorModule->hue('#ff0000'))->toBe('0')
                ->and($this->colorModule->hue('hsl(120, 100%, 50%)'))->toBe('120deg')
                ->and($this->colorModule->hue('#00ff00'))->toBe('120deg')
                ->and($this->colorModule->hue('#0000ff'))->toBe('240deg');
        });

        it('returns saturation', function () {
            expect($this->colorModule->saturation('hsl(0, 100%, 50%)'))->toBe('100%')
                ->and($this->colorModule->saturation('hsl(0, 50%, 50%)'))->toBe('50%')
                ->and($this->colorModule->saturation('#808080'))->toBe('0%');
        });

        it('returns lightness', function () {
            expect($this->colorModule->lightness('hsl(0, 100%, 50%)'))->toBe('50%')
                ->and($this->colorModule->lightness('#ffffff'))->toBe('100%')
                ->and($this->colorModule->lightness('#000000'))->toBe('0%')
                ->and($this->colorModule->lightness('#808080'))->toBe('50.1960784314%');
        });

        it('returns whiteness', function () {
            expect($this->colorModule->whiteness('hwb(0, 50%, 0%)'))->toBe('50%')
                ->and($this->colorModule->whiteness('#ffffff'))->toBe('100%')
                ->and($this->colorModule->whiteness('#000000'))->toBe('0%');
        });

        it('returns blackness', function () {
            expect($this->colorModule->blackness('hwb(0, 0%, 50%)'))->toBe('50%')
                ->and($this->colorModule->blackness('#ffffff'))->toBe('0%')
                ->and($this->colorModule->blackness('#000000'))->toBe('100%');
        });
    });
})->covers(ColorModule::class);

describe('Color Inspection', function () {
    describe('space()', function () {
        it('returns color space', function () {
            expect($this->colorModule->space('#ff0000'))->toBe(ColorFormat::RGB->value)
                ->and($this->colorModule->space('hsl(0, 100%, 50%)'))->toBe(ColorFormat::HSL->value)
                ->and($this->colorModule->space('hwb(0, 0%, 0%)'))->toBe(ColorFormat::HWB->value);
        });

        it('handles lab color space detection', function () {
            $result = $this->colorModule->space('lab(50% 20 -10)');
            expect($result)->toBe(ColorFormat::LAB->value);

            $result = $this->colorModule->space('lab(50% 20 -10 / 0.5)');
            expect($result)->toBe(ColorFormat::LAB->value);
        });
    });

    describe('isLegacy()', function () {
        it('checks if color is legacy', function () {
            expect($this->colorModule->isLegacy('#ff0000'))->toBe('true')
                ->and($this->colorModule->isLegacy('color(xyz 0.5 0.3 0.2)'))->toBe('false');
        });

        it('handles lab color isLegacy check', function () {
            $result = $this->colorModule->isLegacy('lab(50% 20 -10)');
            expect($result)->toBe('false');
        });
    });

    describe('isPowerless()', function () {
        it('checks if hue is powerless', function () {
            expect($this->colorModule->isPowerless('#808080', 'hue'))->toBe('true')
                ->and($this->colorModule->isPowerless('#ff0000', 'hue'))->toBe('false')
                ->and($this->colorModule->isPowerless('hsl(180deg 0% 40%)', 'hue'))->toBe('true');
        });

        it('supports different color spaces', function () {
            expect($this->colorModule->isPowerless('#808080', 'hue', ColorFormat::HSL->value))->toBe('true')
                ->and($this->colorModule->isPowerless('#808080', 'hue', ColorFormat::HWB->value))->toBe('true');
        });

        it('checks if other channels are powerless', function () {
            expect($this->colorModule->isPowerless('#808080', 'saturation'))->toBe('false')
                ->and($this->colorModule->isPowerless('#ff0000', 'hue'))->toBe('false');
        });

        it('throws exception for unknown channel', function () {
            expect(fn() => $this->colorModule->isPowerless('#808080', 'unknown'))
                ->toThrow(CompilationException::class, 'Unknown channel');
        });

        it('checks saturation powerlessness with boundary lightness values', function () {
            expect($this->colorModule->isPowerless('hsl(180deg 50% 0%)', 'saturation'))->toBe('true')
                ->and($this->colorModule->isPowerless('hsl(180deg 50% 100%)', 'saturation'))->toBe('true')
                ->and($this->colorModule->isPowerless('hsl(180deg 50% 50%)', 'saturation'))->toBe('false');
        });

        it('checks RGB channel powerlessness with zero alpha', function () {
            expect($this->colorModule->isPowerless('rgba(255, 0, 0, 0)', 'red'))->toBe('true')
                ->and($this->colorModule->isPowerless('rgba(255, 0, 0, 0)', 'green'))->toBe('true')
                ->and($this->colorModule->isPowerless('rgba(255, 0, 0, 0)', 'blue'))->toBe('true');
        });

        it('checks RGB channel powerlessness with non-zero alpha', function () {
            expect($this->colorModule->isPowerless('rgba(255, 0, 0, 0.5)', 'red'))->toBe('false')
                ->and($this->colorModule->isPowerless('rgba(255, 0, 0, 0.5)', 'green'))->toBe('false')
                ->and($this->colorModule->isPowerless('rgba(255, 0, 0, 0.5)', 'blue'))->toBe('false')
                ->and($this->colorModule->isPowerless('rgb(255, 0, 0)', 'red'))->toBe('false')
                ->and($this->colorModule->isPowerless('rgb(255, 0, 0)', 'green'))->toBe('false')
                ->and($this->colorModule->isPowerless('rgb(255, 0, 0)', 'blue'))->toBe('false');
        });

        it('checks HWB channel powerlessness with zero alpha', function () {
            expect($this->colorModule->isPowerless('hwb(180 50% 25% / 0)', 'whiteness'))->toBe('true')
                ->and($this->colorModule->isPowerless('hwb(180 50% 25% / 0)', 'blackness'))->toBe('true');
        });

        it('checks HWB channel powerlessness with non-zero alpha', function () {
            expect($this->colorModule->isPowerless('hwb(180 50% 25% / 0.5)', 'whiteness'))->toBe('false')
                ->and($this->colorModule->isPowerless('hwb(180 50% 25% / 0.5)', 'blackness'))->toBe('false');
        });

        it('checks hue powerlessness for different saturation levels', function () {
            expect($this->colorModule->isPowerless('hsl(180deg 0% 50%)', 'hue'))->toBe('true')
                ->and($this->colorModule->isPowerless('hsl(180deg 25% 50%)', 'hue'))->toBe('false')
                ->and($this->colorModule->isPowerless('hsl(180deg 100% 50%)', 'hue'))->toBe('false');
        });

        it('validates color space parameter for isPowerless', function () {
            expect($this->colorModule->isPowerless('#808080', 'hue', 'hsl'))->toBe('true')
                ->and(fn() => $this->colorModule->isPowerless('#808080', 'hue', 'invalid-space'))
                ->toThrow(CompilationException::class, 'Unknown color space');
        });

        it('throws exception for channel not valid in color space', function () {
            expect(fn() => $this->colorModule->isPowerless('#808080', 'red', 'hsl'))
                ->toThrow(CompilationException::class, "Channel 'red' is not valid for color space 'hsl'. Valid channels:")
                ->and(fn() => $this->colorModule->isPowerless('#808080', 'red', 'hwb'))
                ->toThrow(CompilationException::class, "Channel 'red' is not valid for color space 'hwb'. Valid channels:")
                ->and(fn() => $this->colorModule->isPowerless('#808080', 'hue', 'rgb'))
                ->toThrow(CompilationException::class, "Channel 'hue' is not valid for color space 'rgb'. Valid channels:");
        });

        it('checks powerlessness for short channel names', function () {
            expect($this->colorModule->isPowerless('hsl(180deg 0% 50%)', 'h'))->toBe('true')
                ->and($this->colorModule->isPowerless('hsl(180deg 50% 50%)', 's'))->toBe('false')
                ->and($this->colorModule->isPowerless('rgba(255, 0, 0, 0)', 'r'))->toBe('true')
                ->and($this->colorModule->isPowerless('rgba(255, 0, 0, 0)', 'g'))->toBe('true')
                ->and($this->colorModule->isPowerless('rgba(255, 0, 0, 0)', 'b'))->toBe('true');
        });

        it('handles quoted channel names', function () {
            expect($this->colorModule->isPowerless('hsl(180deg 0% 50%)', '"hue"'))->toBe('true')
                ->and($this->colorModule->isPowerless('hsl(180deg 0% 50%)', "'hue'"))->toBe('true');
        });
    });

    describe('isMissing()', function () {
        it('returns false for alpha channel in isMissing', function () {
            $result = $this->colorModule->isMissing('#ff0000', 'alpha');
            expect($result)->toBe('false');

            $result = $this->colorModule->isMissing('rgba(255, 0, 0, 0.5)', 'a');
            expect($result)->toBe('false');
        });

        it('checks if channel is missing', function () {
            expect($this->colorModule->isMissing('#ff0000', 'red'))->toBe('false')
                ->and($this->colorModule->isMissing('#ff0000', 'r'))->toBe('false')
                ->and($this->colorModule->isMissing('#ff0000', 'hue'))->toBe('true');
        });

        it('handles different channel formats', function () {
            expect($this->colorModule->isMissing('#ff0000', 'r'))->toBe('false')
                ->and($this->colorModule->isMissing('#ff0000', 'red'))->toBe('false')
                ->and($this->colorModule->isMissing('hsl(0, 100%, 50%)', 'h'))->toBe('false')
                ->and($this->colorModule->isMissing('hsl(0, 100%, 50%)', 'hue'))->toBe('false')
                ->and($this->colorModule->isMissing('#ff0000', 'h'))->toBe('true')
                ->and($this->colorModule->isMissing('#ff0000', 'hue'))->toBe('true');
        });

        it('handles lab color isMissing checks', function () {
            expect($this->colorModule->isMissing('lab(50% 20 -10)', 'lab_l'))->toBe('false')
                ->and($this->colorModule->isMissing('lab(50% 20 -10)', 'lab_a'))->toBe('false')
                ->and($this->colorModule->isMissing('lab(50% 20 -10)', 'lab_b'))->toBe('false')
                ->and($this->colorModule->isMissing('lab(50% 20 -10)', 'alpha'))->toBe('false')
                ->and($this->colorModule->isMissing('lab(50% 20 -10)', 'red'))->toBe('true');
        });

        it('throws exception for unknown channel', function () {
            expect(fn() => $this->colorModule->isMissing('#808080', 'unknown'))
                ->toThrow(CompilationException::class, 'Unknown channel');
        });
    });

    describe('same()', function () {
        it('checks if colors are identical', function () {
            expect($this->colorModule->same('#ff0000', '#ff0000'))->toBe('true')
                ->and($this->colorModule->same('#ff0000', '#00ff00'))->toBe('false')
                ->and($this->colorModule->same('red', '#ff0000'))->toBe('true');
        });

        it('handles lab color same comparison', function () {
            expect($this->colorModule->same('lab(50% 20 -10)', 'lab(50% 20 -10)'))->toBe('true');

            $result = $this->colorModule->same('lab(50% 20 -10)', 'lab(60% 30 5)');
            expect(in_array($result, ['true', 'false'], true))->toBeTrue();
        });
    });
})->covers(ColorModule::class);

describe('Special Functions', function () {
    it('generates IE hex string', function () {
        expect($this->colorModule->ieHexStr('#ff0000'))->toBe('#FFFF0000')
            ->and($this->colorModule->ieHexStr('rgba(255, 0, 0, 0.5)'))->toBe('#80FF0000');
    });
})->covers(ColorModule::class);

describe('Utility Functions', function () {
    it('clamps values correctly', function () {
        expect($this->accessor->callMethod('clamp', [15, 0, 10]))->toBe(10.0)
            ->and($this->accessor->callMethod('clamp', [5, 0, 10]))->toBe(5.0)
            ->and($this->accessor->callMethod('clamp', [-5, 0, 10]))->toBe(0.0);
    });

    it('handles key to channel conversion', function () {
        expect($this->accessor->callMethod('keyToChannel', ['$red']))->toBe('r')
            ->and($this->accessor->callMethod('keyToChannel', ['$green']))->toBe('g')
            ->and($this->accessor->callMethod('keyToChannel', ['$blue']))->toBe('b');
    });

    it('throws exception for invalid key in keyToChannel', function () {
        expect(fn() => $this->accessor->callMethod('keyToChannel', ['$invalid']))
            ->toThrow(InvalidArgumentException::class, 'Invalid RGB key');
    });

    it('handles key to hsl channel conversion', function () {
        expect($this->accessor->callMethod('keyToHslChannel', ['$hue']))->toBe('h')
            ->and($this->accessor->callMethod('keyToHslChannel', ['$saturation']))->toBe('s')
            ->and($this->accessor->callMethod('keyToHslChannel', ['$lightness']))->toBe('l');
    });

    it('throws exception for invalid key in keyToHslChannel', function () {
        expect(fn() => $this->accessor->callMethod('keyToHslChannel', ['$invalid']))
            ->toThrow(InvalidArgumentException::class, 'Invalid HSL key');
    });

    it('detects color space correctly', function () {
        expect($this->accessor->callMethod('getColorSpace', [ColorFormat::HSL->value]))->toBe(ColorFormat::HSL->value)
            ->and($this->accessor->callMethod('getColorSpace', [ColorFormat::HSLA->value]))->toBe(ColorFormat::HSL->value)
            ->and($this->accessor->callMethod('getColorSpace', [ColorFormat::HWB->value]))->toBe(ColorFormat::HWB->value)
            ->and($this->accessor->callMethod('getColorSpace', [ColorFormat::LCH->value]))->toBe(ColorFormat::LCH->value)
            ->and($this->accessor->callMethod('getColorSpace', [ColorFormat::OKLCH->value]))->toBe(ColorFormat::OKLCH->value)
            ->and($this->accessor->callMethod('getColorSpace', [ColorFormat::RGB->value]))->toBe(ColorFormat::RGB->value)
            ->and($this->accessor->callMethod('getColorSpace', [ColorFormat::RGBA->value]))->toBe(ColorFormat::RGB->value);
    });

    describe('Edge Cases and Mutations Coverage', function () {
        it('handles hue normalization above 360 degrees', function () {
            $result = $this->colorModule->parseColor('hwb(720deg 0% 0%)');
            expect($result['h'])->toBeCloseTo(0, 1);
        });

        it('validates multiple RGB adjustments in single call', function () {
            $result = $this->colorModule->adjust('#808080', [
                '$red' => 50,
                '$green' => -30,
                '$blue' => 20,
            ]);
            expect($result)->toMatch('/^#[0-9a-f]{6}$/');
        });

        it('handles whiteness and blackness adjustments together', function () {
            $result = $this->colorModule->adjust('#ff0000', [
                '$whiteness' => 10,
                '$blackness' => 10,
            ]);
            expect($result)->toMatch('/^#[0-9a-f]{6}$/');
        });

        it('scales channels with boundary values', function () {
            expect($this->accessor->callMethod('scaleChannel', [0, 50, 0, 255]))->toBeGreaterThanOrEqual(0)
                ->and($this->accessor->callMethod('scaleChannel', [255, -50, 0, 255]))->toBeLessThanOrEqual(255)
                ->and($this->accessor->callMethod('scaleChannel', [127.5, 50, 0, 255]))->toBeGreaterThan(127);
        });

        it('handles mix with extreme weight values', function () {
            $result1 = $this->colorModule->mix('#ff0000', '#0000ff', 0);
            $result2 = $this->colorModule->mix('#ff0000', '#0000ff', 1);
            $result3 = $this->colorModule->mix('#ff0000', '#0000ff', 100); // Should normalize to 1

            expect($result1)->not->toBe($result2)
                ->and($result2)->toBe($result3);
        });
    });

    describe('Edge Cases Coverage for Code Coverage', function () {
        it('covers RGBA format selection in mix and hue normalization in formatHsl', function () {
            // Test case 1: Cover RGBA format selection in mix method
            // This happens when either color has alpha < 1.0
            $resultWithAlpha = $this->colorModule->mix('rgba(255, 0, 0, 0.5)', '#0000ff');
            expect($resultWithAlpha)->toMatch('/^#[0-9a-f]{8}$/'); // Should be 8-digit hex for RGBA

            $resultBothAlpha = $this->colorModule->mix('rgba(255, 0, 0, 0.3)', 'rgba(0, 0, 255, 0.7)');
            expect($resultBothAlpha)->toMatch('/^#[0-9a-f]{8}$/'); // Should be 8-digit hex for RGBA

            // Test case 2: Cover hue normalization loops in formatHsl method
            // This happens when hue < 0 or hue >= 360
            $colorDataNegativeHue = [
                'h' => -90, // Negative hue that needs normalization
                's' => 100,
                'l' => 50,
                'a' => 1.0,
                'format' => ColorFormat::HSL->value,
            ];
            $resultNegativeHue = $this->colorModule->formatColor($colorDataNegativeHue);
            expect($resultNegativeHue)->toBe('hsl(270, 100%, 50%)'); // -90 + 360 = 270

            $colorDataLargeHue = [
                'h' => 450, // Hue >= 360 that needs normalization
                's' => 100,
                'l' => 50,
                'a' => 1.0,
                'format' => ColorFormat::HSL->value,
            ];
            $resultLargeHue = $this->colorModule->formatColor($colorDataLargeHue);
            expect($resultLargeHue)->toBe('hsl(90, 100%, 50%)'); // 450 - 360 = 90

            // Test case 3: Cover HSLA format with normalized hue
            $colorDataHslaNormalized = [
                'h' => -180, // Negative hue that needs normalization
                's' => 100,
                'l' => 50,
                'a' => 0.8, // Alpha < 1.0 for HSLA format
                'format' => ColorFormat::HSLA->value,
            ];
            $resultHslaNormalized = $this->colorModule->formatColor($colorDataHslaNormalized);
            expect($resultHslaNormalized)->toBe('hsla(180, 100%, 50%, 0.8)'); // -180 + 360 = 180
        });

        it('covers default case in getColorSpace method', function () {
            $result = $this->accessor->callMethod('getColorSpace', ['invalid-format']);
            expect($result)->toBe(ColorFormat::RGB->value); // Should return RGB as default

            $result2 = $this->accessor->callMethod('getColorSpace', ['unknown']);
            expect($result2)->toBe(ColorFormat::RGB->value); // Should return RGB as default

            $result3 = $this->accessor->callMethod('getColorSpace', ['not-a-format']);
            expect($result3)->toBe(ColorFormat::RGB->value); // Should return RGB as default

            $result4 = $this->accessor->callMethod('getColorSpace', [ColorFormat::RGB->value]);
            expect($result4)->toBe(ColorFormat::RGB->value);

            $result5 = $this->accessor->callMethod('getColorSpace', [ColorFormat::HSL->value]);
            expect($result5)->toBe(ColorFormat::HSL->value);
        });
    });
})->covers(ColorModule::class);
