<?php

declare(strict_types=1);

use DartSass\Compiler;

beforeEach(function () {
    $this->compiler = new Compiler();
});

describe('CSS math functions', function () {
    it('supports calc function', function () {
        $scss = <<<'SCSS'
        $width: 100px;
        $min-padding: min(10px, 2vw);

        body {
            width: calc(#{$width} + 20px);
            height: calc(100% * 0.5);
        }
        div {
            width: calc($min-padding * 2);
            height: calc(20px);
        }
        SCSS;

        $expected = <<<'CSS'
        body {
          width: 120px;
          height: 50%;
        }
        div {
          width: calc(min(10px, 2vw) * 2);
          height: 20px;
        }
        CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    });

    it('supports clamp function', function () {
        $scss = <<<'SCSS'
        body {
            width: clamp(100px, 50%, 500px);
        }
        SCSS;

        $expected = <<<'CSS'
        body {
          width: clamp(100px, 50%, 500px);
        }
        CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);

        $scss = <<<'SCSS'
        body {
            width: clamp(100px, 500px, 500px);
        }
        SCSS;

        $expected = <<<'CSS'
        body {
          width: 500px;
        }
        CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    });

    it('supports max function', function () {
        $scss = <<<'SCSS'
        body {
            width: max(100px, 200px, 50%);
        }
        SCSS;

        $expected = <<<'CSS'
        body {
          width: max(100px, 200px, 50%);
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);

        $scss = <<<'SCSS'
        body {
            width: max(100px, 200px);
        }
        SCSS;

        $expected = <<<'CSS'
        body {
          width: 200px;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('supports min function', function () {
        $scss = <<<'SCSS'
        body {
            width: min(100px, 200px, 50%);
        }
        SCSS;

        $expected = <<<'CSS'
        body {
          width: min(100px, 200px, 50%);
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);

        $scss = <<<'SCSS'
        body {
            width: min(100px, 200px);
        }
        SCSS;

        $expected = <<<'CSS'
        body {
          width: 100px;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });
});
