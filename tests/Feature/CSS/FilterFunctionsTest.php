<?php

declare(strict_types=1);

use DartSass\Compiler;

beforeEach(function () {
    $this->compiler = new Compiler();
});

describe('CSS filter functions', function () {
    it('preserves CSS filter functions in filter property', function () {
        $scss = <<<'SCSS'
        .test {
            filter: hue-rotate(177deg) saturate(109%);
        }
        SCSS;

        $expected = <<<'CSS'
        .test {
          filter: hue-rotate(177deg) saturate(109%);
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);

        // Test multiple CSS filter functions
        $scss = <<<'SCSS'
        .test {
            filter: blur(5px) brightness(1.2) contrast(150%);
        }
        SCSS;

        $expected = <<<'CSS'
        .test {
          filter: blur(5px) brightness(1.2) contrast(150%);
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('distinguishes CSS filter functions from Sass color functions', function () {
        // CSS filter function should be preserved
        $scss = <<<'SCSS'
        .test {
            filter: saturate(150%);
        }
        SCSS;

        $expected = <<<'CSS'
        .test {
          filter: saturate(150%);
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('handles CSS filter functions with calc expressions', function () {
        $scss = <<<'SCSS'
        .test {
            filter: hue-rotate(calc(180deg + 45deg));
        }
        SCSS;

        $expected = <<<'CSS'
        .test {
          filter: hue-rotate(225deg);
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('handles various CSS filter functions correctly', function () {
        $scss = <<<'SCSS'
        .test {
            filter: sepia(0.8) saturate(120%) hue-rotate(45deg);
        }
        SCSS;

        $expected = <<<'CSS'
        .test {
          filter: sepia(.8) saturate(120%) hue-rotate(45deg);
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('handles filter functions with variables', function () {
        $scss = <<<'SCSS'
        $hue: 45deg;
        .test {
            filter: hue-rotate($hue);
        }
        SCSS;

        $expected = <<<'CSS'
        .test {
          filter: hue-rotate(45deg);
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });
});
