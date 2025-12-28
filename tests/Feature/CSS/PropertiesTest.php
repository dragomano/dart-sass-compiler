<?php

declare(strict_types=1);

use DartSass\Compiler;

beforeEach(function () {
    $this->compiler = new Compiler();
});

it('preserves CSS order property', function () {
    $scss = <<<'SCSS'
    .card-header {
        overflow: hidden;
        z-index: 0;
        position: relative;
        order: 1;
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    .card-header {
      overflow: hidden;
      z-index: 0;
      position: relative;
      order: 1;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('preserves important declarations', function () {
    $scss = <<<'SCSS'
    .article {
        grid-column: span 1 !important;
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    .article {
      grid-column: span 1 !important;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});
