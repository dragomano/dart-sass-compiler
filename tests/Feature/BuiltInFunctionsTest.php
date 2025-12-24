<?php

declare(strict_types=1);

use DartSass\Compiler;

beforeEach(function () {
    $this->compiler = new Compiler();
});

it('supports calc function', function () {
    $scss = <<<'SCSS'
    $width: 100px;
    $min-padding: min(10px, 2vw);

    body {
        width: calc(#{$width} + 20px);
        height: calc(100% * 0.5);
    }
    div {
        width: calc($min-padding * 2);
        height: calc(20px);
    }
    SCSS;

    $expected = <<<'CSS'
    body {
      width: 120px;
      height: 50%;
    }
    div {
      width: calc(min(10px, 2vw) * 2);
      height: 20px;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('supports clamp function', function () {
    $scss = <<<'SCSS'
    body {
        width: clamp(100px, 50%, 500px);
    }
    SCSS;

    $expected = <<<'CSS'
    body {
      width: clamp(100px, 50%, 500px);
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);

    $scss = <<<'SCSS'
    body {
        width: clamp(100px, 500px, 500px);
    }
    SCSS;

    $expected = <<<'CSS'
    body {
      width: 500px;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

describe('supports max/min functions', function () {
    it('supports max function', function () {
        $scss = <<<'SCSS'
        body {
            width: max(100px, 200px, 50%);
        }
        SCSS;

        $expected = <<<'CSS'
        body {
          width: max(100px, 200px, 50%);
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);

        $scss = <<<'SCSS'
        body {
            width: max(100px, 200px);
        }
        SCSS;

        $expected = <<<'CSS'
        body {
          width: 200px;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('supports min function', function () {
        $scss = <<<'SCSS'
        body {
            width: min(100px, 200px, 50%);
        }
        SCSS;

        $expected = <<<'CSS'
        body {
          width: min(100px, 200px, 50%);
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);

        $scss = <<<'SCSS'
        body {
            width: min(100px, 200px);
        }
        SCSS;

        $expected = <<<'CSS'
        body {
          width: 100px;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });
});

it('handles math constants', function () {
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
      height: 1.0E-12px;
      top: 3.1415926535898px;
      right: 1.0E+305px;
      bottom: -1.0E+305px;
      left: 0;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('handles complex math expressions', function () {
    $scss = <<<'SCSS'
    @use "sass:math";

    $border-radius: 5px;
    $min-padding: min(10px, 2vw);

    .class-0 {
        border-radius: calc($border-radius + 2px);
        padding: max(8px, $min-padding) max(15px, calc($min-padding * 2));
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    .class-0 {
      border-radius: 7px;
      padding: max(8px, min(10px, 2vw)) max(15px, calc(min(10px, 2vw) * 2));
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

describe('supports math.hypot function', function () {
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

describe('supports math.log function', function () {
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

describe('supports math.ceil function', function () {
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

describe('supports math.floor function', function () {
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

describe('supports math.round function', function () {
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

describe('supports math.abs function', function () {
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

describe('supports math.pow function', function () {
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

describe('supports math.sqrt function', function () {
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

describe('supports math.trigonometric functions', function () {
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
            width: round(math.sin(0) * 100) * 0.01px;
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .test {
          width: 0;
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

describe('supports math.inverse trigonometric functions', function () {
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
            angle: math.atan(0);
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .test {
          angle: 0deg;
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

describe('supports math.compatible function', function () {
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

describe('supports math.is-unitless function', function () {
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

describe('supports math.unit function', function () {
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

describe('supports math.div function', function () {
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

describe('supports math.percentage function', function () {
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

describe('supports math.random function', function () {
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

describe('supports math.calc function', function () {
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

it('handles zero values in math functions', function () {
    $scss = <<<'SCSS'
    $zero: abs(0px);

    selector {
        padding: max($zero, min(10px, 2vw));
        opacity: max($zero, min(1, 0.5));
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    selector {
      padding: max(0px, min(10px, 2vw));
      opacity: .5;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('handles unary minus operations', function () {
    $scss = <<<'SCSS'
    $spacer: 20px;
    $offset: 5px;

    .element {
        margin-top: -$spacer;
        margin-bottom: -($spacer + 10px);
        left: 100px + -$offset;
        z-index: -1;
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    .element {
      margin-top: -20px;
      margin-bottom: -30px;
      left: 95px;
      z-index: -1;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

describe('supports color.adjust function', function () {
    it('adjusts RGB components', function () {
        $scss = <<<'SCSS'
        @use 'sass:color';

        $color: #6b717f;
        body {
            background: color.adjust($color, $red: 15);
        }
        SCSS;

        $expected = <<<'CSS'
        body {
          background: #7a717f;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);

        $scss = <<<'SCSS'
        @use 'sass:color';

        $color: #ff0000;

        body {
            background: color.adjust($color, $blue: 50);
        }
        SCSS;

        $expected = <<<'CSS'
        body {
          background: #ff0032;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);

        $scss = <<<'SCSS'
        @use 'sass:color';

        $color: #ff8800;

        body {
            background: color.adjust($color, $green: 100);
        }
        SCSS;

        $expected = <<<'CSS'
        body {
          background: #ffec00;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('adjusts HSL components', function () {
        $scss = <<<'SCSS'
        @use 'sass:color';

        $original-color: #ff8800;
        $adjusted-color: color.adjust($original-color, $hue: 60deg);

        div {
            background-color: $adjusted-color;
        }
        SCSS;

        $expected = <<<'CSS'
        div {
          background-color: #77ff00;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);

        $scss = <<<'SCSS'
        @use 'sass:color';

        $color: #cc6666;

        body {
            background: color.adjust($color, $saturation: 20%);
        }
        SCSS;

        $expected = <<<'CSS'
        body {
          background: #e05252;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('adjusts lightness, alpha, and HWB', function () {
        $scss = <<<'SCSS'
        @use 'sass:color';

        $color: #ff0000;

        body {
            background: color.adjust($color, $lightness: 20%);
        }
        SCSS;

        $expected = <<<'CSS'
        body {
          background: #ff6666;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);

        $scss = <<<'SCSS'
        @use 'sass:color';

        $color: rgba(255, 0, 0, 0.5);

        body {
            background: color.adjust($color, $alpha: 0.3);
        }
        SCSS;

        $expected = <<<'CSS'
        body {
          background: #ff0000cc;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);

        $scss = <<<'SCSS'
        @use 'sass:color';

        $color: #ff0000;

        body {
            background: color.adjust($color, $whiteness: 20%, $space: hwb);
        }
        SCSS;

        $expected = <<<'CSS'
        body {
          background: #ff3333;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('handles edge cases', function () {
        $scss = <<<'SCSS'
        @use 'sass:color';

        $color: #0000ff;

        body {
            background: color.adjust($color, $blue: 50);
        }
        SCSS;

        $expected = <<<'CSS'
        body {
          background: blue;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);

        $scss = <<<'SCSS'
        @use 'sass:color';

        $color: #ff0000;

        body {
            background: color.adjust($color, $saturation: -50%);
        }
        SCSS;

        $expected = <<<'CSS'
        body {
          background: #bf4040;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);

        $scss = <<<'SCSS'
        @use 'sass:color';

        $color: hsl(0, 100%, 50%);

        body {
            background: color.adjust($color, $hue: 120deg);
        }
        SCSS;

        $expected = <<<'CSS'
        body {
          background: lime;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });
});

it('supports color.change function', function () {
    $scss = <<<'SCSS'
    @use 'sass:color';

    $color: #ff0000;
    body {
        background: color.change($color, $lightness: 50%);
    }
    SCSS;

    $expected = <<<'CSS'
    body {
      background: red;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);

    $scss = <<<'SCSS'
    @use 'sass:color';

    $color: #ff0000;
    body {
        background: color.change($color, $hue: 120deg);
    }
    SCSS;

    $expected = <<<'CSS'
    body {
      background: lime;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);

    $scss = <<<'SCSS'
    @use 'sass:color';

    $color: #ff0000;
    body {
        background: color.change($color, $alpha: 0.5);
    }
    SCSS;

    $expected = <<<'CSS'
    body {
      background: #ff000080;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('supports color.channel function', function () {
    $scss = <<<'SCSS'
    @use 'sass:color';

    div {
        red: color.channel(red, "red");
        green: color.channel(red, "green");
        blue: color.channel(red, "blue");
        alpha: color.channel(red, "alpha");
        hue: color.channel(hsl(120, 100%, 50%), "hue");
        saturation: color.channel(hsl(120, 100%, 50%), "saturation");
        lightness: color.channel(hsl(120, 100%, 50%), "lightness");
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    div {
      red: 255;
      green: 0;
      blue: 0;
      alpha: 1;
      hue: 120deg;
      saturation: 100%;
      lightness: 50%;
    }
    CSS;

    expect($this->compiler->compileString($scss))->toEqualCss($expected);
});

it('supports color.complement function', function () {
    $scss = <<<'SCSS'
    @use 'sass:color';

    div {
        complement-red: color.complement(red);
        complement-green: color.complement(lime);
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    div {
      complement-red: cyan;
      complement-green: magenta;
    }
    CSS;

    expect($this->compiler->compileString($scss))->toEqualCss($expected);
});

it('supports color.grayscale function', function () {
    $scss = <<<'SCSS'
    @use 'sass:color';

    div {
        grayscale-red: color.grayscale(red);
        grayscale-blue: color.grayscale(blue);
        grayscale-green: color.grayscale(lime);
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    div {
      grayscale-red: grey;
      grayscale-blue: grey;
      grayscale-green: grey;
    }
    CSS;

    expect($this->compiler->compileString($scss))->toEqualCss($expected);
});

it('supports color.is-hex-str function', function () {
    $scss = <<<'SCSS'
    @use 'sass:color';

    div {
        ie-hex-str-red: color.ie-hex-str(red);
        ie-hex-str-blue: color.ie-hex-str(blue);
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    div {
      ie-hex-str-red: #FFFF0000;
      ie-hex-str-blue: #FF0000FF;
    }
    CSS;

    expect($this->compiler->compileString($scss))->toEqualCss($expected);
});

it('supports color.invert function', function () {
    $scss = <<<'SCSS'
    @use 'sass:color';

    div {
        invert-red: color.invert(red, 100%);
        invert-white: color.invert(white, 100%);
        invert-black: color.invert(black, 100%);
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    div {
      invert-red: cyan;
      invert-white: black;
      invert-black: white;
    }
    CSS;

    expect($this->compiler->compileString($scss))->toEqualCss($expected);
});

it('supports color.is-legacy function', function () {
    $scss = <<<'SCSS'
    @use 'sass:color';

    div {
        is-legacy-red: color.is-legacy(red);
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    div {
      is-legacy-red: true;
    }
    CSS;

    expect($this->compiler->compileString($scss))->toEqualCss($expected);
});

it('supports color.is-missing function', function () {
    $scss = <<<'SCSS'
    @use 'sass:color';

    div {
        is-missing-red-red: color.is-missing(red, "red");
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    div {
      is-missing-red-red: false;
    }
    CSS;

    expect($this->compiler->compileString($scss))->toEqualCss($expected);
});

it('supports color.is-powerless function', function () {
    $scss = <<<'SCSS'
    @use 'sass:color';

    div {
        is-powerless-gray-saturation: color.is-powerless(hsl(180deg 0% 40%), "hue");
        is-powerless-red-saturation: color.is-powerless(hsl(180deg 0% 40%), "saturation");
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    div {
      is-powerless-gray-saturation: true;
      is-powerless-red-saturation: false;
    }
    CSS;

    expect($this->compiler->compileString($scss))->toEqualCss($expected);
});

it('supports color.mix function', function () {
    $scss = <<<'SCSS'
    @use 'sass:color';

    body {
        background: color.mix(#ff0000, #0000ff, 0.5);
    }
    SCSS;

    $expected = <<<'CSS'
    body {
      background: purple;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('supports color.same function', function () {
    $scss = <<<'SCSS'
    @use 'sass:color';

    div {
        same-red-red: color.same(red, red);
        same-red-blue: color.same(red, blue);
        same-red-ff0000: color.same(red, #ff0000);
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    div {
      same-red-red: true;
      same-red-blue: false;
      same-red-ff0000: true;
    }
    CSS;

    expect($this->compiler->compileString($scss))->toEqualCss($expected);
});

it('supports color.scale function', function () {
    $scss = <<<'SCSS'
    @use 'sass:color';

    $color: #ff0000;
    body {
        background: color.scale($color, $lightness: 20%);
    }
    SCSS;

    $expected = <<<'CSS'
    body {
      background: #ff3333;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);

    $scss = <<<'SCSS'
    @use 'sass:color';

    $color: #ff0000;
    body {
        background: color.scale($color, $saturation: -50%);
    }
    SCSS;

    $expected = <<<'CSS'
    body {
      background: #bf4040;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);

    $scss = <<<'SCSS'
    @use 'sass:color';

    $color: #ff0000;
    body {
        background: color.scale($color, $red: 20%);
    }
    SCSS;

    $expected = <<<'CSS'
    body {
      background: red;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('supports color.space function', function () {
    $scss = <<<'SCSS'
    @use 'sass:color';

    div {
        space-red: color.space(red);
        space-hsl: color.space(hsl(0, 100%, 50%));
        space-hwb: color.space(color.hwb(0, 0%, 0%));
        space-lch: color.space(lch(60% 40 30deg));
        space-oklch: color.space(oklch(1% 20 40 / 0.5));
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    div {
      space-red: rgb;
      space-hsl: hsl;
      space-hwb: hwb;
      space-lch: lch;
      space-oklch: oklch;
    }
    CSS;

    expect($this->compiler->compileString($scss))->toEqualCss($expected);
});

it('supports color.to-gamut function', function () {
    $scss = <<<'SCSS'
    @use 'sass:color';

    div {
        to-gamut-red: color.to-gamut(red, $method: clip);
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    div {
      to-gamut-red: red;
    }
    CSS;

    expect($this->compiler->compileString($scss))->toEqualCss($expected);
});

it('supports color.to-space function', function () {
    $scss = <<<'SCSS'
    @use 'sass:color';

    div {
        to-space-red-to-hsl: color.to-space(red, hsl);
        to-space-hsl-to-rgb: color.to-space(hsl(0, 100%, 50%), rgb);
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    div {
      to-space-red-to-hsl: hsl(0, 100%, 50%);
      to-space-hsl-to-rgb: red;
    }
    CSS;

    expect($this->compiler->compileString($scss))->toEqualCss($expected);
});

describe('supports deprecated color functions', function () {
    it('supports adjust-hue function', function () {
        $scss = <<<'SCSS'
        div {
            adjusted-hue: adjust-hue(red, 180deg);
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        div {
          adjusted-hue: cyan;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('supports color.alpha function', function () {
        $scss = <<<'SCSS'
        @use 'sass:color';

        div {
            alpha-red: color.alpha(red);
            alpha-rgba: color.alpha(rgba(255, 0, 0, 0.5));
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        div {
          alpha-red: 1;
          alpha-rgba: .5;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('supports alpha function', function () {
        $scss = <<<'SCSS'
        div {
            alpha-red: alpha(red);
            alpha-rgba: alpha(rgba(255, 0, 0, 0.5));
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        div {
          alpha-red: 1;
          alpha-rgba: .5;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('supports opacity function', function () {
        $scss = <<<'SCSS'
        div {
            opacity-red: opacity(red);
            opacity-rgba: opacity(rgba(255, 0, 0, 0.5));
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        div {
          opacity-red: 1;
          opacity-rgba: .5;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('supports color.blackness function', function () {
        $scss = <<<'SCSS'
        @use 'sass:color';

        div {
            blackness-hwb: color.blackness(hwb(0, 0%, 50%));
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        div {
          blackness-hwb: 50%;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('supports blackness function', function () {
        $scss = <<<'SCSS'
        div {
            blackness-hwb: blackness(hwb(0, 0%, 50%));
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        div {
          blackness-hwb: 50%;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('supports color.red function', function () {
        $scss = <<<'SCSS'
        @use 'sass:color';

        div {
            red-value: color.red(red);
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        div {
          red-value: 255;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('supports red function', function () {
        $scss = <<<'SCSS'
        div {
            red-value: red(red);
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        div {
          red-value: 255;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('supports color.green function', function () {
        $scss = <<<'SCSS'
        @use 'sass:color';

        div {
            green-value: color.green(red);
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        div {
          green-value: 0;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('supports green function', function () {
        $scss = <<<'SCSS'
        div {
            green-value: green(red);
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        div {
          green-value: 0;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('supports color.blue function', function () {
        $scss = <<<'SCSS'
        @use 'sass:color';

        div {
            blue-value: color.blue(red);
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        div {
          blue-value: 0;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('supports blue function', function () {
        $scss = <<<'SCSS'
        div {
            blue-value: blue(red);
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        div {
          blue-value: 0;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('supports color.hue function', function () {
        $scss = <<<'SCSS'
        @use 'sass:color';

        div {
            hue-value: color.hue(hsl(120, 100%, 50%));
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        div {
          hue-value: 120deg;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('supports hue function', function () {
        $scss = <<<'SCSS'
        div {
            hue-value: hue(hsl(120, 100%, 50%));
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        div {
          hue-value: 120deg;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('supports color.lightness function', function () {
        $scss = <<<'SCSS'
        @use 'sass:color';

        div {
            lightness-value: color.lightness(hsl(0, 100%, 75%));
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        div {
          lightness-value: 75%;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('supports lightness function', function () {
        $scss = <<<'SCSS'
        div {
            lightness-value: lightness(hsl(0, 100%, 75%));
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        div {
          lightness-value: 75%;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('supports color.whiteness function', function () {
        $scss = <<<'SCSS'
        @use 'sass:color';

        div {
            whiteness-hwb: color.whiteness(hwb(0, 50%, 0%));
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        div {
          whiteness-hwb: 50%;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('supports whiteness function', function () {
        $scss = <<<'SCSS'
        div {
            whiteness-hwb: whiteness(hwb(0, 50%, 0%));
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        div {
          whiteness-hwb: 50%;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('supports opacify function', function () {
        $scss = <<<'SCSS'
        body {
            background: opacify(rgba(255, 0, 0, 0.5), 0.3);
        }
        SCSS;

        $expected = <<<'CSS'
        body {
          background: #ff0000cc;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('supports fade-in function', function () {
        $scss = <<<'SCSS'
        div {
            fade-in: fade-in(rgba(255, 0, 0, 0.5), 0.3);
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        div {
          fade-in: #ff0000cc;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('supports transparentize function', function () {
        $scss = <<<'SCSS'
        body {
            background: transparentize(rgba(255, 0, 0, 0.8), 0.3);
        }
        SCSS;

        $expected = <<<'CSS'
        body {
          background: #ff000080;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('supports fade-out function', function () {
        $scss = <<<'SCSS'
        div {
            fade-out: fade-out(rgba(255, 0, 0, 0.8), 0.3);
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        div {
          fade-out: #ff000080;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('supports color.saturation function', function () {
        $scss = <<<'SCSS'
        @use 'sass:color';

        div {
            saturation-value: color.saturation(hsl(0, 75%, 50%));
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        div {
          saturation-value: 75%;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('supports saturation function', function () {
        $scss = <<<'SCSS'
        div {
            saturation-value: saturation(hsl(0, 75%, 50%));
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        div {
          saturation-value: 75%;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('supports lighten function', function () {
        $scss = <<<'SCSS'
        body {
            background: lighten(#ff0000, 20%);
        }
        SCSS;

        $expected = <<<'CSS'
        body {
          background: #ff6666;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('supports darken function', function () {
        $scss = <<<'SCSS'
        body {
            background: darken(#ff6666, 20%);
        }
        SCSS;

        $expected = <<<'CSS'
        body {
          background: red;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('supports saturate function', function () {
        $scss = <<<'SCSS'
        body {
            background: saturate(#cc6666, 20%);
        }
        SCSS;

        $expected = <<<'CSS'
        body {
          background: #e05252;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('supports desaturate function', function () {
        $scss = <<<'SCSS'
        body {
            background: desaturate(#ff0000, 50%);
        }
        SCSS;

        $expected = <<<'CSS'
        body {
          background: #bf4040;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });
});

it('supports hsl function', function () {
    $scss = <<<'SCSS'
    body {
        background: hsl(0deg, 100%, 50%);
    }
    SCSS;

    $expected = <<<'CSS'
    body {
      background: hsl(0, 100%, 50%);
    }
    CSS;

    expect($this->compiler->compileString($scss))->toEqualCss($expected);

    $scss = <<<'SCSS'
    body {
        background: hsl(120deg, 100%, 50%);
    }
    SCSS;

    $expected = <<<'CSS'
    body {
      background: hsl(120, 100%, 50%);
    }
    CSS;

    expect($this->compiler->compileString($scss))->toEqualCss($expected);
});

it('supports color.hwb function', function () {
    $scss = <<<'SCSS'
    @use 'sass:color';

    body {
        background: color.hwb(0, 0%, 0%);
    }
    SCSS;

    $expected = <<<'CSS'
    body {
      background: hwb(0 0% 0%);
    }
    CSS;

    expect($this->compiler->compileString($scss))->toEqualCss($expected);

    $scss = <<<'SCSS'
    @use 'sass:color';

    body {
        background: color.hwb(0, 100%, 0%);
    }
    SCSS;

    $expected = <<<'CSS'
    body {
      background: hwb(0 100% 0%);
    }
    CSS;

    expect($this->compiler->compileString($scss))->toEqualCss($expected);
});

it('supports lch function', function () {
    $scss = <<<'SCSS'
    body {
        background: lch(60% 40 30deg);
    }
    SCSS;

    $expected = <<<'CSS'
    body {
      background: lch(60% 40 30);
    }
    CSS;

    expect($this->compiler->compileString($scss))->toEqualCss($expected);

    $scss = <<<'SCSS'
    body {
        background: lch(40% 60 240deg / 0.5);
    }
    SCSS;

    $expected = <<<'CSS'
    body {
      background: lch(40% 60 240 / 0.5);
    }
    CSS;

    expect($this->compiler->compileString($scss))->toEqualCss($expected);
});

it('supports oklch function', function () {
    $scss = <<<'SCSS'
    body {
        background: oklch(0.6 0.3 90deg);
    }
    SCSS;

    $expected = <<<'CSS'
    body {
      background: oklch(60% 0.3 90);
    }
    CSS;

    expect($this->compiler->compileString($scss))->toEqualCss($expected);

    $scss = <<<'SCSS'
    body {
        background: oklch(0.2 0.4 270deg / 0.9);
    }
    SCSS;

    $expected = <<<'CSS'
    body {
      background: oklch(20% 0.4 270 / 0.9);
    }
    CSS;

    expect($this->compiler->compileString($scss))->toEqualCss($expected);
});

it('preserves vendor prefixes in output', function () {
    $scss = <<<'SCSS'
    p {
        margin-bottom: 5px;
        overflow: hidden;
        display: -webkit-box;
        line-clamp: 3;
        line-height: 1.4;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
    }
    SCSS;

    $expected = <<<'CSS'
    p {
      margin-bottom: 5px;
      overflow: hidden;
      display: -webkit-box;
      line-clamp: 3;
      line-height: 1.4;
      -webkit-line-clamp: 3;
      -webkit-box-orient: vertical;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});
