<?php

declare(strict_types=1);

use DartSass\Modules\ColorFormat;

describe('ColorFormat', function () {
    describe('::getPrimaryChannels', function () {
        it('returns correct primary channels for HSL format', function () {
            $channels = ColorFormat::HSL->getPrimaryChannels();
            expect($channels)->toBe(['hue', 'saturation', 'lightness']);
        });

        it('returns correct primary channels for HSLA format', function () {
            $channels = ColorFormat::HSLA->getPrimaryChannels();
            expect($channels)->toBe(['hue', 'saturation', 'lightness']);
        });

        it('returns correct primary channels for HWB format', function () {
            $channels = ColorFormat::HWB->getPrimaryChannels();
            expect($channels)->toBe(['hue', 'whiteness', 'blackness']);
        });

        it('returns correct primary channels for LAB format', function () {
            $channels = ColorFormat::LAB->getPrimaryChannels();
            expect($channels)->toBe(['l', 'a', 'b']);
        });

        it('returns correct primary channels for LABA format', function () {
            $channels = ColorFormat::LABA->getPrimaryChannels();
            expect($channels)->toBe(['l', 'a', 'b']);
        });

        it('returns correct primary channels for LCH format', function () {
            $channels = ColorFormat::LCH->getPrimaryChannels();
            expect($channels)->toBe(['lightness', 'chroma', 'hue']);
        });

        it('returns correct primary channels for OKLCH format', function () {
            $channels = ColorFormat::OKLCH->getPrimaryChannels();
            expect($channels)->toBe(['lightness', 'chroma', 'hue']);
        });

        it('returns correct primary channels for RGB format', function () {
            $channels = ColorFormat::RGB->getPrimaryChannels();
            expect($channels)->toBe(['red', 'green', 'blue']);
        });

        it('returns correct primary channels for RGBA format', function () {
            $channels = ColorFormat::RGBA->getPrimaryChannels();
            expect($channels)->toBe(['red', 'green', 'blue']);
        });

        it('returns correct primary channels for XYZ format', function () {
            $channels = ColorFormat::XYZ->getPrimaryChannels();
            expect($channels)->toBe(['x', 'y', 'z']);
        });

        it('returns correct primary channels for XYZA format', function () {
            $channels = ColorFormat::XYZA->getPrimaryChannels();
            expect($channels)->toBe(['x', 'y', 'z']);
        });

        it('returns empty array for HEX format', function () {
            $channels = ColorFormat::HEX->getPrimaryChannels();
            expect($channels)->toBe([]);
        });

        it('returns empty array for HEXA format', function () {
            $channels = ColorFormat::HEXA->getPrimaryChannels();
            expect($channels)->toBe([]);
        });
    });

    describe('::format', function () {
        it('formats color data correctly for RGB format', function () {
            $colorData = ['r' => 255, 'g' => 0, 'b' => 0, 'a' => 1];
            $formatted = ColorFormat::RGB->format($colorData);
            expect($formatted)->toBe('red'); // Named color for pure red
        });

        it('formats color data correctly for RGBA format', function () {
            $colorData = ['r' => 255, 'g' => 0, 'b' => 0, 'a' => 0.5];
            $formatted = ColorFormat::RGBA->format($colorData);
            expect($formatted)->toBe('#ff000080'); // Hex with alpha
        });

        it('formats color data correctly for HSL format', function () {
            $colorData = ['h' => 0, 's' => 100, 'l' => 50, 'a' => 1];
            $formatted = ColorFormat::HSL->format($colorData);
            expect($formatted)->toBe('hsl(0, 100%, 50%)');
        });

        it('formats color data correctly for HSLA format', function () {
            $colorData = ['h' => 0, 's' => 100, 'l' => 50, 'a' => 0.75];
            $formatted = ColorFormat::HSLA->format($colorData);
            expect($formatted)->toBe('hsla(0, 100%, 50%, 0.75)');
        });

        it('formats color data correctly for HWB format', function () {
            $colorData = ['h' => 0, 'w' => 0, 'bl' => 0, 'a' => 1];
            $formatted = ColorFormat::HWB->format($colorData);
            expect($formatted)->toBe('hwb(0 0% 0%)');
        });

        it('formats color data correctly for LAB format', function () {
            $colorData = ['lab_l' => 50, 'lab_a' => 20, 'lab_b' => 10, 'a' => 1];
            $formatted = ColorFormat::LAB->format($colorData);
            expect($formatted)->toBe('lab(50% 20 10)');
        });

        it('formats color data correctly for LABA format', function () {
            $colorData = ['lab_l' => 50, 'lab_a' => 20, 'lab_b' => 10, 'a' => 0.8];
            $formatted = ColorFormat::LABA->format($colorData);
            expect($formatted)->toBe('lab(50% 20 10 / 0.8)');
        });

        it('formats color data correctly for LCH format', function () {
            $colorData = ['l' => 50, 'c' => 25, 'h' => 180, 'a' => 1];
            $formatted = ColorFormat::LCH->format($colorData);
            expect($formatted)->toBe('lch(50% 25 180)');
        });

        it('formats color data correctly for OKLCH format', function () {
            $colorData = ['l' => 0.5, 'c' => 0.1, 'h' => 180, 'a' => 1];
            $formatted = ColorFormat::OKLCH->format($colorData);
            expect($formatted)->toBe('oklch(50% 0.1 180)');
        });

        it('formats color data correctly for XYZ format', function () {
            $colorData = ['x' => 0.5, 'y' => 0.3, 'z' => 0.2, 'alpha' => 1];
            $formatted = ColorFormat::XYZ->format($colorData);
            expect($formatted)->toBe('color(xyz 0.5 0.3 0.2)');
        });

        it('formats color data correctly for XYZA format', function () {
            $colorData = ['x' => 0.5, 'y' => 0.3, 'z' => 0.2, 'a' => 0.9];
            $formatted = ColorFormat::XYZA->format($colorData);
            expect($formatted)->toBe('color(xyz 0.5 0.3 0.2 / 0.9)');
        });

        it('formats color data correctly for HEX format', function () {
            $colorData = ['r' => 255, 'g' => 0, 'b' => 0, 'a' => 1];
            $formatted = ColorFormat::HEX->format($colorData);
            expect($formatted)->toBe('red'); // Named color for pure red
        });

        it('formats color data correctly for HEXA format', function () {
            $colorData = ['r' => 255, 'g' => 0, 'b' => 0, 'a' => 0.5];
            $formatted = ColorFormat::HEXA->format($colorData);
            expect($formatted)->toBe('#ff000080'); // Hex with alpha
        });
    });

    describe('::getPattern', function () {
        it('returns correct pattern for HEX format', function () {
            $pattern = ColorFormat::HEX->getPattern();
            expect($pattern)->toBe('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/');
        });

        it('returns correct pattern for RGB format', function () {
            $pattern = ColorFormat::RGB->getPattern();
            expect($pattern)->toBe('/^rgb\((\d+(?:\.\d+)?%?)[\s,]+(\d+(?:\.\d+)?%?)[\s,]+(\d+(?:\.\d+)?%?)\s*(?:\/\s*([0-1]?\.\d+|0|1))?\)$/');
        });

        it('returns correct pattern for HSL format', function () {
            $pattern = ColorFormat::HSL->getPattern();
            expect($pattern)->toBe('/^hsl\((\d+(?:\.\d+)?(?:deg|rad|grad|turn)?)[\s,]+(\d+(?:\.\d+)?)%[\s,]+(\d+(?:\.\d+)?)%\s*(?:\/\s*([0-1]?\.\d+|0|1|100%|\d{1,2}%))?\)$/');
        });

        it('returns correct pattern for LAB format', function () {
            $pattern = ColorFormat::LAB->getPattern();
            expect($pattern)->toBe('/^lab\((\d+(?:\.\d+)?%?)\s+([-+]?(?:\d+(?:\.\d+)?|\.\d+))\s+([-+]?(?:\d+(?:\.\d+)?|\.\d+))\)$/i');
        });

        it('returns correct pattern for XYZ format', function () {
            $pattern = ColorFormat::XYZ->getPattern();
            expect($pattern)->toBe('/^color\(xyz\s+([-+]?(?:\d+(?:\.\d+)?|\.\d+))\s+([-+]?(?:\d+(?:\.\d+)?|\.\d+))\s+([-+]?(?:\d+(?:\.\d+)?|\.\d+))\s*(?:\/\s*([0-1]?(?:\.\d+)?|\d+%))?\)$/i');
        });
    });

    describe('::isPolar', function () {
        it('returns true for polar color spaces', function () {
            expect(ColorFormat::HSL->isPolar())->toBeTrue()
                ->and(ColorFormat::HSLA->isPolar())->toBeTrue()
                ->and(ColorFormat::HWB->isPolar())->toBeTrue()
                ->and(ColorFormat::LCH->isPolar())->toBeTrue()
                ->and(ColorFormat::OKLCH->isPolar())->toBeTrue();
        });

        it('returns false for non-polar color spaces', function () {
            expect(ColorFormat::RGB->isPolar())->toBeFalse()
                ->and(ColorFormat::RGBA->isPolar())->toBeFalse()
                ->and(ColorFormat::LAB->isPolar())->toBeFalse()
                ->and(ColorFormat::LABA->isPolar())->toBeFalse()
                ->and(ColorFormat::XYZ->isPolar())->toBeFalse()
                ->and(ColorFormat::XYZA->isPolar())->toBeFalse()
                ->and(ColorFormat::HEX->isPolar())->toBeFalse()
                ->and(ColorFormat::HEXA->isPolar())->toBeFalse();
        });
    });

    describe('::isLegacy', function () {
        it('returns true for legacy color spaces', function () {
            expect(ColorFormat::HSL->isLegacy())->toBeTrue()
                ->and(ColorFormat::HSLA->isLegacy())->toBeTrue()
                ->and(ColorFormat::HWB->isLegacy())->toBeTrue()
                ->and(ColorFormat::RGB->isLegacy())->toBeTrue()
                ->and(ColorFormat::RGBA->isLegacy())->toBeTrue();
        });

        it('returns false for modern color spaces', function () {
            expect(ColorFormat::LAB->isLegacy())->toBeFalse()
                ->and(ColorFormat::LABA->isLegacy())->toBeFalse()
                ->and(ColorFormat::LCH->isLegacy())->toBeFalse()
                ->and(ColorFormat::OKLCH->isLegacy())->toBeFalse()
                ->and(ColorFormat::XYZ->isLegacy())->toBeFalse()
                ->and(ColorFormat::XYZA->isLegacy())->toBeFalse()
                ->and(ColorFormat::HEX->isLegacy())->toBeFalse()
                ->and(ColorFormat::HEXA->isLegacy())->toBeFalse();
        });
    });

    describe('::getChannels', function () {
        it('returns all channels for RGB format', function () {
            $channels = ColorFormat::RGB->getChannels();
            expect($channels)->toBe(['red', 'r', 'green', 'g', 'blue', 'b', 'alpha', 'a']);
        });

        it('returns all channels for HSL format', function () {
            $channels = ColorFormat::HSL->getChannels();
            expect($channels)->toBe(['hue', 'h', 'saturation', 's', 'lightness', 'l', 'alpha', 'a']);
        });

        it('returns all channels for LAB format', function () {
            $channels = ColorFormat::LAB->getChannels();
            expect($channels)->toBe(['lab_l', 'lab_a', 'lab_b', 'alpha']);
        });

        it('returns minimal channels for HEX format', function () {
            $channels = ColorFormat::HEX->getChannels();
            expect($channels)->toBe(['alpha', 'a']);
        });
    });

    describe('::hasChannel', function () {
        it('returns true for existing channels', function () {
            expect(ColorFormat::RGB->hasChannel('red'))->toBeTrue()
                ->and(ColorFormat::RGB->hasChannel('r'))->toBeTrue()
                ->and(ColorFormat::HSL->hasChannel('hue'))->toBeTrue()
                ->and(ColorFormat::HSL->hasChannel('h'))->toBeTrue();
        });

        it('returns false for non-existing channels', function () {
            expect(ColorFormat::RGB->hasChannel('yellow'))->toBeFalse()
                ->and(ColorFormat::HSL->hasChannel('green'))->toBeFalse();
        });

        it('is case insensitive', function () {
            expect(ColorFormat::RGB->hasChannel('RED'))->toBeTrue()
                ->and(ColorFormat::RGB->hasChannel('Red'))->toBeTrue();
        });
    });

    describe('::isCompatibleWith', function () {
        it('returns true for same format', function () {
            expect(ColorFormat::RGB->isCompatibleWith(ColorFormat::RGB))->toBeTrue();
        });

        it('returns true for compatible formats', function () {
            expect(ColorFormat::RGB->isCompatibleWith(ColorFormat::RGBA))->toBeTrue()
                ->and(ColorFormat::RGBA->isCompatibleWith(ColorFormat::RGB))->toBeTrue()
                ->and(ColorFormat::HSL->isCompatibleWith(ColorFormat::HSLA))->toBeTrue()
                ->and(ColorFormat::LAB->isCompatibleWith(ColorFormat::LABA))->toBeTrue()
                ->and(ColorFormat::XYZ->isCompatibleWith(ColorFormat::XYZA))->toBeTrue();
        });

        it('returns false for incompatible formats', function () {
            expect(ColorFormat::RGB->isCompatibleWith(ColorFormat::HSL))->toBeFalse()
                ->and(ColorFormat::LAB->isCompatibleWith(ColorFormat::LCH))->toBeFalse();
        });

        it('returns false for null', function () {
            expect(ColorFormat::RGB->isCompatibleWith(null))->toBeFalse();
        });
    });
})->covers(ColorFormat::class);
