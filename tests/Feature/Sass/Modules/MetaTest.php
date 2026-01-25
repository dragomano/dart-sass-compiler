<?php

declare(strict_types=1);

use DartSass\Compiler;

beforeEach(function () {
    $this->compiler = new Compiler(['loadPaths' => [dirname(__DIR__) . '/fixtures']]);
});

describe('sass:meta', function () {
    it('supports apply function', function () {
        $scss = <<<'SCSS'
        @use "sass:meta";
        @use "sass:string";

        @mixin apply-to-all($mixin, $list) {
            @each $element in $list {
                @include meta.apply($mixin, $element);
            }
        }

        @mixin font-class($size) {
            .font-#{$size} {
                font-size: $size;
            }
        }

        $sizes: [8px, 12px, 2rem];

        @include apply-to-all(meta.get-mixin("font-class"), $sizes);
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .font-8px {
          font-size: 8px;
        }
        .font-12px {
          font-size: 12px;
        }
        .font-2rem {
          font-size: 2rem;
        }
        CSS;

        $css = $this->compiler->compileString($scss);

        expect($css)->toEqualCss($expected);
    });

    it('supports load-css function', function () {
        $scss = <<<'SCSS'
        @use "sass:meta";

        .test {
            content: meta.load-css("https://php.dragomano.ru/extra.css");
        }
        SCSS;

        expect(fn() => $this->compiler->compileString($scss))->not->toThrow(Exception::class);
    });

    it('supports accepts-content function', function () {
        $scss = <<<'SCSS'
        @use "sass:meta";

        @mixin with-content {
            @content;
        }

        @mixin without-content {
            value: "no-content";
        }

        .test {
            with: meta.accepts-content(meta.get-mixin("with-content"));
            without: meta.accepts-content(meta.get-mixin("without-content"));
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .test {
          with: true;
          without: false;
        }
        CSS;

        $css = $this->compiler->compileString($scss);

        expect($css)->toEqualCss($expected);
    });

    it('supports calc-args function', function () {
        $scss = <<<'SCSS'
        @use "sass:meta";

        .test {
            args: meta.calc-args(calc(10px + 20%));
            args2: meta.calc-args(clamp(50px, var(--width), 1000px));
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .test {
          args: 10px + 20%;
          args2: 50px, var(--width), 1000px;
        }
        CSS;

        $css = $this->compiler->compileString($scss);

        expect($css)->toEqualCss($expected);
    });

    it('supports calc-name function', function () {
        $scss = <<<'SCSS'
        @use "sass:meta";

        .test {
            name: meta.calc-name(calc(10px + 20%));
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .test {
          name: "calc";
        }
        CSS;

        $css = $this->compiler->compileString($scss);

        expect($css)->toEqualCss($expected);
    });

    it('supports call function', function () {
        $scss = <<<'SCSS'
        @use "sass:meta";
        @use "sass:math";

        .test {
            result: meta.call(meta.get-function("ceil", "math"), 4.2);
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .test {
          result: 5;
        }
        CSS;

        $css = $this->compiler->compileString($scss);

        expect($css)->toEqualCss($expected);
    });

    it('supports content-exists function', function () {
        $scss = <<<'SCSS'
        @use "sass:meta";

        @mixin test-mixin {
            exists: meta.content-exists();
        }

        .test {
            @include test-mixin;
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .test {
          exists: false;
        }
        CSS;

        $css = $this->compiler->compileString($scss);

        expect($css)->toEqualCss($expected);
    });

    it('supports feature-exists function', function () {
        $scss = <<<'SCSS'
        @use "sass:meta";

        .test {
            calc: meta.feature-exists("global-variable-shadowing");
            clamp: meta.feature-exists("clamp");
            not-exists: meta.feature-exists("nonexistent");
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .test {
          calc: true;
          clamp: false;
          not-exists: false;
        }
        CSS;

        $css = $this->compiler->compileString($scss);

        expect($css)->toEqualCss($expected);
    });

    it('supports function-exists function', function () {
        $scss = <<<'SCSS'
        @use "sass:meta";
        @use "sass:math";

        .test {
            exists: meta.function-exists("ceil", "math");
            not-exists: meta.function-exists("nonexistent");
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .test {
          exists: true;
          not-exists: false;
        }
        CSS;

        $css = $this->compiler->compileString($scss);

        expect($css)->toEqualCss($expected);
    });

    it('supports get-function function', function () {
        $scss = <<<'SCSS'
        @use "sass:meta";

        @function test-function($x) {
            @return $x * 2;
        }

        .test {
            result: meta.call(meta.get-function("test-function"), 3);
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .test {
          result: 6;
        }
        CSS;

        $css = $this->compiler->compileString($scss);

        expect($css)->toEqualCss($expected);
    });

    it('supports get-mixin function', function () {
        $scss = <<<'SCSS'
        @use "sass:meta";

        @mixin shadow($level) {
            box-shadow: 0 0 $level rgba(0, 0, 0, 0.3);
        }

        $shadow-mixin: meta.get-mixin(shadow);

        .box {
            @include meta.apply($shadow-mixin, 10px);
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .box {
          box-shadow: 0 0 10px rgba(0, 0, 0, .3);
        }
        CSS;

        $css = $this->compiler->compileString($scss);

        expect($css)->toEqualCss($expected);
    });

    it('supports global-variable-exists function', function () {
        $scss = <<<'SCSS'
        @use "sass:meta";

        $global-var: "test";

        .test {
            exists: meta.global-variable-exists("global-var");
            not-exists: meta.global-variable-exists("nonexistent");
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .test {
          exists: true;
          not-exists: false;
        }
        CSS;

        $css = $this->compiler->compileString($scss);

        expect($css)->toEqualCss($expected);
    });

    it('supports inspect function', function () {
        $scss = <<<'SCSS'
        @use "sass:meta";

        .test {
            number: meta.inspect(123px);
            color: meta.inspect(#ff0000);
            string: meta.inspect("hello");
            list: meta.inspect(1 2 3);
            map: meta.inspect((key: value));
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .test {
          number: 123px;
          color: red;
          string: "hello";
          list: 1 2 3;
          map: (key: "value");
        }
        CSS;

        $css = $this->compiler->compileString($scss);

        expect($css)->toEqualCss($expected);
    });

    it('supports keywords function', function () {
        $scss = <<<'SCSS'
        @use "sass:meta";

        @mixin syntax-colors($args...) {
            @each $name, $color in meta.keywords($args) {
                pre span.stx-#{$name} {
                    color: $color;
                }
            }
        }

        @include syntax-colors(
            $string: #080,
            $comment: #800,
            $variable: #60b,
        );
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        pre span.stx-string {
          color: #080;
        }
        pre span.stx-comment {
          color: #800;
        }
        pre span.stx-variable {
          color: #60b;
        }
        CSS;

        $css = $this->compiler->compileString($scss);

        expect($css)->toEqualCss($expected);
    });

    it('supports mixin-exists function', function () {
        $scss = <<<'SCSS'
        @use "sass:meta";

        @mixin other-test-mixin {
            content: "test";
        }

        .test {
            exists: meta.mixin-exists("other-test-mixin");
            not-exists: meta.mixin-exists("nonexistent");
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .test {
          exists: true;
          not-exists: false;
        }
        CSS;

        $css = $this->compiler->compileString($scss);

        expect($css)->toEqualCss($expected);
    });

    it('supports module-functions function', function () {
        $scss = <<<'SCSS'
        @use "sass:meta";
        @use "sass:math";

        $math-fns: meta.module-functions(math);

        .box {
            width: meta.call(map-get($math-fns, "max"), 100px, 60px);
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .box {
          width: 100px;
        }
        CSS;

        $css = $this->compiler->compileString($scss);

        expect($css)->toEqualCss($expected);
    });

    it('supports module-mixins function', function () {
        $scss = <<<'SCSS'
        @use "sass:meta";
        @use "lib";

        .box {
            @include meta.apply(
                map-get(meta.module-mixins(lib), "rounded")
            );
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .box {
          border-radius: 8px;
        }
        CSS;

        $css = $this->compiler->compileString($scss);
        expect($css)->toEqualCss($expected);
    });

    it('supports module-variables function', function () {
        $scss = <<<'SCSS'
        @use "sass:meta";
        @use "lib";

        $vars: meta.module-variables(lib);

        .box {
            color: map-get($vars, "color");
            padding: map-get($vars, "padding");
            @include lib.rounded;
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .box {
          border-radius: 8px;
          color: red;
          padding: 16px;
        }
        CSS;

        $css = $this->compiler->compileString($scss);
        expect($css)->toEqualCss($expected);
    });

    it('supports type-of function', function () {
        $scss = <<<'SCSS'
        @use "sass:meta";

        .test {
            number: meta.type-of(42);
            color: meta.type-of(#ff0000);
            string: meta.type-of("hello");
            bool: meta.type-of(true);
            null: meta.type-of(null);
            list: meta.type-of(1 2 3);
            map: meta.type-of((key: value));
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .test {
          number: number;
          color: color;
          string: string;
          bool: bool;
          null: null;
          list: list;
          map: map;
        }
        CSS;

        $css = $this->compiler->compileString($scss);
        expect($css)->toEqualCss($expected);
    });

    it('supports variable-exists function', function () {
        $scss = <<<'SCSS'
        @use "sass:meta";

        $local-var: "test";

        @mixin another-test-mixin {
            $mixin-var: "mixin";
            local: meta.variable-exists("mixin-var");
            global: meta.variable-exists("local-var");
        }

        .test {
            @include another-test-mixin;
            not-exists: meta.variable-exists("nonexistent");
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .test {
          local: true;
          global: true;
          not-exists: false;
        }
        CSS;

        $css = $this->compiler->compileString($scss);
        expect($css)->toEqualCss($expected);
    });
});
