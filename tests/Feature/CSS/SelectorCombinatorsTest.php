<?php

declare(strict_types=1);

use DartSass\Compiler;
use DartSass\Parsers\Syntax;

dataset('scss selectors', [
  'multiple class selectors' => [
    <<<'SCSS'
    .row.d-flex {
        > .col-xs-12 {
            display: flex;
            flex-direction: column;
        }

        > [class*="col-"] {
            display: flex;
            flex-direction: column;
        }
    }
    SCSS,
    /** @lang text */ <<<'CSS'
    .row.d-flex > .col-xs-12 {
      display: flex;
      flex-direction: column;
    }
    .row.d-flex > [class*=col-] {
      display: flex;
      flex-direction: column;
    }
    CSS,
  ],

  'attribute selectors in nesting' => [
    <<<'SCSS'
    .category-default-icon {
        [class*="icon"] {
            font-size: 20px;
        }
    }
    SCSS,
    /** @lang text */ <<<'CSS'
    .category-default-icon [class*=icon] {
      font-size: 20px;
    }
    CSS,
  ],

  'attribute selectors with quotes for complex values' => [
    <<<'SCSS'
    .container {
        [data-value="hello world"] {
            padding: 10px;
        }
    }
    SCSS,
    <<<'CSS'
    .container [data-value="hello world"] {
      padding: 10px;
    }
    CSS,
  ],

  'attribute selectors with not pseudo-class' => [
    <<<'SCSS'
    #layout {
        aside {
            &[id^="block"]:not(:first-child) {
                clear: both;
                margin-top: 4px;
            }
        }
    }
    SCSS,
    /** @lang text */ <<<'CSS'
    #layout aside[id^=block]:not(:first-child) {
      clear: both;
      margin-top: 4px;
    }
    CSS,
  ],

  'has pseudo-class in selectors' => [
    <<<'SCSS'
    section {
        article[itemprop='articleBody'] {
            a:has(img) {
                display: grid;
                place-items: center;

            }
        }
    }
    SCSS,
    <<<'CSS'
    section article[itemprop=articleBody] a:has(img) {
      display: grid;
      place-items: center;
    }
    CSS,
  ],

  'pseudo-classes with css variables' => [
    <<<'SCSS'
    input[type="url"] {
        &:focus {
            border: 1px solid var(--toggle-border-on, #10b981);
        }
    }
    SCSS,
    /** @lang text */ <<<'CSS'
    input[type=url]:focus {
      border: 1px solid var(--toggle-border-on, #10b981);
    }
    CSS,
  ],
]);

dataset('sass selectors', [
  'multiple class selectors' => [
    <<<'SASS'
    .row.d-flex
      > .col-xs-12
        display: flex
        flex-direction: column

      > [class*="col-"]
        display: flex
        flex-direction: column
    SASS,
    /** @lang text */ <<<'CSS'
    .row.d-flex > .col-xs-12 {
      display: flex;
      flex-direction: column;
    }
    .row.d-flex > [class*=col-] {
      display: flex;
      flex-direction: column;
    }
    CSS,
  ],

  'attribute selectors in nesting' => [
    <<<'SASS'
    .category-default-icon
      [class*="icon"]
        font-size: 20px
    SASS,
    /** @lang text */ <<<'CSS'
    .category-default-icon [class*=icon] {
      font-size: 20px;
    }
    CSS,
  ],

  'attribute selectors with quotes for complex values' => [
    <<<'SASS'
    .container
      [data-value="hello world"]
        padding: 10px
    SASS,
    <<<'CSS'
    .container [data-value="hello world"] {
      padding: 10px;
    }
    CSS,
  ],

  'attribute selectors with not pseudo-class' => [
    <<<'SASS'
    #layout
      aside
        &[id^="block"]:not(:first-child)
          clear: both
          margin-top: 4px
    SASS,
    /** @lang text */ <<<'CSS'
    #layout aside[id^=block]:not(:first-child) {
      clear: both;
      margin-top: 4px;
    }
    CSS,
  ],

  'has pseudo-class in selectors' => [
    <<<'SASS'
    section
      article[itemprop='articleBody']
        a:has(img)
          display: grid
          place-items: center
    SASS,
    <<<'CSS'
    section article[itemprop=articleBody] a:has(img) {
      display: grid;
      place-items: center;
    }
    CSS,
  ],

  'pseudo-classes with css variables' => [
    <<<'SASS'
    input[type="url"]
      &:focus
        border: 1px solid var(--toggle-border-on, #10b981)
    SASS,
    /** @lang text */ <<<'CSS'
    input[type=url]:focus {
      border: 1px solid var(--toggle-border-on, #10b981);
    }
    CSS,
  ],
]);

beforeEach(function () {
    $this->compiler = new Compiler();
});

describe('SCSS selectors', function () {
    it('handles complex selectors', function (string $scss, string $expected) {
        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    })->with('scss selectors');
});

describe('SASS selectors', function () {
    it('handles complex selectors', function (string $sass, string $expected) {
        expect($this->compiler->compileString($sass, Syntax::SASS))
            ->toEqualCss($expected);
    })->with('sass selectors');
});
