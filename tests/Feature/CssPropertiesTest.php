<?php declare(strict_types=1);

use DartSass\Compiler;

beforeEach(function () {
    $this->compiler = new Compiler();
});

it('preserves CSS order property', function () {
    $scss = <<<'SCSS'
    .card-header {
        overflow: hidden;
        z-index: 0;
        position: relative;
        order: 1;
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    .card-header {
      overflow: hidden;
      z-index: 0;
      position: relative;
      order: 1;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('preserves important declarations', function () {
    $scss = <<<'SCSS'
    .article {
        grid-column: span 1 !important;
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    .article {
      grid-column: span 1 !important;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('handles container queries', function () {
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

it('compiles linear gradient function', function () {
    $scss = <<<'SCSS'
    .card-title {
        background-image: linear-gradient(to bottom, transparent, transparent, #111827);
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    .card-title {
      background-image: linear-gradient(to bottom, transparent, transparent, #111827);
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('compiles keyframes at-rule', function () {
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
