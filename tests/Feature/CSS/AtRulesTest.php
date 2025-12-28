<?php

declare(strict_types=1);

use DartSass\Compiler;

beforeEach(function () {
    $this->compiler = new Compiler();
});

it('handles @container rule', function () {
    $scss = <<<'SCSS'
    .article_alt3_view {
        @container (min-width: 400px) {
            .card {
                flex-direction: row;
                align-items: normal;

                .lazy {
                    width: 50%;
                }
            }
        }
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    @container (min-width: 400px) {
      .article_alt3_view .card {
        flex-direction: row;
        align-items: normal;
      }
      .article_alt3_view .card .lazy {
        width: 50%;
      }
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('handles @keyframes rule', function () {
    $scss = <<<'SCSS'
    @keyframes fade-in-up {
        0% {
            opacity: 0;
            transform: translateY(20px);
        }
        100% {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .animate-fade {
        animation: fade-in-up 0.5s ease-out;
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    @keyframes fade-in-up {
      0% {
        opacity: 0;
        transform: translateY(20px);
      }
      100% {
        opacity: 1;
        transform: translateY(0);
      }
    }
    .animate-fade {
      animation: fade-in-up .5s ease-out;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});
