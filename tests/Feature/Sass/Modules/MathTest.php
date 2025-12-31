<?php

declare(strict_types=1);

use DartSass\Compiler;

beforeEach(function () {
    $this->compiler = new Compiler();
});

describe('sass:math', function () {
    it('supports math constants', function () {
        $scss = <<<'SCSS'
        @use "sass:math";

        .class {
            width: math.$e * 1px;
            height: math.$epsilon * 1px;
            top: math.$pi * 1px;
            right: math.$max-number * 0.001px;
            bottom: math.$min-number * 0.001px;
            left: math.$max-safe-integer * 0px;
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .class {
          width: 2.718281828459px;
          height: 0;
          top: 3.1415926535898px;
          right: 1.7976931348623E+305px;
          bottom: 0;
          left: 0;
        }
        CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    });

    describe('supports math.hypot', function () {
        it('returns Euclidean distance (3, 4) → 5', function () {
            $scss = <<<'SCSS'
            @use "sass:math";

            .test {
                width: math.hypot(3, 4) * 1px;
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              width: 5px;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('handles 3D distance (1, 1, 1) → √3', function () {
            $scss = <<<'SCSS'
            @use "sass:math";

            .test {
                width: round(math.hypot(1, 1, 1)) * 1px;
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              width: 2px;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('handles zero and single values', function () {
            $scss = <<<'SCSS'
            @use "sass:math";

            .test {
                zero: math.hypot(0, 0) * 1px;
                single: math.hypot(5) * 1px;
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              zero: 0px;
              single: 5px;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });
    });

    describe('supports math.log', function () {
        it('calculates natural logarithm math.log(10)', function () {
            $scss = <<<'SCSS'
            @use "sass:math";

            .test {
                width: round(math.log(10) * 100) * 0.01px;
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              width: 2.3px;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('calculates log base 10 math.log(10, 10)', function () {
            $scss = <<<'SCSS'
            @use "sass:math";

            .test {
                width: math.log(10, 10) * 1px;
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              width: 1px;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('handles various logarithm values', function () {
            $scss = <<<'SCSS'
            @use "sass:math";

            .test {
                ln-e: round(math.log(math.$e) * 100) * 0.01px;
                log10-100: math.log(100, 10) * 1px;
                ln-1: math.log(1) * 1px;
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              ln-e: 1px;
              log10-100: 2px;
              ln-1: 0px;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });
    });

    describe('supports math.ceil', function () {
        it('rounds up positive number', function () {
            $scss = <<<'SCSS'
            @use "sass:math";

            .test {
                width: math.ceil(4.1) * 1px;
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              width: 5px;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('rounds up negative number', function () {
            $scss = <<<'SCSS'
            @use "sass:math";

            .test {
                width: math.ceil(-4.1) * 1px;
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              width: -4px;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('rounds up number with unit', function () {
            $scss = <<<'SCSS'
            @use "sass:math";

            .test {
                width: math.ceil(4.1px);
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              width: 5px;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });
    });

    describe('supports math.floor', function () {
        it('rounds down positive number', function () {
            $scss = <<<'SCSS'
            @use "sass:math";

            .test {
                width: math.floor(4.9) * 1px;
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              width: 4px;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('rounds down negative number', function () {
            $scss = <<<'SCSS'
            @use "sass:math";

            .test {
                width: math.floor(-4.9) * 1px;
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              width: -5px;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('rounds down number with unit', function () {
            $scss = <<<'SCSS'
            @use "sass:math";

            .test {
                width: math.floor(4.9px);
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              width: 4px;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });
    });

    describe('supports math.round', function () {
        it('rounds number', function () {
            $scss = <<<'SCSS'
            @use "sass:math";

            .test {
                width: math.round(4.6) * 1px;
                height: math.round(4.4) * 1px;
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              width: 5px;
              height: 4px;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });
    });

    describe('supports math.abs', function () {
        it('handles positive values', function () {
            $scss = <<<'SCSS'
            @use "sass:math";

            .test {
                width: math.abs(5) * 1px;
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              width: 5px;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('handles negative values', function () {
            $scss = <<<'SCSS'
            @use "sass:math";

            .test {
                width: math.abs(-20) * 1px;
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              width: 20px;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('handles zero and unitless values', function () {
            $scss = <<<'SCSS'
            @use "sass:math";

            .test {
                width: math.abs(0) * 1px;
                height: math.abs(5) * 2px;
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              width: 0;
              height: 10px;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });
    });

    describe('supports math.pow', function () {
        it('calculates power of numbers', function () {
            $scss = <<<'SCSS'
            @use "sass:math";

            .test {
                width: math.pow(2, 3) * 1px;
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              width: 8px;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('calculates power with decimal numbers', function () {
            $scss = <<<'SCSS'
            @use "sass:math";

            .test {
                width: round(math.pow(2.5, 2) * 100) * 0.01px;
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              width: 6.25px;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });
    });

    describe('supports math.sqrt', function () {
        it('calculates square root of perfect squares', function () {
            $scss = <<<'SCSS'
            @use "sass:math";

            .test {
                width: math.sqrt(16) * 1px;
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              width: 4px;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('calculates square root of decimal numbers', function () {
            $scss = <<<'SCSS'
            @use "sass:math";

            .test {
                width: round(math.sqrt(2) * 100) * 0.01px;
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              width: 1.41px;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });
    });

    describe('supports trigonometric functions', function () {
        it('calculates cosine of zero', function () {
            $scss = <<<'SCSS'
            @use "sass:math";

            .test {
                width: round(math.cos(0) * 100) * 0.01px;
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              width: 1px;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('calculates sine of zero', function () {
            $scss = <<<'SCSS'
            @use "sass:math";

            .test {
                width: round(math.sin(10) * 100) * 0.01px;
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              width: -0.54px;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('calculates tangent of zero', function () {
            $scss = <<<'SCSS'
            @use "sass:math";

            .test {
                width: round(math.tan(0) * 100) * 0.01px;
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              width: 0;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });
    });

    describe('supports inverse trigonometric functions', function () {
        it('calculates arc cosine', function () {
            $scss = <<<'SCSS'
            @use "sass:math";

            .test {
                angle: math.acos(0);
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              angle: 90deg;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('calculates arc sine', function () {
            $scss = <<<'SCSS'
            @use "sass:math";

            .test {
                angle: math.asin(0);
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              angle: 0deg;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('calculates arc tangent', function () {
            $scss = <<<'SCSS'
            @use "sass:math";

            .test {
                angle: math.atan(1);
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              angle: 45deg;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('calculates arc tangent of two variables', function () {
            $scss = <<<'SCSS'
            @use "sass:math";

            .test {
                angle: math.atan2(1, 1);
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              angle: 45deg;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });
    });

    describe('supports math.compatible', function () {
        it('checks if units are compatible', function () {
            $scss = <<<'SCSS'
            @use "sass:math";

            .test {
                compatible-px-em: math.compatible(1px, 1em);
                compatible-px-px: math.compatible(1px, 2px);
                compatible-unitless-px: math.compatible(1, 1px);
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              compatible-px-em: false;
              compatible-px-px: true;
              compatible-unitless-px: true;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });
    });

    describe('supports math.is-unitless', function () {
        it('checks if value is unitless', function () {
            $scss = <<<'SCSS'
            @use "sass:math";

            .test {
                unitless-number: math.is-unitless(10);
                unitless-with-unit: math.is-unitless(10px);
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              unitless-number: true;
              unitless-with-unit: false;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });
    });

    describe('supports math.unit', function () {
        it('returns unit of a value', function () {
            $scss = <<<'SCSS'
            @use "sass:math";

            .test {
                unit-of-px: math.unit(10px);
                unit-of-unitless: math.unit(10);
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              unit-of-px: "px";
              unit-of-unitless: "";
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });
    });

    describe('supports math.div', function () {
        it('divides two numbers', function () {
            $scss = <<<'SCSS'
            @use "sass:math";

            .test {
                simple-div: math.div(10, 2);
                div-with-units: math.div(10px, 2);
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              simple-div: 5;
              div-with-units: 5px;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });
    });

    describe('supports math.percentage', function () {
        it('converts unitless number to percentage', function () {
            $scss = <<<'SCSS'
            @use "sass:math";

            .test {
                percentage-value: math.percentage(0.5);
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              percentage-value: 50%;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });
    });

    describe('supports math.random', function () {
        it('generates random number', function () {
            $scss = <<<'SCSS'
            @use "sass:math";

            .test {
                has-random: if(math.random() > 0, true, false);
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              has-random: true;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });
    });

    describe('supports math.calc', function () {
        it('creates calc expression', function () {
            $scss = <<<'SCSS'
            @use "sass:math";

            .test {
                calc-expression: math.calc(10px + 5px);
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            .test {
              calc-expression: 15px;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });
    });
});
