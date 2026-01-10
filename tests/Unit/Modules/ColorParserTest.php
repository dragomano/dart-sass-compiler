<?php

declare(strict_types=1);

use DartSass\Exceptions\CompilationException;
use DartSass\Modules\ColorFormat;
use DartSass\Modules\ColorParser;

describe('ColorParser', function () {
    describe('getPattern()', function () {
        it('returns correct pattern for all parsers', function () {
            foreach (ColorParser::cases() as $parser) {
                $pattern = $parser->getPattern();
                expect($pattern)->toBeString()->and(strlen($pattern))->toBeGreaterThan(0);
            }
        });
    });

    describe('parse()', function () {
        it('returns null for invalid input', function () {
            $result = ColorParser::HEX->parse('invalid');
            expect($result)->toBeNull();

            $result = ColorParser::RGB->parse('not-a-color');
            expect($result)->toBeNull();
        });

        it('returns null for mismatched parser', function () {
            $result = ColorParser::HEX->parse('rgb(255, 0, 0)');
            expect($result)->toBeNull();

            $result = ColorParser::RGB->parse('#ff0000');
            expect($result)->toBeNull();
        });
    });

    describe('HEX parsing', function () {
        it('parses 3-digit hex color', function () {
            $result = ColorParser::HEX->parse('#f00');

            expect($result)->toBeArray()
                ->and($result['r'])->toBe(255)
                ->and($result['g'])->toBe(0)
                ->and($result['b'])->toBe(0)
                ->and($result['a'])->toBe(1.0)
                ->and($result['format'])->toBe(ColorFormat::RGB->value);
        });

        it('parses 6-digit hex color', function () {
            $result = ColorParser::HEX->parse('#ff0000');

            expect($result['r'])->toBe(255)
                ->and($result['g'])->toBe(0)
                ->and($result['b'])->toBe(0)
                ->and($result['a'])->toBe(1.0)
                ->and($result['format'])->toBe(ColorFormat::RGB->value);
        });

        it('parses various hex colors correctly', function () {
            // Test different combinations
            $testCases = [
                ['#000', ['r' => 0, 'g' => 0, 'b' => 0]],
                ['#fff', ['r' => 255, 'g' => 255, 'b' => 255]],
                ['#abc', ['r' => 170, 'g' => 187, 'b' => 204]],
                ['#123456', ['r' => 18, 'g' => 52, 'b' => 86]],
            ];

            foreach ($testCases as [$hex, $expected]) {
                $result = ColorParser::HEX->parse($hex);
                expect($result['r'])->toBe($expected['r'])
                    ->and($result['g'])->toBe($expected['g'])
                    ->and($result['b'])->toBe($expected['b']);
            }
        });
    });

    describe('HEXA parsing', function () {
        it('parses 4-digit hex color with alpha', function () {
            $result = ColorParser::HEXA->parse('#f008');

            expect($result)->toBeArray()
                ->and($result['r'])->toBe(255)
                ->and($result['g'])->toBe(0)
                ->and($result['b'])->toBe(0)
                ->and($result['a'])->toBeCloseTo(136 / 255, 3)
                ->and($result['format'])->toBe(ColorFormat::RGBA->value);
        });

        it('parses 8-digit hex color with alpha', function () {
            $result = ColorParser::HEXA->parse('#ff000080');

            expect($result['r'])->toBe(255)
                ->and($result['g'])->toBe(0)
                ->and($result['b'])->toBe(0)
                ->and($result['a'])->toBeCloseTo(128 / 255, 3)
                ->and($result['format'])->toBe(ColorFormat::RGBA->value);
        });

        it('parses various hexa colors correctly', function () {
            $testCases = [
                ['#0000', ['r' => 0, 'g' => 0, 'b' => 0, 'a' => 0]],
                ['#ffff', ['r' => 255, 'g' => 255, 'b' => 255, 'a' => 1.0]],
                ['#abcd', ['r' => 170, 'g' => 187, 'b' => 204, 'a' => 221 / 255]],
                ['#12345678', ['r' => 18, 'g' => 52, 'b' => 86, 'a' => 120 / 255]],
            ];

            foreach ($testCases as [$hex, $expected]) {
                $result = ColorParser::HEXA->parse($hex);
                expect($result['r'])->toBe($expected['r'])
                    ->and($result['g'])->toBe($expected['g'])
                    ->and($result['b'])->toBe($expected['b'])
                    ->and($result['a'])->toBeCloseTo($expected['a'], 3);
            }
        });
    });

    describe('RGB parsing', function () {
        it('parses rgb color with integers', function () {
            $result = ColorParser::RGB->parse('rgb(255, 0, 0)');

            expect($result)->toBeArray()
                ->and($result['r'])->toBe(255)
                ->and($result['g'])->toBe(0)
                ->and($result['b'])->toBe(0)
                ->and($result['a'])->toBe(1.0)
                ->and($result['format'])->toBe(ColorFormat::RGB->value);
        });

        it('parses rgb color with percentages', function () {
            $result = ColorParser::RGB->parse('rgb(100%, 0%, 0%)');

            expect($result['r'])->toBe(255)
                ->and($result['g'])->toBe(0)
                ->and($result['b'])->toBe(0)
                ->and($result['a'])->toBe(1.0);
        });

        it('parses rgb color with mixed formats', function () {
            $result = ColorParser::RGB->parse('rgb(255, 50%, 0)');

            expect($result['r'])->toBe(255)
                ->and($result['g'])->toBe(128) // 50% of 255 ≈ 127.5, rounded to 128
                ->and($result['b'])->toBe(0);
        });

        it('parses rgb with decimal values', function () {
            $result = ColorParser::RGB->parse('rgb(255.5 127.3 63.7)');

            expect($result['r'])->toBe(255)
                ->and($result['g'])->toBe(127)
                ->and($result['b'])->toBe(64);
        });
    });

    describe('RGBA parsing', function () {
        it('parses rgba color with alpha', function () {
            $result = ColorParser::RGBA->parse('rgba(255, 0, 0, 0.5)');

            expect($result)->toBeArray()
                ->and($result['r'])->toBe(255)
                ->and($result['g'])->toBe(0)
                ->and($result['b'])->toBe(0)
                ->and($result['a'])->toBe(0.5)
                ->and($result['format'])->toBe(ColorFormat::RGBA->value);
        });

        it('throws exception for out-of-range alpha values and returns null for invalid format', function () {
            // Alpha value 1.5 matches regex but fails validation
            expect(fn() => ColorParser::RGBA->parse('rgba(255, 0, 0, 1.5)'))
                ->toThrow(CompilationException::class);

            // Alpha value -0.5 doesn't match regex at all
            $result = ColorParser::RGBA->parse('rgba(255, 0, 0, -0.5)');
            expect($result)->toBeNull();
        });
    });

    describe('HSL parsing', function () {
        it('parses hsl color', function () {
            $result = ColorParser::HSL->parse('hsl(0, 100%, 50%)');

            expect($result)->toBeArray()
                ->and($result['h'])->toBe(0.0)
                ->and($result['s'])->toBe(100.0)
                ->and($result['l'])->toBe(50.0)
                ->and($result['a'])->toBe(1.0)
                ->and($result['format'])->toBe(ColorFormat::HSL->value);
        });

        it('parses hsl with different hue units', function () {
            // Degrees (default)
            $result = ColorParser::HSL->parse('hsl(180, 50%, 50%)');
            expect($result['h'])->toBe(180.0);

            // Radians
            $result = ColorParser::HSL->parse('hsl(3.14159rad, 50%, 50%)');
            expect($result['h'])->toBeCloseTo(180.0, 1);

            // Turns
            $result = ColorParser::HSL->parse('hsl(0.5turn, 50%, 50%)');
            expect($result['h'])->toBe(180.0);
        });

        it('parses hsl with alpha', function () {
            $result = ColorParser::HSL->parse('hsl(0, 100%, 50% / 0.8)');

            expect($result['a'])->toBe(0.8)
                ->and($result['format'])->toBe(ColorFormat::HSLA->value);
        });
    });

    describe('HSLA parsing', function () {
        it('parses hsla color', function () {
            $result = ColorParser::HSLA->parse('hsla(120, 75%, 60%, 0.7)');

            expect($result)->toBeArray()
                ->and($result['h'])->toBe(120.0)
                ->and($result['s'])->toBe(75.0)
                ->and($result['l'])->toBe(60.0)
                ->and($result['a'])->toBe(0.7)
                ->and($result['format'])->toBe(ColorFormat::HSLA->value);
        });

        it('handles percentage alpha in hsla', function () {
            $result = ColorParser::HSLA->parse('hsla(120, 75%, 60%, 50%)');
            expect($result['a'])->toBe(0.5);
        });
    });

    describe('HWB parsing', function () {
        it('parses hwb color', function () {
            $result = ColorParser::HWB->parse('hwb(0, 20%, 30%)');

            expect($result)->toBeArray()
                ->and($result['h'])->toEqual(0.0)
                ->and($result['w'])->toEqual(20.0)
                ->and($result['bl'])->toEqual(30.0)
                ->and($result['a'])->toEqual(1.0)
                ->and($result['format'])->toBe(ColorFormat::HWB->value);
        });

        it('parses hwb with alpha', function () {
            $result = ColorParser::HWB->parse('hwb(120, 10%, 20% / 0.6)');

            expect($result['a'])->toBe(0.6)
                ->and($result['format'])->toBe(ColorFormat::HWB->value);
        });

        it('handles hwb with percentage alpha', function () {
            $result = ColorParser::HWB->parse('hwb(120, 10%, 20% / 50%)');
            expect($result['a'])->toBe(0.5);
        });
    });

    describe('LAB parsing', function () {
        it('parses lab color with numeric lightness', function () {
            $result = ColorParser::LAB->parse('lab(50 20 -30)');

            expect($result)->toBeArray()
                ->and($result['lab_l'])->toEqual(50.0)
                ->and($result['lab_a'])->toEqual(20.0)
                ->and($result['lab_b'])->toEqual(-30.0)
                ->and($result['a'])->toEqual(1.0)
                ->and($result['format'])->toBe(ColorFormat::LAB->value);
        });

        it('parses lab color with percentage lightness', function () {
            $result = ColorParser::LAB->parse('lab(75% 10 -20)');

            expect($result['lab_l'])->toBe(75.0);
        });

        it('handles lab with percentage lightness', function () {
            $result = ColorParser::LAB->parse('lab(75% 10 -20)');
            expect($result['lab_l'])->toBe(75.0);
        });
    });

    describe('LABA parsing', function () {
        it('parses laba color', function () {
            $result = ColorParser::LABA->parse('lab(60 15 -25 / 0.8)');

            expect($result)->toBeArray()
                ->and($result['lab_l'])->toEqual(60.0)
                ->and($result['lab_a'])->toEqual(15.0)
                ->and($result['lab_b'])->toEqual(-25.0)
                ->and($result['a'])->toEqual(0.8)
                ->and($result['format'])->toBe(ColorFormat::LABA->value);
        });

        it('parses laba with percentage alpha', function () {
            $result = ColorParser::LABA->parse('lab(60 15 -25 / 80%)');

            expect($result['a'])->toBe(0.8);
        });

        it('handles laba with percentage alpha', function () {
            $result = ColorParser::LABA->parse('lab(60 15 -25 / 80%)');
            expect($result['a'])->toBe(0.8);
        });
    });

    describe('LCH parsing', function () {
        it('parses lch color', function () {
            $result = ColorParser::LCH->parse('lch(60 50 120)');

            expect($result)->toBeArray()
                ->and($result['l'])->toEqual(60.0)
                ->and($result['c'])->toEqual(50.0)
                ->and($result['h'])->toEqual(120.0)
                ->and($result['a'])->toEqual(1.0)
                ->and($result['format'])->toBe(ColorFormat::LCH->value);
        });

        it('handles lch with percentage alpha', function () {
            $result = ColorParser::LCH->parse('lch(60 50 120 / 70%)');
            expect($result['a'])->toBe(0.7);
        });

        it('parses lch with alpha', function () {
            $result = ColorParser::LCH->parse('lch(60 50 120 / 0.7)');

            expect($result['a'])->toBe(0.7)
                ->and($result['format'])->toBe(ColorFormat::LCH->value);
        });
    });

    describe('OKLCH parsing', function () {
        it('parses oklch color', function () {
            $result = ColorParser::OKLCH->parse('oklch(60 0.3 120)');

            expect($result)->toBeArray()
                ->and($result['l'])->toBe(60.0)
                ->and($result['c'])->toBe(0.3)
                ->and($result['h'])->toBe(120.0)
                ->and($result['a'])->toBe(1.0)
                ->and($result['format'])->toBe(ColorFormat::OKLCH->value);
        });

        it('handles oklch with percentage alpha', function () {
            $result = ColorParser::OKLCH->parse('oklch(0.6 0.3 120 / 90%)');
            expect($result['a'])->toBe(0.9);
        });

        it('parses oklch with alpha', function () {
            $result = ColorParser::OKLCH->parse('oklch(0.6 0.3 120 / 0.9)');

            expect($result['a'])->toBe(0.9)
                ->and($result['format'])->toBe(ColorFormat::OKLCH->value);
        });
    });

    describe('XYZ parsing', function () {
        it('gets xyz pattern', function () {
            $pattern = ColorParser::XYZ->getPattern();
            expect($pattern)->toBeString()
                ->and(preg_match($pattern, 'color(xyz 0.5 0.3 0.8)'))->toBe(1);
        });

        it('parses xyz color', function () {
            $result = ColorParser::XYZ->parse('color(xyz 0.5 0.3 0.8)');

            expect($result)->toBeArray()
                ->and($result['x'])->toBe(0.5)
                ->and($result['y'])->toBe(0.3)
                ->and($result['z'])->toBe(0.8)
                ->and($result['a'])->toBe(1.0)
                ->and($result['format'])->toBe(ColorFormat::XYZ->value);
        });

        it('parses xyz with negative values', function () {
            $result = ColorParser::XYZ->parse('color(xyz -0.2 0.5 -0.1)');

            expect($result['x'])->toBe(-0.2)
                ->and($result['y'])->toBe(0.5)
                ->and($result['z'])->toBe(-0.1);
        });

        it('parses xyz with alpha', function () {
            $result = ColorParser::XYZ->parse('color(xyz 0.5 0.3 0.8 / 0.6)');

            expect($result['a'])->toBe(0.6)
                ->and($result['format'])->toBe(ColorFormat::XYZA->value);
        });

        it('parses xyz with percentage alpha', function () {
            $result = ColorParser::XYZ->parse('color(xyz 0.5 0.3 0.8 / 60%)');

            expect($result['a'])->toBe(0.6);
        });
    });

    describe('XYZA parsing', function () {
        it('parses xyza color', function () {
            $result = ColorParser::XYZA->parse('color(xyz 0.4 0.2 0.7 / 0.8)');

            expect($result)->toBeArray()
                ->and($result['x'])->toBe(0.4)
                ->and($result['y'])->toBe(0.2)
                ->and($result['z'])->toBe(0.7)
                ->and($result['a'])->toBe(0.8)
                ->and($result['format'])->toBe(ColorFormat::XYZA->value);
        });

        it('handles xyza with percentage alpha', function () {
            $result = ColorParser::XYZA->parse('color(xyz 0.4 0.2 0.7 / 80%)');
            expect($result['a'])->toBe(0.8);
        });
    });

    describe('Helper methods', function () {
        describe('parseHueValue()', function () {
            it('parses different hue units correctly', function () {
                // This tests the internal helper method indirectly through HSL parsing
                $result = ColorParser::HSL->parse('hsl(180, 50%, 50%)');
                expect($result['h'])->toBe(180.0);

                $result = ColorParser::HSL->parse('hsl(3.14159rad, 50%, 50%)');
                expect($result['h'])->toBeCloseTo(180.0, 1);

                $result = ColorParser::HSL->parse('hsl(200grad, 50%, 50%)');
                expect($result['h'])->toBe(180.0);

                $result = ColorParser::HSL->parse('hsl(0.5turn, 50%, 50%)');
                expect($result['h'])->toBe(180.0);
            });

            it('normalizes negative and oversized hue values', function () {
                $result = ColorParser::HSL->parse('hsl(-90, 50%, 50%)');
                expect($result['h'])->toBe(270.0);

                $result = ColorParser::HSL->parse('hsl(450, 50%, 50%)');
                expect($result['h'])->toBe(90.0);
            });
        });

        describe('parsePercentageValue()', function () {
            it('removes percentage sign correctly', function () {
                $result = ColorParser::RGB->parse('rgb(50%, 75%, 25%)');

                // 50% of 255 = 127.5 ≈ 128
                expect($result['r'])->toBe(128)
                    ->and($result['g'])->toBe(191) // 75% of 255 = 191.25 ≈ 191
                    ->and($result['b'])->toBe(64);  // 25% of 255 = 63.75 ≈ 64
            });
        });

        describe('parseAlpha()', function () {
            it('handles numeric alpha values', function () {
                $result = ColorParser::RGBA->parse('rgba(255, 0, 0, 0.5)');
                expect($result['a'])->toEqual(0.5);
            });
        });

        describe('parsePercentageValue()', function () {
            it('handles percentage values correctly', function () {
                $result = ColorParser::RGB->parse('rgb(50% 75% 25%)');

                // 50% of 255 = 127.5 ≈ 128
                expect($result['r'])->toBe(128)
                    ->and($result['g'])->toBe(191) // 75% of 255 = 191.25 ≈ 191
                    ->and($result['b'])->toBe(64);  // 25% of 255 = 63.75 ≈ 64
            });
        });
    });

    describe('Edge cases and error handling', function () {
        it('handles whitespace in color strings', function () {
            $result = ColorParser::RGB->parse('rgb(255 0 0)');
            expect($result['r'])->toBe(255)
                ->and($result['g'])->toBe(0)
                ->and($result['b'])->toBe(0);
        });

        it('handles case insensitive parsing', function () {
            // Most parsers are case-sensitive, but let's test some variations
            $result = ColorParser::HEX->parse('#FF0000');
            expect($result['r'])->toBe(255);
        });

        it('returns null for completely invalid formats', function () {
            $parsers = ColorParser::cases();
            $invalidInputs = ['', '   ', 'not-a-color', 'rgb(invalid)', '#gg0000'];

            foreach ($parsers as $parser) {
                foreach ($invalidInputs as $input) {
                    $result = $parser->parse($input);
                    expect($result)->toBeNull();
                }
            }
        });
    });
})->covers(ColorParser::class);
