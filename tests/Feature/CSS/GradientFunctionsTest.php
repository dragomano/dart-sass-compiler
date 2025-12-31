<?php

declare(strict_types=1);

use DartSass\Compiler;

beforeEach(function () {
    $this->compiler = new Compiler();
});

it('compiles linear gradient function', function () {
    $scss = <<<'SCSS'
    .card-title {
        background-image: linear-gradient(to bottom, transparent, red, #111827);
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    .card-title {
      background-image: linear-gradient(to bottom, transparent, red, #111827);
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('compiles linear gradient with multiple effects', function () {
    $scss = <<<'SCSS'
    .multiple-effects {
        background:
            linear-gradient(45deg, transparent 30%, #000),
            url('texture.png') repeat,
            url('base.jpg') no-repeat center / cover;
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    .multiple-effects {
      background: linear-gradient(45deg, transparent 30%, #000), url("texture.png") repeat, url("base.jpg") no-repeat center / cover;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('compiles linear gradient with angles', function () {
    $scss = <<<'SCSS'
    .angles {
        background-1: linear-gradient(45deg, blue, red);
        background-2: linear-gradient(0.25turn, blue, red);
        background-3: linear-gradient(1.57rad, blue, red);
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    .angles {
      background-1: linear-gradient(45deg, blue, red);
      background-2: linear-gradient(.25turn, blue, red);
      background-3: linear-gradient(1.57rad, blue, red);
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('compiles linear gradient with corner directions', function () {
    $scss = <<<'SCSS'
    .corners {
        background-1: linear-gradient(to top left, #333, #fff);
        background-2: linear-gradient(to bottom right, #000, #ccc);
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    .corners {
      background-1: linear-gradient(to top left, #333, #fff);
      background-2: linear-gradient(to bottom right, #000, #ccc);
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('compiles linear gradient with complex color stops', function () {
    $scss = <<<'SCSS'
    .stops {
        background-1: linear-gradient(to right, red 20%, blue 80%);
        background-2: linear-gradient(to bottom, red 10px, blue 90%);
        background-3: linear-gradient(90deg, red 50%, blue 50%);
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    .stops {
      background-1: linear-gradient(to right, red 20%, blue 80%);
      background-2: linear-gradient(to bottom, red 10px, blue 90%);
      background-3: linear-gradient(90deg, red 50%, blue 50%);
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('compiles linear gradient with variables interpolation', function () {
    $scss = <<<'SCSS'
    $start: #ff0000;
    $end: #0000ff;
    $angle: 180deg;

    .interpolated {
        background: linear-gradient($angle, $start, $end);
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    .interpolated {
      background: linear-gradient(180deg, #ff0000, #0000ff);
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});
