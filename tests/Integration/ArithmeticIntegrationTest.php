<?php

declare(strict_types=1);

use DartSass\Compiler;

beforeEach(function () {
    $this->compiler = new Compiler();
});

it('handles complex math expressions', function () {
    $scss = <<<'SCSS'
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
