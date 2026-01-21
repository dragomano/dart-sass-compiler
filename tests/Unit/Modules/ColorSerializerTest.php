<?php

declare(strict_types=1);

use DartSass\Modules\ColorFormat;
use DartSass\Modules\ColorSerializer;
use DartSass\Values\SassColor;
use Tests\ReflectionAccessor;

describe('ColorSerializer', function () {
    describe('ensureRgbFormat()', function () {
        it('keeps RGB format when original format is RGB', function () {
            $colorData = [
                'r'      => 255,
                'g'      => 0,
                'b'      => 0,
                'a'      => 1.0,
                'format' =>  ColorFormat::RGB->value,
            ];

            $result = ColorSerializer::ensureRgbFormat($colorData);
            expect($result['format'])->toBe(ColorFormat::RGB->value);
        });

        it('keeps RGBA format when original format is RGBA', function () {
            $colorData = [
                'r'      => 255,
                'g'      => 0,
                'b'      => 0,
                'a'      => 0.5,
                'format' =>  ColorFormat::RGBA->value,
            ];

            $result = ColorSerializer::ensureRgbFormat($colorData);
            expect($result['format'])->toBe(ColorFormat::RGBA->value);
        });

        it('converts HSL to RGB format correctly', function () {
            $colorData = [
                'h'      => 0,
                's'      => 100,
                'l'      => 50,
                'a'      => 1.0,
                'format' => ColorFormat::HSL->value,
            ];

            $result = ColorSerializer::ensureRgbFormat($colorData);
            expect($result['format'])->toBe(ColorFormat::RGB->value)
                ->and($result['r'])->toBeCloseTo(255, 0)
                ->and($result['g'])->toBeCloseTo(0, 0)
                ->and($result['b'])->toBeCloseTo(0, 0);
        });

        it('converts HSLA to RGB format regardless of alpha value', function () {
            $colorData = [
                'h'      => 0,
                's'      => 100,
                'l'      => 50,
                'a'      => 0.5,
                'format' => ColorFormat::HSLA->value,
            ];

            $result = ColorSerializer::ensureRgbFormat($colorData);
            expect($result['format'])->toBe(ColorFormat::RGB->value)
                ->and($result['a'])->toBe(0.5);
        });

        it('converts HSLA to RGB when alpha equals ALPHA_MAX', function () {
            $colorData = [
                'h'      => 0,
                's'      => 100,
                'l'      => 50,
                'a'      => 1.0,
                'format' => ColorFormat::HSLA->value,
            ];

            $result = ColorSerializer::ensureRgbFormat($colorData);
            expect($result['format'])->toBe(ColorFormat::RGB->value)
                ->and($result['a'])->toBe(1.0);
        });

        it('converts XYZ to RGB format correctly', function () {
            $colorData = [
                'x'      => 0.5,
                'y'      => 0.3,
                'z'      => 0.2,
                'a'      => 1.0,
                'format' => ColorFormat::XYZ->value,
            ];

            $result = ColorSerializer::ensureRgbFormat($colorData);
            expect($result['format'])->toBe(ColorFormat::RGB->value)
                ->and($result)->toHaveKeys(['r', 'g', 'b']);
        });

        it('converts XYZA to RGB when alpha equals ALPHA_MAX', function () {
            $colorData = [
                'x'      => 0.5,
                'y'      => 0.3,
                'z'      => 0.2,
                'a'      => 1.0,
                'format' => ColorFormat::XYZA->value,
            ];

            $result = ColorSerializer::ensureRgbFormat($colorData);
            expect($result['format'])->toBe(ColorFormat::RGB->value)
                ->and($result['a'])->toBe(1.0)
                ->and($result)->toHaveKeys(['r', 'g', 'b']);
        });

        it('converts XYZA to RGBA when alpha is less than ALPHA_MAX', function () {
            $colorData = [
                'x'      => 0.5,
                'y'      => 0.3,
                'z'      => 0.2,
                'a'      => 0.7,
                'format' => ColorFormat::XYZA->value,
            ];

            $result = ColorSerializer::ensureRgbFormat($colorData);
            expect($result['format'])->toBe(ColorFormat::RGB->value)
                ->and($result['a'])->toBe(0.7)
                ->and($result)->toHaveKeys(['r', 'g', 'b']);
        });

        it('throws InvalidArgumentException for unsupported color format', function () {
            $colorData = [
                'r'      => 255,
                'g'      => 0,
                'b'      => 0,
                'a'      => 1.0,
                'format' => 'unsupported_format',
            ];

            expect(fn() => ColorSerializer::ensureRgbFormat($colorData))
                ->toThrow(InvalidArgumentException::class, 'Unsupported color format: unsupported_format');
        });
    });

    describe('resolveRgbFormat() edge cases', function () {
        it('returns RGB for HSLA format regardless of alpha value', function () {
            $colorData = [
                'h'      => 0,
                's'      => 100,
                'l'      => 50,
                'a'      => 0.999,
                'format' => ColorFormat::HSLA->value,
            ];

            $result = ColorSerializer::ensureRgbFormat($colorData);
            expect($result['format'])->toBe(ColorFormat::RGB->value);
        });

        it('returns RGB for HSLA format when alpha equals ALPHA_MAX', function () {
            $colorData = [
                'h'      => 0,
                's'      => 100,
                'l'      => 50,
                'a'      => 1.0,
                'format' => ColorFormat::HSLA->value,
            ];

            $result = ColorSerializer::ensureRgbFormat($colorData);
            expect($result['format'])->toBe(ColorFormat::RGB->value);
        });

        it('returns RGB for non-RGBA original format regardless of alpha', function () {
            $colorData = [
                'h'      => 0,
                's'      => 100,
                'l'      => 50,
                'a'      => 0.5,
                'format' => ColorFormat::HSL->value,
            ];

            $result = ColorSerializer::ensureRgbFormat($colorData);
            expect($result['format'])->toBe(ColorFormat::RGB->value);
        });
    });

    describe('resolveRgbFormat() conditional logic test', function () {
        beforeEach(function () {
            $this->accessor = new ReflectionAccessor(ColorSerializer::class);
        });

        it('tests the exact conditional logic: $alpha < self::ALPHA_MAX ? RGBA : RGB', function () {
            $colorDataLowAlpha = [
                'h'      => 240,
                's'      => 100,
                'l'      => 50,
                'a'      => 0.5,
                'format' => ColorFormat::RGBA->value,
            ];

            $result = $this->accessor->callMethod('resolveRgbFormat', [$colorDataLowAlpha]);
            expect($result)->toBe(ColorFormat::RGBA->value);

            $colorDataHighAlpha = [
                'h'      => 240,
                's'      => 100,
                'l'      => 50,
                'a'      => 1.0,
                'format' => ColorFormat::RGBA->value,
            ];

            $result = $this->accessor->callMethod('resolveRgbFormat', [$colorDataHighAlpha]);
            expect($result)->toBe(ColorFormat::RGB->value);

            $colorDataMaxAlpha = [
                'h'      => 240,
                's'      => 100,
                'l'      => 50,
                'a'      => ColorSerializer::ALPHA_MAX,
                'format' => ColorFormat::RGBA->value,
            ];

            $result = $this->accessor->callMethod('resolveRgbFormat', [$colorDataMaxAlpha]);
            expect($result)->toBe(ColorFormat::RGB->value);

            $colorDataNotRgba = [
                'h'      => 240,
                's'      => 100,
                'l'      => 50,
                'a'      => 0.5,
                'format' => ColorFormat::HSL->value,
            ];

            $result = $this->accessor->callMethod('resolveRgbFormat', [$colorDataNotRgba]);
            expect($result)->toBe(ColorFormat::RGB->value);
        });
    });

    describe('getNamedColor()', function () {
        it('returns named color for valid RGB values', function () {
            expect(ColorSerializer::getNamedColor(255, 0, 0))->toBe('red');
        });

        it('returns null for non-named color', function () {
            expect(ColorSerializer::getNamedColor(128, 64, 32))->toBeNull();
        });
    });

    describe('format() method', function () {
        it('formats color to RGB when no alpha', function () {
            $sassColor = SassColor::rgb(255, 0, 0, 1.0);
            $result = ColorSerializer::format(ColorFormat::RGB, $sassColor);
            expect($result)->toBe('red');
        });

        it('formats color to RGBA when has alpha', function () {
            $sassColor = SassColor::rgb(255, 0, 0, 0.5);
            $result = ColorSerializer::format(ColorFormat::RGBA, $sassColor);
            expect($result)->toBe('#ff000080');
        });
    });

    describe('formatLch() edge cases', function () {
        it('handles lightness value less than or equal to 1 by multiplying by PERCENT_MAX', function () {
            $sassColor = SassColor::lch(0.6, 40, 30);
            $result = ColorSerializer::format(ColorFormat::LCH, $sassColor);
            expect($result)->toBe('lch(60% 40 30)');
        });

        it('handles lightness value greater than 1 without conversion', function () {
            $sassColor = SassColor::lch(60, 40, 30);
            $result = ColorSerializer::format(ColorFormat::LCH, $sassColor);
            expect($result)->toBe('lch(60% 40 30)');
        });

        it('handles lightness value exactly equal to 1', function () {
            $sassColor = SassColor::lch(1, 40, 30);
            $result = ColorSerializer::format(ColorFormat::LCH, $sassColor);
            expect($result)->toBe('lch(100% 40 30)');
        });

        it('handles lightness value slightly above 1', function () {
            $sassColor = SassColor::lch(1.1, 40, 30);
            $result = ColorSerializer::format(ColorFormat::LCH, $sassColor);
            expect($result)->toBe('lch(1.1% 40 30)');
        });
    });

    describe('formatHwb() edge cases', function () {
        it('handles HWB format with alpha channel', function () {
            $sassColor = SassColor::hwb(120, 25, 40, 0.8);
            $result = ColorSerializer::format(ColorFormat::HWB, $sassColor);
            expect($result)->toBe('hwb(120 25% 40% / 0.8)');
        });

        it('handles HWB format without alpha channel', function () {
            $sassColor = SassColor::hwb(120, 25, 40);
            $result = ColorSerializer::format(ColorFormat::HWB, $sassColor);
            expect($result)->toBe('hwb(120 25% 40%)');
        });

        it('handles HWB rounding of values', function () {
            $sassColor = SassColor::hwb(120.7, 25.3, 40.9, 0.75);
            $result = ColorSerializer::format(ColorFormat::HWB, $sassColor);
            expect($result)->toBe('hwb(121 25% 41% / 0.75)');
        });
    });

    describe('formatHex() method', function () {
        it('formats color to named color when available', function () {
            $sassColor = SassColor::rgb(255, 0, 0, 1.0);
            $result = ColorSerializer::format(ColorFormat::HEX, $sassColor);
            expect($result)->toBe('red');
        });

        it('formats named color to color name', function () {
            $sassColor = SassColor::rgb(0, 0, 255, 1.0);
            $result = ColorSerializer::format(ColorFormat::HEX, $sassColor);
            expect($result)->toBe('blue');
        });

        it('formats non-named RGB values to HEX', function () {
            $sassColor = SassColor::rgb(128, 64, 32, 1.0);
            $result = ColorSerializer::format(ColorFormat::HEX, $sassColor);
            expect($result)->toBe('#804020');
        });
    });

    describe('formatHexa() method', function () {
        it('formats color to HEXA format', function () {
            $sassColor = SassColor::rgb(210.5, 0.0, 0.0, 0.5);
            $result = ColorSerializer::format(ColorFormat::HEXA, $sassColor);
            expect($result)->toBe('#d3000080');
        });

        it('rounds RGB values correctly in formatHexa', function () {
            $sassColor = new SassColor([
                'r' => 127.5,
                'g' => 128.4,
                'b' => 128.6,
                'a' => 0.502,
            ]);

            $result = ColorSerializer::format(ColorFormat::HEXA, $sassColor);

            expect($result)->toBe('#80808180');
        });

        it('handles edge case rounding in formatHexa with boundary values', function () {
            $sassColor = new SassColor([
                'r' => 0.4,
                'g' => 0.5,
                'b' => 254.6,
                'a' => 0.996,
            ]);

            $result = ColorSerializer::format(ColorFormat::HEXA, $sassColor);

            expect($result)->toMatch('/^#[0-9a-f]{8}$/');
        });
    });

    describe('formatXyz() method', function () {
        it('formats XYZ color with alpha (line 302)', function () {
            $sassColor = SassColor::xyz(0.5, 0.3, 0.2, 0.8);
            $result = ColorSerializer::format(ColorFormat::XYZA, $sassColor);
            expect($result)->toBe('color(xyz 0.5 0.3 0.2 / 0.8)');
        });
    });

    describe('formatHsl hue normalization coverage', function () {
        it('covers while ($h < 0) loop in formatHsl method', function () {
            $sassColor = SassColor::hsl(-90, 100, 50, 1.0);
            $result = ColorSerializer::format(ColorFormat::HSL, $sassColor);
            expect($result)->toBe('hsl(270, 100%, 50%)');
        });

        it('covers while ($h >= self::HUE_MAX) loop in formatHsl method', function () {
            $sassColor = SassColor::hsl(450, 100, 50, 1.0);
            $result = ColorSerializer::format(ColorFormat::HSL, $sassColor);
            expect($result)->toBe('hsl(90, 100%, 50%)');
        });
    });
})->covers(ColorSerializer::class);
