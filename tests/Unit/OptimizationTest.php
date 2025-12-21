<?php declare(strict_types=1);

use DartSass\Compiler;
use DartSass\Parsers\Syntax;

dataset('scss output styles', [
  'expanded SCSS' => [
    <<<'SCSS'
        body {
            color: blue;
            font-size: 16px;
        }
        SCSS,
    <<<'CSS'
        body {
          color: blue;
          font-size: 16px;
        }
        CSS,
    'expanded',
  ],

  'compressed SCSS' => [
    <<<'SCSS'
        body {
            color: blue;
            font-size: 16px;
        }
        SCSS,
    <<<'CSS'
        body{color:blue;font-size:16px}
        CSS,
    'compressed',
  ],
]);

describe('SCSS output styles', function () {
  it('compiles SCSS with style', function (string $scss, string $expected, string $style) {
    $compiler = new Compiler(['style' => $style]);

    expect($compiler->compileString($scss))->toEqualCss($expected);
  })->with('scss output styles');
});

dataset('sass output styles', [
  'expanded SASS' => [
    <<<'SASS'
        body
          color: blue
          font-size: 16px
        SASS,
    <<<'CSS'
        body {
          color: blue;
          font-size: 16px;
        }
        CSS,
    'expanded',
  ],

  'compressed SASS' => [
    <<<'SASS'
        body
          color: blue
          font-size: 16px
        SASS,
    <<<'CSS'
        body{color:blue;font-size:16px}
        CSS,
    'compressed',
  ],
]);

describe('SASS output styles', function () {
  it('compiles SASS with style', function (string $sass, string $expected, string $style) {
    $compiler = new Compiler(['style' => $style]);

    expect($compiler->compileString($sass, Syntax::SASS))->toEqualCss($expected);
  })->with('sass output styles');
});
