<?php

declare(strict_types=1);

use DartSass\Compiler;

beforeEach(function () {
    $this->compiler = new Compiler();
});

it('handles basic @supports', function () {
    $scss = '@supports (animation-name: test) { foo {a: b} }';

    $expected = /** @lang text */ <<<'CSS'
    @supports (animation-name: test) {
      foo {
        a: b;
      }
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('handles @supports with transform-origin', function () {
    $scss = '@supports (transform-origin: 5% 5%) { foo {a: b} }';

    $expected = /** @lang text */ <<<'CSS'
    @supports (transform-origin: 5% 5%) {
      foo {
        a: b;
      }
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('handles @supports with selector', function () {
    $scss = '@supports selector(A > B) { foo {a: b} }';

    $expected = /** @lang text */ <<<'CSS'
    @supports selector(A > B) {
      foo {
        a: b;
      }
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('handles @supports with not', function () {
    $scss = '@supports not (transform-origin: 10em 10em 10em) { foo {a: b} }';

    $expected = /** @lang text */ <<<'CSS'
    @supports not (transform-origin: 10em 10em 10em) {
      foo {
        a: b;
      }
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('handles @supports with double not', function () {
    $scss = '@supports not (not (transform-origin: 2px)) { foo {a: b} }';

    $expected = /** @lang text */ <<<'CSS'
    @supports not (not (transform-origin: 2px)) {
      foo {
        a: b;
      }
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('handles @supports with and', function () {
    $scss = '@supports (display: grid) and (display: inline-grid) { foo {a: b} }';

    $expected = /** @lang text */ <<<'CSS'
    @supports (display: grid) and (display: inline-grid) {
      foo {
        a: b;
      }
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('handles @supports with and not', function () {
    $scss = '@supports (display: grid) and (not (display: inline-grid)) { foo {a: b} }';

    $expected = /** @lang text */ <<<'CSS'
    @supports (display: grid) and (not (display: inline-grid)) {
      foo {
        a: b;
      }
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('handles @supports with multiple and', function () {
    $scss = '@supports (display: table-cell) and (display: list-item) { foo {a: b} }';

    $expected = /** @lang text */ <<<'CSS'
    @supports (display: table-cell) and (display: list-item) {
      foo {
        a: b;
      }
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('handles @supports with multiple and conditions', function () {
    $scss = '@supports (display: table-cell) and (display: list-item) and (display: run-in) { foo {a: b} }';

    $expected = /** @lang text */ <<<'CSS'
    @supports (display: table-cell) and (display: list-item) and (display: run-in) {
      foo {
        a: b;
      }
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('handles @supports with nested parentheses and and', function () {
    $scss = '@supports (display: table-cell) and ((display: list-item) and (display:run-in)) { foo {a: b} }';

    $expected = /** @lang text */ <<<'CSS'
    @supports (display: table-cell) and ((display: list-item) and (display: run-in)) {
      foo {
        a: b;
      }
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('handles @supports with nested parentheses and or', function () {
    $scss = '@supports (display: table-cell) and ((display: list-item) or (display:run-in)) { foo {a: b} }';

    $expected = /** @lang text */ <<<'CSS'
    @supports (display: table-cell) and ((display: list-item) or (display: run-in)) {
      foo {
        a: b;
      }
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('handles @supports with or', function () {
    $scss = '@supports (transform-style: preserve) or (-moz-transform-style: preserve) { foo {a: b} }';

    $expected = /** @lang text */ <<<'CSS'
    @supports (transform-style: preserve) or (-moz-transform-style: preserve) {
      foo {
        a: b;
      }
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('handles @supports with multiple or', function () {
    $scss = '@supports (transform-style: preserve) or (-moz-transform-style: preserve) or (-o-transform-style: preserve) or (-webkit-transform-style: preserve) { foo {a: b} }';

    $expected = /** @lang text */ <<<'CSS'
    @supports (transform-style: preserve) or (-moz-transform-style: preserve) or (-o-transform-style: preserve) or (-webkit-transform-style: preserve) {
      foo {
        a: b;
      }
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('handles @supports with nested or', function () {
    $scss = '@supports (transform-style: preserve-3d) or ((-moz-transform-style: preserve-3d) or ((-o-transform-style: preserve-3d) or (-webkit-transform-style: preserve-3d))) { foo {a: b} }';

    $expected = /** @lang text */ <<<'CSS'
    @supports (transform-style: preserve-3d) or ((-moz-transform-style: preserve-3d) or ((-o-transform-style: preserve-3d) or (-webkit-transform-style: preserve-3d))) {
      foo {
        a: b;
      }
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('handles @supports with not and or', function () {
    $scss = '@supports not ((text-align-last: justify) or (-moz-text-align-last: justify)) { foo {a: b} }';

    $expected = /** @lang text */ <<<'CSS'
    @supports not ((text-align-last: justify) or (-moz-text-align-last: justify)) {
      foo {
        a: b;
      }
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('handles @supports with custom property', function () {
    $scss = '@supports (--foo: green) { foo {a: b} }';

    $expected = /** @lang text */ <<<'CSS'
    @supports (--foo: green) {
      foo {
        a: b;
      }
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('handles @supports with not selector', function () {
    $scss = '@supports not selector(:is(a, b)) { foo {a: b} }';

    $expected = /** @lang text */ <<<'CSS'
    @supports not selector(:is(a, b)) {
      foo {
        a: b;
      }
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('handles @supports with selector :nth-child', function () {
    $scss = '@supports selector(:nth-child(1n of a, b)) { foo {a: b} }';

    $expected = /** @lang text */ <<<'CSS'
    @supports selector(:nth-child(1n of a, b)) {
      foo {
        a: b;
      }
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('handles @supports with interpolation', function () {
    $scss = <<<'SCSS'
    $query: "(feature1: val)";
    @supports #{$query} {
      foo {a: b}
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    @supports (feature1: val) {
      foo {
        a: b;
      }
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('handles @supports with interpolation in parentheses', function () {
    $scss = <<<'SCSS'
    $query: "(feature1: val)";
    @supports (#{$query}) {
      foo {a: b}
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    @supports ((feature1: val)) {
      foo {
        a: b;
      }
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('handles @supports with variables', function () {
    $scss = <<<'SCSS'
    $feature: feature2;
    $val: val;
    @supports ($feature: $val) {
      foo {a: b}
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    @supports (feature2: val) {
      foo {
        a: b;
      }
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('handles @supports with complex condition', function () {
    $scss = <<<'SCSS'
    $query: "(feature1: val)";
    $feature: feature2;
    $val: val;
    @supports (#{$query} and ($feature: $val)) {
      foo {a: b}
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    @supports ((feature1: val) and (feature2: val)) {
      foo {
        a: b;
      }
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('handles @supports with complex or condition', function () {
    $scss = <<<'SCSS'
    $query: "(feature1: val)";
    $feature: feature2;
    $val: val;
    @supports (#{$query} and ($feature: $val)) or (not ($feature + 3: $val + 4)) {
      foo {a: b}
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    @supports ((feature1: val) and (feature2: val)) or (not (feature23: val4)) {
      foo {
        a: b;
      }
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});
