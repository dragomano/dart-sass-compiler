<?php declare(strict_types=1);

use DartSass\Compiler;

beforeEach(function () {
    $this->compiler = new Compiler();
});

it('allows custom function definition with @return', function () {
    $scss = <<<'SCSS'
    @function double($n) {
        @return $n * 2;
    }
    body {
        width: double(10px);
    }
    SCSS;

    $expected = <<<'CSS'
    body {
      width: 20px;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('allows adding custom PHP function', function () {
    $this->compiler->addFunction('triple', fn ($value) => $value * 3);

    $scss = <<<'SCSS'
    body {
        width: triple(10px);
    }
    SCSS;

    $expected = <<<'CSS'
    body {
      width: 30px;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});
