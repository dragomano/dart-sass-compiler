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

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);

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

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
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

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);

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

it('supports abs function', function () {
    $scss = <<<'SCSS'
    body {
        width: abs(10px);
    }
    SCSS;

    $expected = <<<'CSS'
    body {
      width: 10px;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);

    $scss = <<<'SCSS'
    body {
        width: abs(-20px);
    }
    SCSS;

    $expected = <<<'CSS'
    body {
      width: 20px;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);

    $scss = <<<'SCSS'
    body {
        width: abs(-10em);
    }
    SCSS;

    $expected = <<<'CSS'
    body {
      width: 10em;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);

    $scss = <<<'SCSS'
    body {
        width: abs(0px);
    }
    SCSS;

    $expected = <<<'CSS'
    body {
      width: 0;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);

    $scss = <<<'SCSS'
    body {
        width: abs(5px);
    }
    SCSS;

    $expected = <<<'CSS'
    body {
      width: 5px;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);

    $scss = <<<'SCSS'
    body {
        width: abs(-15px);
    }
    SCSS;

    $expected = <<<'CSS'
    body {
      width: 15px;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('supports color.adjust function', function () {
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

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);

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

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);

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

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);

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

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);

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

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);

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

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);

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

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);

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

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);

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

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);

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

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);

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

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
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

it('supports color.lighten function', function () {
    $scss = <<<'SCSS'
    @use 'sass:color';

    body {
        background: color.lighten(#ff0000, 20%);
    }
    SCSS;

    $expected = <<<'CSS'
    body {
      background: #ff6666;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('supports color.opacify function', function () {
    $scss = <<<'SCSS'
    @use 'sass:color';

    body {
        background: color.opacify(rgba(255, 0, 0, 0.5), 0.3);
    }
    SCSS;

    $expected = <<<'CSS'
    body {
      background: #ff0000cc;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});


it('supports color.darken function', function () {
    $scss = <<<'SCSS'
    @use 'sass:color';

    body {
        background: color.darken(#ff6666, 20%);
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

it('supports color.saturate function', function () {
    $scss = <<<'SCSS'
    @use 'sass:color';

    body {
        background: color.saturate(#cc6666, 20%);
    }
    SCSS;

    $expected = <<<'CSS'
    body {
      background: #e05252;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('supports color.desaturate function', function () {
    $scss = <<<'SCSS'
    @use 'sass:color';

    body {
        background: color.desaturate(#ff0000, 50%);
    }
    SCSS;

    $expected = <<<'CSS'
    body {
      background: #bf4040;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('supports color.transparentize function', function () {
    $scss = <<<'SCSS'
    @use 'sass:color';

    body {
        background: color.transparentize(rgba(255, 0, 0, 0.8), 0.3);
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

it('supports color.hsl function', function () {
    $scss = <<<'SCSS'
    @use 'sass:color';

    body {
        background: color.hsl(0deg, 100%, 50%);
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

    body {
        background: color.hsl(120deg, 100%, 50%);
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

    body {
        background: color.hsl(0deg, 100%, 50%, 0.5);
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

it('supports color.hwb function', function () {
    $scss = <<<'SCSS'
    @use 'sass:color';

    body {
        background: color.hwb(0deg, 0%, 0%);
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

    body {
        background: color.hwb(0deg, 100%, 0%);
    }
    SCSS;

    $expected = <<<'CSS'
    body {
      background: white;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);

    $scss = <<<'SCSS'
    @use 'sass:color';

    body {
        background: color.hwb(0deg, 0%, 100%);
    }
    SCSS;

    $expected = <<<'CSS'
    body {
      background: black;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('preserves vendor prefixes in output', function () {
    $scss = <<<'SCSS'
    p {
        margin-bottom: 5px;
        overflow: hidden;
        display: -webkit-box;
        line-clamp: 3;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        line-height: 1.4;
    }
    SCSS;

    $expected = <<<'CSS'
    p {
      margin-bottom: 5px;
      overflow: hidden;
      display: -webkit-box;
      line-clamp: 3;
      -webkit-line-clamp: 3;
      -webkit-box-orient: vertical;
      line-height: 1.4;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
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
