<?php declare(strict_types=1);

use DartSass\Compiler;
use DartSass\Exceptions\CompilationException;
use DartSass\Parsers\Syntax;

dataset('scss variables', [
  'definition and usage' => [
    <<<'SCSS'
        $primary-color: #333;
        body {
            color: $primary-color;
        }
        SCSS,
    <<<'CSS'
        body {
          color: #333;
        }
        CSS,
  ],

  '!default flag' => [
    <<<'SCSS'
        $color: blue !default;
        $color: red;
        body {
            color: $color;
        }
        SCSS,
    <<<'CSS'
        body {
          color: red;
        }
        CSS,
  ],

  'global scope with !global' => [
    <<<'SCSS'
        .block {
            $color: blue !global;
        }
        body {
            color: $color;
        }
        SCSS,
    <<<'CSS'
        body {
          color: blue;
        }
        CSS,
  ],

  'variables in nested selectors' => [
    <<<'SCSS'
        $color-light-gray: #ddd;
        .comment {
            &_wrapper {
                width: 100%;
                padding-left: 55px;

                .comment_entry {
                    border: 1px solid $color-light-gray;
                }
            }
        }
        SCSS,
    /** @lang text */ <<<'CSS'
        .comment_wrapper {
          width: 100%;
          padding-left: 55px;
        }
        .comment_wrapper .comment_entry {
          border: 1px solid #ddd;
        }
        CSS,
  ],
]);

dataset('scss interpolation', [
  'simple interpolation' => [
    <<<'SCSS'
        $name: "header";
        .#{$name} {
            color: red;
        }
        SCSS,
    /** @lang text */ <<<'CSS'
        .header {
          color: red;
        }
        CSS,
  ],

  'nested interpolation' => [
    <<<'SCSS'
        $name: "footer";
        #{"#{$name}"} {
            color: green;
        }
        SCSS,
    /** @lang text */ <<<'CSS'
        footer {
          color: green;
        }
        CSS,
  ],
]);

dataset('sass variables', [
  'definition and usage' => [
    <<<'SASS'
        $primary-color: #333
        body
          color: $primary-color
        SASS,
    <<<'CSS'
        body {
          color: #333;
        }
        CSS,
  ],

  '!default flag' => [
    <<<'SASS'
        $color: blue !default
        $color: red
        body
          color: $color
        SASS,
    <<<'CSS'
        body {
          color: red;
        }
        CSS,
  ],

  'global scope with !global' => [
    <<<'SASS'
        .block
          $color: blue !global
        body
          color: $color
        SASS,
    <<<'CSS'
        body {
          color: blue;
        }
        CSS,
  ],

  'variables in nested selectors' => [
    <<<'SASS'
        $color-light-gray: #ddd
        .comment
          &_wrapper
            width: 100%
            padding-left: 55px

            .comment_entry
              border: 1px solid $color-light-gray
        SASS,
    /** @lang text */ <<<'CSS'
        .comment_wrapper {
          width: 100%;
          padding-left: 55px;
        }
        .comment_wrapper .comment_entry {
          border: 1px solid #ddd;
        }
        CSS,
  ],
]);

dataset('sass interpolation', [
  'simple interpolation' => [
    <<<'SASS'
        $name: "header"
        .#{$name}
          color: red
        SASS,
    /** @lang text */ <<<'CSS'
        .header {
          color: red;
        }
        CSS,
  ],

  'nested interpolation' => [
    <<<'SASS'
        $name: "footer"
        #{"#{$name}"}
          color: green
        SASS,
    /** @lang text */ <<<'CSS'
        footer {
          color: green;
        }
        CSS,
  ],
]);

beforeEach(function () {
  $this->compiler = new Compiler();
});

describe('SCSS variables', function () {
  it('handles variables', function (string $scss, string $expected) {
    expect($this->compiler->compileString($scss))
      ->toEqualCss($expected);
  })->with('scss variables');

  it('supports variable interpolation', function (string $scss, string $expected) {
    expect($this->compiler->compileString($scss))
      ->toEqualCss($expected);
  })->with('scss interpolation');

  it('throws error on undefined variable', function () {
    $scss = <<<'SCSS'
        body {
            color: $undefined;
        }
        SCSS;

    expect(fn() => $this->compiler->compileString($scss))
      ->toThrow(CompilationException::class, 'Undefined variable: $undefined');
  });
});

describe('SASS variables', function () {
  it('handles variables', function (string $sass, string $expected) {
    expect($this->compiler->compileString($sass, Syntax::SASS))
      ->toEqualCss($expected);
  })->with('sass variables');

  it('supports variable interpolation', function (string $sass, string $expected) {
    expect($this->compiler->compileString($sass, Syntax::SASS))
      ->toEqualCss($expected);
  })->with('sass interpolation');

  it('throws error on undefined variable', function () {
    $sass = <<<'SASS'
        body
          color: $undefined
        SASS;

    expect(fn() => $this->compiler->compileString($sass, Syntax::SASS))
      ->toThrow(CompilationException::class, 'Undefined variable: $undefined');
  });
});
