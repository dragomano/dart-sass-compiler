<?php

declare(strict_types=1);

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
    $this->compiler->addFunction('triple', fn($value) => $value * 3);

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

it('handles function with url()', function () {
    $scss = <<<'SCSS'
    $image-path: '../images';

    @function asset-url($filename) {
        @return url('#{$image-path}/#{$filename}');
    }

    .dynamic {
        background: asset-url('background.jpg');
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    .dynamic {
      background: url("../images/background.jpg");
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});
