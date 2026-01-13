<?php

declare(strict_types=1);

use DartSass\Compiler;

beforeEach(function () {
    $this->compiler = new Compiler();
});

it('supports spread operator (arbitrary arguments)', function () {
    $scss = <<<'SCSS'
    $widths: 50px, 30px, 100px;
    .micro {
      width: min($widths...);
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    .micro {
      width: 30px;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});
