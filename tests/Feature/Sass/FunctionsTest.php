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

it('handles function with local variables', function () {
    $scss = <<<'SCSS'
    @function calculate($n) {
        $result: $n * 2;
        @return $result + 10;
    }

    body {
        width: calculate(5px);
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

it('handles complex function with list methods', function () {
    $scss = <<<'SCSS'
    @function fibonacci($n) {
      $sequence: 0 1;
      @for $_ from 1 through $n {
        $new: nth($sequence, length($sequence)) + nth($sequence, length($sequence) - 1);
        $sequence: append($sequence, $new);
      }
      @return nth($sequence, length($sequence));
    }

    .sidebar {
      float: left;
      margin-left: fibonacci(4) * 1px;
    }

    .test {
      content: fibonacci(4);
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    .sidebar {
      float: left;
      margin-left: 5px;
    }
    .test {
      content: 5;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('handles custom function with arbitrary arguments', function () {
    $scss = <<<'SCSS'
    @function sum($numbers...) {
      $sum: 0;
      @each $number in $numbers {
        $sum: $sum + $number;
      }
      @return $sum;
    }

    .micro {
      width: sum(50px, 30px, 100px);
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    .micro {
      width: 180px;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});
