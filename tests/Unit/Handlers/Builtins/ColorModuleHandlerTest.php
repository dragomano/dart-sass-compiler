<?php

declare(strict_types=1);

use DartSass\Exceptions\CompilationException;
use DartSass\Handlers\Builtins\ColorModuleHandler;
use DartSass\Handlers\Builtins\CssColorFunctionHandler;
use DartSass\Handlers\SassModule;
use DartSass\Modules\ColorModule;
use Tests\ReflectionAccessor;

beforeEach(function () {
    $this->colorModule     = new ColorModule();
    $this->cssColorHandler = new CssColorFunctionHandler();
    $this->handler         = new ColorModuleHandler($this->colorModule, $this->cssColorHandler);
    $this->accessor        = new ReflectionAccessor($this->handler);
});

describe('ColorModuleHandler', function () {
    describe('canHandle method', function () {
        it('returns true for module functions', function () {
            expect($this->handler->canHandle('adjust'))->toBeTrue()
                ->and($this->handler->canHandle('change'))->toBeTrue()
                ->and($this->handler->canHandle('channel'))->toBeTrue()
                ->and($this->handler->canHandle('complement'))->toBeTrue()
                ->and($this->handler->canHandle('grayscale'))->toBeTrue()
                ->and($this->handler->canHandle('ie-hex-str'))->toBeTrue()
                ->and($this->handler->canHandle('invert'))->toBeTrue()
                ->and($this->handler->canHandle('is-legacy'))->toBeTrue()
                ->and($this->handler->canHandle('is-missing'))->toBeTrue()
                ->and($this->handler->canHandle('is-powerless'))->toBeTrue()
                ->and($this->handler->canHandle('mix'))->toBeTrue()
                ->and($this->handler->canHandle('same'))->toBeTrue()
                ->and($this->handler->canHandle('scale'))->toBeTrue()
                ->and($this->handler->canHandle('space'))->toBeTrue()
                ->and($this->handler->canHandle('to-gamut'))->toBeTrue()
                ->and($this->handler->canHandle('to-space'))->toBeTrue()
                ->and($this->handler->canHandle('alpha'))->toBeTrue()
                ->and($this->handler->canHandle('blackness'))->toBeTrue()
                ->and($this->handler->canHandle('red'))->toBeTrue()
                ->and($this->handler->canHandle('green'))->toBeTrue()
                ->and($this->handler->canHandle('blue'))->toBeTrue()
                ->and($this->handler->canHandle('hue'))->toBeTrue()
                ->and($this->handler->canHandle('lightness'))->toBeTrue()
                ->and($this->handler->canHandle('saturation'))->toBeTrue()
                ->and($this->handler->canHandle('whiteness'))->toBeTrue()
                ->and($this->handler->canHandle('hwb'))->toBeTrue();
        });

        it('returns true for legacy functions', function () {
            expect($this->handler->canHandle('adjust-color'))->toBeTrue()
                ->and($this->handler->canHandle('change-color'))->toBeTrue()
                ->and($this->handler->canHandle('complement'))->toBeTrue()
                ->and($this->handler->canHandle('grayscale'))->toBeTrue()
                ->and($this->handler->canHandle('ie-hex-str'))->toBeTrue()
                ->and($this->handler->canHandle('invert'))->toBeTrue()
                ->and($this->handler->canHandle('mix'))->toBeTrue()
                ->and($this->handler->canHandle('scale-color'))->toBeTrue()
                ->and($this->handler->canHandle('adjust-hue'))->toBeTrue()
                ->and($this->handler->canHandle('alpha'))->toBeTrue()
                ->and($this->handler->canHandle('opacity'))->toBeTrue()
                ->and($this->handler->canHandle('blackness'))->toBeTrue()
                ->and($this->handler->canHandle('red'))->toBeTrue()
                ->and($this->handler->canHandle('green'))->toBeTrue()
                ->and($this->handler->canHandle('blue'))->toBeTrue()
                ->and($this->handler->canHandle('darken'))->toBeTrue()
                ->and($this->handler->canHandle('desaturate'))->toBeTrue()
                ->and($this->handler->canHandle('hue'))->toBeTrue()
                ->and($this->handler->canHandle('lighten'))->toBeTrue()
                ->and($this->handler->canHandle('lightness'))->toBeTrue()
                ->and($this->handler->canHandle('opacify'))->toBeTrue()
                ->and($this->handler->canHandle('fade-in'))->toBeTrue()
                ->and($this->handler->canHandle('fade-out'))->toBeTrue()
                ->and($this->handler->canHandle('saturate'))->toBeTrue()
                ->and($this->handler->canHandle('saturation'))->toBeTrue()
                ->and($this->handler->canHandle('transparentize'))->toBeTrue();
        });

        it('returns false for unknown functions', function () {
            expect($this->handler->canHandle('unknown-function'))->toBeFalse()
                ->and($this->handler->canHandle('non-existent'))->toBeFalse();
        });
    });

    describe('handle method', function () {
        it('throws exception for unknown color function', function () {
            expect(fn() => $this->handler->handle('unknown-function', ['#ff0000']))
                ->toThrow(CompilationException::class, 'Unknown color function: unknown-function');
        });

        it('throws exception for non-existent method in ColorModule', function () {
            expect(fn() => $this->handler->handle('unknown', ['#ff0000']))
                ->toThrow(CompilationException::class, 'Unknown color function: unknown');
        });

        it('handles valid color functions correctly', function () {
            $result = $this->handler->handle('red', ['#ff0000']);
            expect($result)->toBeString();
        });

        it('handles CSS filter functions correctly', function () {
            $result = $this->handler->handle('blur', ['5px']);
            expect($result)->toContain('blur')
                ->and($result)->toContain('5px');
        });

        it('handles mix with named sass arguments', function () {
            $result = $this->handler->handle('mix', [
                '$color1' => '#fff',
                '$color2' => '#000',
                '$weight' => ['value' => 20.0, 'unit' => '%'],
            ]);

            expect($result)->toBe('#333333');
        });

        it('returns CSS function call when first argument is not valid color format in scale-color', function () {
            // Test with numeric value as first argument to trigger the !isValidColorFormat branch
            $result = $this->handler->handle('scale-color', [123]);
            expect($result)->toBe('scale(123)');

            // Test with invalid string format
            $result = $this->handler->handle('scale-color', ['not-a-color']);
            expect($result)->toBe('scale(not-a-color)');
        });
    });

    describe('getSupportedFunctions method', function () {
        it('returns all supported functions', function () {
            $functions = $this->handler->getSupportedFunctions();

            expect($functions)->toContain('adjust')
                ->and($functions)->toContain('change')
                ->and($functions)->toContain('mix')
                ->and($functions)->toContain('scale')
                ->and($functions)->toContain('red')
                ->and($functions)->toContain('green')
                ->and($functions)->toContain('blue')
                ->and($functions)->toContain('adjust-color')
                ->and($functions)->toContain('change-color')
                ->and($functions)->toContain('scale-color');
        });
    });

    describe('getModuleNamespace method', function () {
        it('returns correct namespace', function () {
            expect($this->handler->getModuleNamespace())->toEqual(SassModule::COLOR);
        });
    });

    describe('getModuleFunctions method', function () {
        it('returns module functions', function () {
            $functions = $this->handler->getModuleFunctions();

            expect($functions)->toContain('adjust')
                ->and($functions)->toContain('change')
                ->and($functions)->toContain('mix')
                ->and($functions)->toContain('scale')
                ->and($functions)->not()->toContain('adjust-color') // Legacy functions excluded
                ->and($functions)->not()->toContain('change-color');
        });
    });

    describe('isValidColorFormat method', function () {
        it('returns false for numeric values', function () {
            // Tests the first condition: if (is_numeric($color))
            expect($this->accessor->callMethod('isValidColorFormat', ['123']))->toBeFalse()
                ->and($this->accessor->callMethod('isValidColorFormat', ['123.45']))->toBeFalse()
                ->and($this->accessor->callMethod('isValidColorFormat', ['0']))->toBeFalse();
        });

        it('returns true for named colors', function () {
            // Tests the second condition: if (isset(ColorSerializer::NAMED_COLORS[$color]))
            expect($this->accessor->callMethod('isValidColorFormat', ['red']))->toBeTrue()
                ->and($this->accessor->callMethod('isValidColorFormat', ['blue']))->toBeTrue()
                ->and($this->accessor->callMethod('isValidColorFormat', ['green']))->toBeTrue();
            // Note: 'transparent' might not be in NAMED_COLORS, so we exclude it
        });

        it('returns true for hex color formats', function () {
            // Tests the third condition: foreach loop with preg_match for HEX formats
            expect($this->accessor->callMethod('isValidColorFormat', ['#ff0000']))->toBeTrue()
                ->and($this->accessor->callMethod('isValidColorFormat', ['#f00']))->toBeTrue()
                ->and($this->accessor->callMethod('isValidColorFormat', ['#ff000080']))->toBeTrue()
                ->and($this->accessor->callMethod('isValidColorFormat', ['#f008']))->toBeTrue();
        });

        it('returns true for rgb color formats', function () {
            // Tests the third condition: foreach loop with preg_match for RGB formats
            expect($this->accessor->callMethod('isValidColorFormat', ['rgb(255, 0, 0)']))->toBeTrue()
                ->and($this->accessor->callMethod('isValidColorFormat', ['rgba(255, 0, 0, 0.5)']))->toBeTrue()
                ->and($this->accessor->callMethod('isValidColorFormat', ['rgb(100% 0% 0%)']))->toBeTrue();
        });

        it('returns true for hsl color formats', function () {
            // Tests the third condition: foreach loop with preg_match for HSL formats
            expect($this->accessor->callMethod('isValidColorFormat', ['hsl(0, 100%, 50%)']))->toBeTrue()
                ->and($this->accessor->callMethod('isValidColorFormat', ['hsla(0, 100%, 50%, 0.5)']))->toBeTrue()
                ->and($this->accessor->callMethod('isValidColorFormat', ['hsl(0deg 100% 50%)']))->toBeTrue();
        });

        it('returns true for hwb color formats', function () {
            // Tests the third condition: foreach loop with preg_match for HWB formats
            expect($this->accessor->callMethod('isValidColorFormat', ['hwb(0, 0%, 0%)']))->toBeTrue()
                ->and($this->accessor->callMethod('isValidColorFormat', ['hwb(0deg 0% 0% / 0.5)']))->toBeTrue();
        });

        it('returns true for lab color formats', function () {
            // Tests the third condition: foreach loop with preg_match for LAB formats
            expect($this->accessor->callMethod('isValidColorFormat', ['lab(50% 20 30)']))->toBeTrue();
            // Note: LABA format might have different pattern requirements
        });

        it('returns true for lch color formats', function () {
            // Tests the third condition: foreach loop with preg_match for LCH formats
            expect($this->accessor->callMethod('isValidColorFormat', ['lch(50% 30 120)']))->toBeTrue()
                ->and($this->accessor->callMethod('isValidColorFormat', ['lch(50% 30 120 / 0.5)']))->toBeTrue();
        });

        it('returns true for oklch color formats', function () {
            // Tests the third condition: foreach loop with preg_match for OKLCH formats
            expect($this->accessor->callMethod('isValidColorFormat', ['oklch(60% 0.2 120)']))->toBeTrue()
                ->and($this->accessor->callMethod('isValidColorFormat', ['oklch(60% 0.2 120 / 0.5)']))->toBeTrue();
        });

        it('returns true for xyz color formats', function () {
            // Tests the third condition: foreach loop with preg_match for XYZ formats
            expect($this->accessor->callMethod('isValidColorFormat', ['color(xyz 0.5 0.3 0.2)']))->toBeTrue();
            // Note: XYZ format uses 'color(xyz ...)' syntax according to ColorFormat patterns
        });

        it('returns false for invalid color formats', function () {
            // Tests the final return false statement
            expect($this->accessor->callMethod('isValidColorFormat', ['invalid-color']))->toBeFalse()
                ->and($this->accessor->callMethod('isValidColorFormat', ['not-a-color']))->toBeFalse()
                ->and($this->accessor->callMethod('isValidColorFormat', ['']))->toBeFalse()
                ->and($this->accessor->callMethod('isValidColorFormat', ['  ']))->toBeFalse();
        });

        it('handles whitespace trimming', function () {
            // Tests that whitespace is properly trimmed before processing
            expect($this->accessor->callMethod('isValidColorFormat', [' red ']))->toBeTrue()
                ->and($this->accessor->callMethod('isValidColorFormat', [' #ff0000 ']))->toBeTrue()
                ->and($this->accessor->callMethod('isValidColorFormat', [' rgb(255, 0, 0) ']))->toBeTrue();
        });
    });
})->covers(ColorModuleHandler::class);
