<?php

declare(strict_types=1);

use DartSass\Compiler;
use DartSass\Exceptions\CompilationException;
use DartSass\Loaders\LoaderInterface;

beforeEach(function () {
    $this->loader   = mock(LoaderInterface::class);
    $this->compiler = new Compiler(loader: $this->loader);
});

describe('@debug directive', function () {
    it('compiles @debug directive without error', function () {
        $scss = <<<'SCSS'
        $color: red;
        body {
            @debug "Color is: " + $color;
            background: $color;
        }
        SCSS;

        $expectedCss = <<<'CSS'
        body {
          background: red;
        }
        CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expectedCss);
    });

    it('compiles @debug with number', function () {
        $scss = <<<'SCSS'
        $size: 42;
        @debug $size;
        .box {
            width: $size + px;
        }
        SCSS;

        $expectedCss = /** @lang text */ <<<'CSS'
        .box {
          width: 42px;
        }
        CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expectedCss);
    });
});

describe('@warn directive', function () {
    it('compiles @warn directive without error', function () {
        $scss = <<<'SCSS'
        $theme: dark;
        @if $theme == dark {
            @warn "Using dark theme";
            body {
                background: black;
            }
        }
        SCSS;

        $expectedCss = <<<'CSS'
        body {
          background: black;
        }
        CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expectedCss);
    });

    it('compiles @warn with interpolation', function () {
        $scss = <<<'SCSS'
        $prefix: "my";
        @warn "#{$prefix}-component is deprecated";
        .old {
            display: block;
        }
        SCSS;

        $expectedCss = /** @lang text */ <<<'CSS'
        .old {
          display: block;
        }
        CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expectedCss);
    });
});

describe('@error directive', function () {
    it('throws CompilationException on @error directive', function () {
        $scss = <<<'SCSS'
        $color: blue;
        @if $color == blue {
            @error "Blue is not allowed";
        }
        body {
            color: $color;
        }
        SCSS;

        expect(fn() => $this->compiler->compileString($scss))
            ->toThrow(CompilationException::class);
    });

    it('throws CompilationException with message containing error text', function () {
        $scss = <<<'SCSS'
        $value: 100;
        @if $value > 50 {
            @error "Value too high: #{$value}";
        }
        SCSS;

        expect(fn() => $this->compiler->compileString($scss))
            ->toThrow(CompilationException::class, 'Value too high: 100');
    });

    it('throws CompilationException inside mixin', function () {
        $scss = <<<'SCSS'
        @mixin validate($input) {
            @if $input == null {
                @error "Input cannot be null";
            }
        }
        .test {
            @include validate(null);
        }
        SCSS;

        expect(fn() => $this->compiler->compileString($scss))
            ->toThrow(CompilationException::class, 'Input cannot be null');
    });
});
