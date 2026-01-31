<?php

declare(strict_types=1);

use DartSass\Compiler;

beforeEach(function () {
    $this->compiler = new Compiler();
});

describe('transform functions', function () {
    it('tests translate support', function () {
        $scss = '.test { transform: translate(10px, 20px); }';

        $expected = /** @lang text */ <<<'CSS'
        .test {
          transform: translate(10px, 20px);
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('tests rotate support', function () {
        $scss = '.test { transform: rotate(45deg); }';

        $expected = /** @lang text */ <<<'CSS'
        .test {
          transform: rotate(45deg);
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('tests scale support', function () {
        $scss = '.test { transform: scale(1.5); }';

        $expected = /** @lang text */ <<<'CSS'
        .test {
          transform: scale(1.5);
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('tests skew support', function () {
        $scss = '.test { transform: skew(30deg, 20deg); }';

        $expected = /** @lang text */ <<<'CSS'
        .test {
          transform: skew(30deg, 20deg);
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });
});
