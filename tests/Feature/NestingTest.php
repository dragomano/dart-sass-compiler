<?php

declare(strict_types=1);

use DartSass\Compiler;

beforeEach(function () {
    $this->compiler = new Compiler();
});

it('handles nested selectors', function () {
    $scss = <<<'SCSS'
    .parent {
        color: blue;
        .child {
            font-size: 14px;
        }
        &:hover {
            color: red;
        }
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    .parent {
      color: blue;
    }
    .parent .child {
      font-size: 14px;
    }
    .parent:hover {
      color: red;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('handles grouped selectors with nested pseudo-classes', function () {
    $scss = <<<'SCSS'
    #lp_layout {
        h3,
        h4 {
            &:hover {
                white-space: normal;
            }
        }
    }
    SCSS;

    $expected = <<<'CSS'
    #lp_layout h3:hover,
    #lp_layout h4:hover {
      white-space: normal;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('handles nested pseudo-elements', function () {
    $scss = <<<'SCSS'
    .fa-portal {
        &::before {
            content: "\f0ac";
        }
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    .fa-portal::before {
      content: "\f0ac";
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('handles child combinators in nesting', function () {
    $scss = <<<'SCSS'
    .sidebar > {
        .error {
            color: red;
        }
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    .sidebar > .error {
      color: red;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('handles direct child combinators', function () {
    $scss = <<<'SCSS'
    .article_view {
        > div {
            margin-bottom: 10px;
        }
    }
    SCSS;

    $expected = <<<'CSS'
    .article_view > div {
      margin-bottom: 10px;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('handles nested pseudo-classes', function () {
    $scss = <<<'SCSS'
    a:hover {
        text-decoration: none;
        opacity: .7;
    }
    SCSS;

    $expected = <<<'CSS'
    a:hover {
      text-decoration: none;
      opacity: .7;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('handles complex nested pseudo-classes', function () {
    $scss = <<<'SCSS'
    .article {
        &:nth-last-child(-n+5) {
            grid-column: span 2;
        }

        &:nth-last-child(2),
        &:last-child {
            grid-column: span 3;
        }
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    .article:nth-last-child(-n+5) {
      grid-column: span 2;
    }
    .article:nth-last-child(2),
    .article:last-child {
      grid-column: span 3;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('handles pseudo-classes with nested selectors', function () {
    $scss = <<<'SCSS'
    .article_simple_view {
        > div {
            &:hover {
                box-shadow: 0 1px 3px 0 rgba(0, 0, 0, .1), 0 1px 2px 0 rgba(0, 0, 0, .06);
            }
        }
    }
    SCSS;

    $expected = <<<'CSS'
    .article_simple_view > div:hover {
      box-shadow: 0 1px 3px 0 rgba(0, 0, 0, .1), 0 1px 2px 0 rgba(0, 0, 0, .06);
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('handles descendant pseudo-classes', function () {
    $scss = <<<'SCSS'
    article {
        a:hover {
            text-decoration: none;
            opacity: .7;
        }
    }
    SCSS;

    $expected = <<<'CSS'
    article a:hover {
      text-decoration: none;
      opacity: .7;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('handles nested selectors with margins', function () {
    $scss = <<<'SCSS'
    section {
        #display_head {
            margin-top: .1em;
            margin-bottom: 0;

            span {
                margin: 0;
            }
        }
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    section #display_head {
      margin-top: .1em;
      margin-bottom: 0;
    }
    section #display_head span {
      margin: 0;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});
