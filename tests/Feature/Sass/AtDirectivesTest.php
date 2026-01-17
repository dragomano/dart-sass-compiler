<?php

declare(strict_types=1);

use DartSass\Compiler;
use DartSass\Exceptions\CompilationException;

beforeEach(function () {
    $this->compiler = new Compiler(['loadPaths' => [__DIR__ . '/fixtures']]);
});

describe('supports @use directive', function () {
    it('compiles @use directive and accesses module variable', function () {
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

    it('compiles @use with namespace alias', function () {
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

    it('compiles @use with global namespace (*)', function () {
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

    it('compiles @use \'_colors\' with global namespace', function () {
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

    it('compiles @use with default namespace', function () {
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

    it('uses module variable in simple CSS property', function () {
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

    it('uses module variable in complex CSS property (border)', function () {
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

    it('uses module variable in multiple property values', function () {
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

    it('uses module variable inside Sass function (lighten)', function () {
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

    it('uses module variable in calc() function', function () {
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

    it('compiles file with multiple @use directives', function () {
        $expected = /** @lang text */ <<<'CSS'
        .button {
          background-color: #007bff;
          border: 1px solid #6c757d;
          padding: 10px;
          display: flex;
          justify-content: center;
          align-items: center;
          font-size: clamp(12px, 2.5vw, 20px);
          max-width: 1200px;
          border-radius: 5px;
        }
        CSS;

        expect($this->compiler->compileFile('test_main'))
            ->toEqualCss($expected);
    });
});

describe('supports @forward directive', function () {
    it('compiles @forward directive and accesses module variable', function () {
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

    it('compiles @forward with variable configuration', function () {
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

    it('compiles @forward with hidden members', function () {
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

    it('throws CompilationException when accessing hidden variable via @forward', function () {
        $scss = <<<'SCSS'
        @forward "colors" hide $primary;

        body {
            background: colors.$primary;
        }
        SCSS;

        expect(fn() => $this->compiler->compileString($scss))
            ->toThrow(CompilationException::class, 'Property $primary not found in module colors');
    });
});
