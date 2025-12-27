<?php

declare(strict_types=1);

use DartSass\Compiler;

beforeEach(function () {
    $this->compiler = new Compiler();
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
