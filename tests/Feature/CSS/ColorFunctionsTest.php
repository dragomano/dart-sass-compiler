<?php

declare(strict_types=1);

use DartSass\Compiler;

beforeEach(function () {
    $this->compiler = new Compiler();
});

describe('CSS color functions', function () {
    it('supports hsl function', function () {
        $scss = <<<'SCSS'
        body {
            background: hsl(0deg, 100%, 50%);
        }
        SCSS;

        $expected = <<<'CSS'
        body {
          background: hsl(0, 100%, 50%);
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);

        $scss = <<<'SCSS'
        body {
            background: hsl(120deg, 100%, 50%);
        }
        SCSS;

        $expected = <<<'CSS'
        body {
          background: hsl(120, 100%, 50%);
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('supports lch function', function () {
        $scss = <<<'SCSS'
        body {
            background: lch(60% 40 30deg);
        }
        SCSS;

        $expected = <<<'CSS'
        body {
          background: lch(60% 40 30);
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);

        $scss = <<<'SCSS'
        body {
            background: lch(40% 60 240deg / 0.5);
        }
        SCSS;

        $expected = <<<'CSS'
        body {
          background: lch(40% 60 240 / 0.5);
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('supports oklch function', function () {
        $scss = <<<'SCSS'
        body {
            background: oklch(0.6 0.3 90deg);
        }
        SCSS;

        $expected = <<<'CSS'
        body {
          background: oklch(60% 0.3 90);
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);

        $scss = <<<'SCSS'
        body {
            background: oklch(0.2 0.4 270deg / 0.9);
        }
        SCSS;

        $expected = <<<'CSS'
        body {
          background: oklch(20% 0.4 270 / 0.9);
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });
});
