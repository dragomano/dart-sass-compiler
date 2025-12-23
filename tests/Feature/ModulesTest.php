<?php

declare(strict_types=1);

use DartSass\Compiler;
use DartSass\Exceptions\CompilationException;

beforeEach(function () {
    $this->compiler = new Compiler(['loadPaths' => [__DIR__ . '/fixtures']]);
});

it('supports @use directive', function () {
    $scss = <<<'SCSS'
    @use "colors";
    body {
        background: colors.$primary;
    }
    SCSS;

    $expected = <<<'CSS'
    body {
      background: blue;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('supports @forward directive', function () {
    $scss = <<<'SCSS'
    @forward "colors";
    body {
        background: colors.$primary;
    }
    SCSS;

    $expected = <<<'CSS'
    body {
      background: blue;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('supports @forward with configuration', function () {
    $scss = <<<'SCSS'
    @forward "colors" with ($primary: green);
    body {
        background: colors.$primary;
    }
    SCSS;

    $expected = <<<'CSS'
    body {
      background: green;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('supports @forward with hide', function () {
    $scss = <<<'SCSS'
    @forward "colors" hide $secondary;
    body {
        background: colors.$primary;
    }
    SCSS;

    $expected = <<<'CSS'
    body {
      background: blue;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('throws error when accessing hidden property', function () {
    $scss = <<<'SCSS'
    @forward "colors" hide $primary;
    body {
        background: colors.$primary;
    }
    SCSS;

    expect(fn () => $this->compiler->compileString($scss))
        ->toThrow(CompilationException::class, 'Property $primary not found in module colors');
});

it('supports @use with alias', function () {
    $scss = <<<'SCSS'
    @use "_variables" as vars;
    body {
        background: vars.$color-checked;
    }
    SCSS;

    $expected = <<<'CSS'
    body {
      background: #00cc33;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('supports @use with global namespace', function () {
    $scss = <<<'SCSS'
    @use "_variables" as *;
    body {
        background: $color-checked;
    }
    SCSS;

    $expected = <<<'CSS'
    body {
      background: #00cc33;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('supports @use "_colors" as *', function () {
    $scss = <<<'SCSS'
    @use "_colors" as *;
    body {
        background: $primary;
    }
    SCSS;

    $expected = <<<'CSS'
    body {
      background: blue;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('supports @use with default namespace', function () {
    $scss = <<<'SCSS'
    @use "_variables";
    body {
        background: variables.$color-checked;
    }
    SCSS;

    $expected = <<<'CSS'
    body {
      background: #00cc33;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('supports module variable usage in simple property', function () {
    $scss = <<<'SCSS'
    @use "variables";
    .test {
        background: variables.$color-light-gray;
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    .test {
      background: #ddd;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('supports module variable usage in complex property', function () {
    $scss = <<<'SCSS'
    @use "variables";
    .test {
        border: 1px solid variables.$color-light-gray;
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    .test {
      border: 1px solid #ddd;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('supports module variable usage in multiple values', function () {
    $scss = <<<'SCSS'
    @use "variables";
    .test {
        margin: 10px variables.$some-margin;
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    .test {
      margin: 10px 7px;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('supports module variable usage nested in function', function () {
    $scss = <<<'SCSS'
    @use "variables";
    .test {
        color: lighten(variables.$color-light-gray, 10%);
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    .test {
      color: #f7f7f7;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('supports module variable usage in calc function', function () {
    $scss = <<<'SCSS'
    @use "variables";
    .test {
        width: calc(100% - variables.$some-percent);
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    .test {
      width: 75%;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});
