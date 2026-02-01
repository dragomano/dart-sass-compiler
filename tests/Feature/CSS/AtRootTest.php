<?php

declare(strict_types=1);

use DartSass\Compiler;

beforeEach(function () {
    $this->compiler = new Compiler();
});

it('handles basic @at-root', function () {
    $scss = <<<'SCSS'
    .parent {
        .child {
            @at-root {
                .sibling { color: red; }
            }
        }
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    .sibling {
      color: red;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('handles @at-root with multiple selectors', function () {
    $scss = <<<'SCSS'
    .block {
        &__element {
            @at-root {
                .other { color: blue; }
                .another { color: green; }
            }
        }
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    .other {
      color: blue;
    }
    .another {
      color: green;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('handles @at-root with declarations', function () {
    $scss = <<<'SCSS'
    .parent {
        @at-root {
            .child {
                color: red;
                font-size: 14px;
            }
        }
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    .child {
      color: red;
      font-size: 14px;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('handles @at-root with without context', function () {
    $scss = <<<'SCSS'
    @media print {
        .page {
            @at-root (without: media) {
                width: 100%;
            }
        }
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    .page {
      width: 100%;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('handles nested @at-root', function () {
    $scss = <<<'SCSS'
    .outer {
        .middle {
            @at-root {
                .inner { color: red; }
            }
        }
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    .inner {
      color: red;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});
