<?php

declare(strict_types=1);

use DartSass\Compiler;

beforeEach(function () {
    $this->compiler = new Compiler();
});

it('compiles linear gradient function', function () {
    $scss = <<<'SCSS'
    .card-title {
        background-image: linear-gradient(to bottom, transparent, transparent, #111827);
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    .card-title {
      background-image: linear-gradient(to bottom, transparent, transparent, #111827);
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});
