<?php

declare(strict_types=1);

use DartSass\Modules\ColorFormat;
use DartSass\Values\SassColor;

describe('SassColor factory methods', function () {
    it('creates from RGB values', function () {
        $color = SassColor::rgb(255, 0, 0);

        expect($color->getRed())->toBe(255.0)
            ->and($color->getGreen())->toBe(0.0)
            ->and($color->getBlue())->toBe(0.0)
            ->and($color->getAlpha())->toBe(1.0)
            ->and($color->getFormat())->toBe(ColorFormat::RGB->value)
            ->and((string) $color)->toBe('red');
    });

    it('creates from RGB values with alpha', function () {
        $color = SassColor::rgb(255, 0, 0, 0.5);

        expect($color->getRed())->toBe(255.0)
            ->and($color->getGreen())->toBe(0.0)
            ->and($color->getBlue())->toBe(0.0)
            ->and($color->getAlpha())->toBe(0.5)
            ->and($color->getFormat())->toBe(ColorFormat::RGBA->value)
            ->and((string) $color)->toMatch('/^#[0-9a-f]{8}$/i');
    });

    it('creates from HSL values', function () {
        $color = SassColor::hsl(0, 100, 50);

        expect($color->getHue())->toBe(0.0)
            ->and($color->getSaturation())->toBe(100.0)
            ->and($color->getLightness())->toBe(50.0)
            ->and($color->getAlpha())->toBe(1.0)
            ->and($color->getFormat())->toBe(ColorFormat::HSL->value)
            ->and((string) $color)->toBe('hsl(0, 100%, 50%)');
    });

    it('creates from HSL values with alpha', function () {
        $color = SassColor::hsl(0, 100, 50, 0.8);

        expect($color->getHue())->toBe(0.0)
            ->and($color->getSaturation())->toBe(100.0)
            ->and($color->getLightness())->toBe(50.0)
            ->and($color->getAlpha())->toBe(0.8)
            ->and($color->getFormat())->toBe(ColorFormat::HSLA->value)
            ->and((string) $color)->toBe('hsla(0, 100%, 50%, 0.8)');
    });

    it('creates from HWB values', function () {
        $color = SassColor::hwb(0, 0, 0);

        expect($color->getHue())->toBe(0.0)
            ->and($color->getFormat())->toBe(ColorFormat::HWB->value)
            ->and((string) $color)->toBe('hwb(0 0% 0%)');
    });

    it('creates from HWB values with alpha', function () {
        $color = SassColor::hwb(120, 20, 30, 0.7);

        expect($color->getAlpha())->toBe(0.7)
            ->and($color->getFormat())->toBe(ColorFormat::HWB->value);
    });

    it('creates from LAB values', function () {
        $color = SassColor::lab(50, -10, 20);

        expect($color->getLabL())->toBe(50.0)
            ->and($color->getLabA())->toBe(-10.0)
            ->and($color->getLabB())->toBe(20.0)
            ->and($color->getAlpha())->toBe(1.0)
            ->and($color->getFormat())->toBe(ColorFormat::LAB->value)
            ->and((string) $color)->toBe('lab(50% -10 20)');
    });

    it('creates from LAB values with alpha', function () {
        $color = SassColor::lab(60, 15, -5, 0.8);

        expect($color->getLabL())->toBe(60.0)
            ->and($color->getLabA())->toBe(15.0)
            ->and($color->getLabB())->toBe(-5.0)
            ->and($color->getAlpha())->toBe(0.8)
            ->and($color->getFormat())->toBe(ColorFormat::LABA->value)
            ->and((string) $color)->toBe('lab(60% 15 -5 / 0.8)');
    });

    it('creates from LCH values', function () {
        $color = SassColor::lch(60, 40, 30);

        expect($color->getFormat())->toBe(ColorFormat::LCH->value)
            ->and($color->getAlpha())->toBe(1.0)
            ->and((string) $color)->toBe('lch(60% 40 30)');
    });

    it('creates from LCH values with alpha', function () {
        $color = SassColor::lch(60, 40, 30, 0.9);

        expect($color->getAlpha())->toBe(0.9)
            ->and($color->getFormat())->toBe(ColorFormat::LCH->value);
    });

    it('creates from OKLCH values', function () {
        $color = SassColor::oklch(60, 0.15, 30);

        expect($color->getFormat())->toBe(ColorFormat::OKLCH->value)
            ->and($color->getAlpha())->toBe(1.0)
            ->and((string) $color)->toBe('oklch(60% 0.15 30)');
    });

    it('creates from OKLCH values with alpha', function () {
        $color = SassColor::oklch(60, 0.15, 30, 0.6);

        expect($color->getAlpha())->toBe(0.6)
            ->and($color->getFormat())->toBe(ColorFormat::OKLCH->value);
    });

    it('creates from XYZ values', function () {
        $color = SassColor::xyz(0.5, 0.3, 0.2);

        expect($color->getFormat())->toBe(ColorFormat::XYZ->value)
            ->and($color->getAlpha())->toBe(1.0)
            ->and((string) $color)->toBe('color(xyz 0.5 0.3 0.2)');
    });

    it('creates from XYZ values with alpha', function () {
        $color = SassColor::xyz(0.5, 0.3, 0.2, 0.4);

        expect($color->getAlpha())->toBe(0.4)
            ->and($color->getFormat())->toBe(ColorFormat::XYZA->value);
    });
});

describe('SassColor getters', function () {
    it('returns RGB channel values', function () {
        $color = SassColor::rgb(128, 64, 192);

        expect($color->getRed())->toBe(128.0)
            ->and($color->getGreen())->toBe(64.0)
            ->and($color->getBlue())->toBe(192.0);
    });

    it('returns HSL channel values', function () {
        $color = SassColor::hsl(240, 75, 50);

        expect($color->getHue())->toBe(240.0)
            ->and($color->getSaturation())->toBe(75.0)
            ->and($color->getLightness())->toBe(50.0);
    });

    it('returns LAB channel values', function () {
        $color = SassColor::lab(75, 25, -30);

        expect($color->getLabL())->toBe(75.0)
            ->and($color->getLabA())->toBe(25.0)
            ->and($color->getLabB())->toBe(-30.0);
    });

    it('returns alpha channel', function () {
        $color = SassColor::rgb(255, 0, 0, 0.5);

        expect($color->getAlpha())->toBe(0.5);
    });

    it('returns default values for missing channels', function () {
        $color = new SassColor(['format' => ColorFormat::RGB->value]);

        expect($color->getRed())->toBe(0.0)
            ->and($color->getGreen())->toBe(0.0)
            ->and($color->getBlue())->toBe(0.0)
            ->and($color->getAlpha())->toBe(1.0)
            ->and($color->getHue())->toBe(0.0)
            ->and($color->getSaturation())->toBe(0.0)
            ->and($color->getLightness())->toBe(0.0);
    });

    it('returns format', function () {
        $color = SassColor::rgb(255, 0, 0);

        expect($color->getFormat())->toBe(ColorFormat::RGB->value);
    });
});

describe('SassColor format handling', function () {
    it('preserves RGB format without alpha', function () {
        $color = SassColor::rgb(255, 0, 0, 1.0);

        expect($color->getFormat())->toBe(ColorFormat::RGB->value);
    });

    it('uses RGBA format with alpha', function () {
        $color = SassColor::rgb(255, 0, 0, 0.5);

        expect($color->getFormat())->toBe(ColorFormat::RGBA->value);
    });

    it('preserves HSL format without alpha', function () {
        $color = SassColor::hsl(0, 100, 50, 1.0);

        expect($color->getFormat())->toBe(ColorFormat::HSL->value);
    });

    it('uses HSLA format with alpha', function () {
        $color = SassColor::hsl(0, 100, 50, 0.5);

        expect($color->getFormat())->toBe(ColorFormat::HSLA->value);
    });

    it('preserves XYZ format without alpha', function () {
        $color = SassColor::xyz(0.5, 0.3, 0.2, 1.0);

        expect($color->getFormat())->toBe(ColorFormat::XYZ->value);
    });

    it('uses XYZA format with alpha', function () {
        $color = SassColor::xyz(0.5, 0.3, 0.2, 0.5);

        expect($color->getFormat())->toBe(ColorFormat::XYZA->value);
    });

    it('preserves LAB format without alpha', function () {
        $color = SassColor::lab(50, -10, 20, 1.0);

        expect($color->getFormat())->toBe(ColorFormat::LAB->value);
    });

    it('uses LABA format with alpha', function () {
        $color = SassColor::lab(50, -10, 20, 0.5);

        expect($color->getFormat())->toBe(ColorFormat::LABA->value);
    });
});

describe('SassColor string representation', function () {
    it('formats RGB color as named color', function () {
        $color = SassColor::rgb(255, 0, 0);

        expect((string) $color)->toBe('red');
    });

    it('formats RGBA color as hex with alpha', function () {
        $color = SassColor::rgb(255, 0, 0, 0.5);

        expect((string) $color)->toMatch('/^#[0-9a-f]{8}$/i');
    });

    it('formats HSL color', function () {
        $color = SassColor::hsl(120, 50, 50);

        expect((string) $color)->toBe('hsl(120, 50%, 50%)');
    });

    it('formats HWB color', function () {
        $color = SassColor::hwb(240, 10, 20);

        expect((string) $color)->toContain('hwb(');
    });

    it('formats LAB color', function () {
        $color = SassColor::lab(50, -10, 20);

        expect((string) $color)->toBe('lab(50% -10 20)');
    });

    it('formats LABA color with alpha', function () {
        $color = SassColor::lab(60, 15, -5, 0.8);

        expect((string) $color)->toBe('lab(60% 15 -5 / 0.8)');
    });

    it('uses fallback format when unknown', function () {
        $color = new SassColor(['r' => 255, 'g' => 0, 'b' => 0, 'a' => 1.0]);

        expect((string) $color)->toBeString();
    });
});

describe('SassColor supported conversions', function () {
    it('returns list of supported formats', function () {
        $color     = SassColor::rgb(255, 0, 0);
        $supported = $color->getSupportedConversions();

        expect($supported)->toBeArray()
            ->and($supported)->toContain(
                ColorFormat::HSL->value,
                ColorFormat::HSLA->value,
                ColorFormat::HWB->value,
                ColorFormat::LAB->value,
                ColorFormat::LABA->value,
                ColorFormat::LCH->value,
                ColorFormat::OKLCH->value,
                ColorFormat::XYZ->value,
                ColorFormat::XYZA->value,
                ColorFormat::RGB->value,
                ColorFormat::RGBA->value,
            );
    });
});

describe('SassColor edge cases', function () {
    it('handles zero values', function () {
        $color = SassColor::rgb(0, 0, 0);

        expect($color->getRed())->toBe(0.0)
            ->and($color->getGreen())->toBe(0.0)
            ->and($color->getBlue())->toBe(0.0);
    });

    it('handles maximum values', function () {
        $color = SassColor::rgb(255, 255, 255);

        expect($color->getRed())->toBe(255.0)
            ->and($color->getGreen())->toBe(255.0)
            ->and($color->getBlue())->toBe(255.0);
    });

    it('handles zero LAB values', function () {
        $color = SassColor::lab(0, 0, 0);

        expect($color->getLabL())->toBe(0.0)
            ->and($color->getLabA())->toBe(0.0)
            ->and($color->getLabB())->toBe(0.0);
    });
});
