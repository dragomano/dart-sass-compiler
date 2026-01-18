<?php

declare(strict_types=1);

use DartSass\Compiler;
use DartSass\Exceptions\CompilationException;
use DartSass\Loaders\LoaderInterface;

beforeEach(function () {
    $this->loader   = mock(LoaderInterface::class);
    $this->compiler = new Compiler(loader: $this->loader);
});

it('compiles multiplication with no left unit and right unit', function () {
    $scss = <<<'SCSS'
    body {
        width: 2 * 3px;
    }
    SCSS;

    $expected = <<<'CSS'
    body {
      width: 6px;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('compiles division with no left unit and right unit', function () {
    $scss = <<<'SCSS'
    body {
        width: 6 / 2px;
    }
    SCSS;

    $expected = <<<'CSS'
    body {
      width: 3px;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('compiles multiplication with left unit and no right unit', function () {
    $scss = <<<'SCSS'
    body {
        width: 2px * 3;
    }
    SCSS;

    $expected = <<<'CSS'
    body {
      width: 6px;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('compiles multiplication with left percentage unit and no right unit', function () {
    $scss = <<<'SCSS'
    body {
        width: 50% * 2;
    }
    SCSS;

    $expected = <<<'CSS'
    body {
      width: 100%;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('compiles division with left unit and no right unit', function () {
    $scss = <<<'SCSS'
    body {
        width: 6px / 2;
    }
    SCSS;

    $expected = <<<'CSS'
    body {
      width: 3px;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('compiles addition with string operands using calc', function () {
    $scss = <<<'SCSS'
    body {
        content: "hello" + "world";
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    body {
      content: helloworld;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('compiles addition with numeric and string operands using calc', function () {
    $scss = <<<'SCSS'
    body {
        width: 10px + "test";
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    body {
      width: 10pxtest;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('compiles multiplication with numeric and string operands using calc', function () {
    $scss = <<<'SCSS'
    body {
        width: 10px * "test";
    }
    SCSS;

    expect(fn() => $this->compiler->compileString($scss))
        ->toThrow(CompilationException::class);
});

it('compiles division with numeric and string operands using calc', function () {
    $scss = <<<'SCSS'
    body {
        width: 10px / "test";
    }
    SCSS;

    expect(fn() => $this->compiler->compileString($scss))
        ->toThrow(CompilationException::class);
});

it('compiles multiplication with string operands using calc', function () {
    $scss = <<<'SCSS'
    body {
        content: "hello" * "world";
    }
    SCSS;

    expect(fn() => $this->compiler->compileString($scss))
        ->toThrow(CompilationException::class);
});

it('compiles concatenation of string function results and literals without duplicating quotes', function () {
    $scss = <<<'SCSS'
    @use "sass:string";

    .test {
        content: string.slice("hello", 1, 3) + " world";
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    .test {
      content: "hel world";
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});
