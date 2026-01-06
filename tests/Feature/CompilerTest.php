<?php

declare(strict_types=1);

use DartSass\Compiler;
use DartSass\Exceptions\CompilationException;
use DartSass\Exceptions\InvalidColorException;
use DartSass\Loaders\LoaderInterface;
use DartSass\Parsers\Syntax;

dataset('scss styles', [
  'basic SCSS' => [
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
  ],

  'identifier token' => [
    <<<'SCSS'
    body {
        display: block;
        float: left;
    }
    SCSS,
    <<<'CSS'
    body {
      display: block;
      float: left;
    }
    CSS,
  ],

  'string token' => [
    <<<'SCSS'
    body {
        content: "text content";
        background-image: url("data:image/png;base64,iVBORw0KGgo=");
    }
    SCSS,
    <<<'CSS'
    body {
      content: "text content";
      background-image: url("data:image/png;base64,iVBORw0KGgo=");
    }
    CSS,
  ],

  'number token' => [
    <<<'SCSS'
    body {
        width: 100px;
        height: 50%;
        margin: 1.5em;
    }
    SCSS,
    <<<'CSS'
    body {
      width: 100px;
      height: 50%;
      margin: 1.5em;
    }
    CSS,
  ],

  'operator token' => [
    <<<'SCSS'
    body {
        width: calc(100% - 20px);
        opacity: 0.5 + 0.3;
    }
    SCSS,
    <<<'CSS'
    body {
      width: calc(100% - 20px);
      opacity: .8;
    }
    CSS,
  ],

  'variable token' => [
    <<<'SCSS'
    $color: blue;
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

  'at-rule token' => [
    <<<'SCSS'
    @media screen {
        body {
            color: red;
        }
    }
    SCSS,
    <<<'CSS'
    @media screen {
      body {
        color: red;
      }
    }
    CSS,
  ],

  'pseudo-class' => [
    <<<'SCSS'
    body:hover {
        color: red;
    }
    SCSS,
    <<<'CSS'
    body:hover {
      color: red;
    }
    CSS,
  ],
]);

dataset('sass styles', [
  'basic SASS' => [
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
  ],

  'identifier token' => [
    <<<'SASS'
    body
      display: block
      float: left
    SASS,
    <<<'CSS'
    body {
      display: block;
      float: left;
    }
    CSS,
  ],

  'string token' => [
    <<<'SASS'
    body
      content: "text content"
      background-image: url("data:image/png;base64,iVBORw0KGgo=");
    SASS,
    <<<'CSS'
    body {
      content: "text content";
      background-image: url("data:image/png;base64,iVBORw0KGgo=");
    }
    CSS,
  ],

  'number token' => [
    <<<'SASS'
    body
      width: 100px
      height: 50%
      margin: 1.5em
    SASS,
    <<<'CSS'
    body {
      width: 100px;
      height: 50%;
      margin: 1.5em;
    }
    CSS,
  ],

  'operator token' => [
    <<<'SASS'
    body
      width: calc(100% - 20px)
      opacity: 0.5 + 0.3
    SASS,
    <<<'CSS'
    body {
      width: calc(100% - 20px);
      opacity: .8;
    }
    CSS,
  ],

  'variable token' => [
    <<<'SASS'
    $color: blue
    body
      color: $color
    SASS,
    <<<'CSS'
    body {
      color: blue;
    }
    CSS,
  ],

  'at-rule token' => [
    <<<'SASS'
    @media screen
      body
        color: red
    SASS,
    <<<'CSS'
    @media screen {
      body {
        color: red;
      }
    }
    CSS,
  ],

  'pseudo-class' => [
    <<<'SASS'
    body:hover
      color: red
    SASS,
    <<<'CSS'
    body:hover {
      color: red;
    }
    CSS,
  ],
]);

beforeEach(function () {
    $this->loader   = mock(LoaderInterface::class);
    $this->compiler = new Compiler(loader: $this->loader);
});

describe('SCSS', function () {
    it('compiles SCSS styles', function (string $scss, string $expected) {
        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    })->with('scss styles');

    it('compiles file input correctly (SCSS)', function () {
        $scss = <<<'SCSS'
        $color: #333;

        body {
          color: $color;
        }
        SCSS;

        $expectedCss = <<<'CSS'
        body {
          color: #333;
        }
        CSS;

        $this->loader
            ->shouldReceive('load')
            ->with('virtual.scss')
            ->andReturn($scss);

        expect($this->compiler->compileFile('virtual.scss'))
            ->toEqualCss($expectedCss);
    });

    it('throws syntax error on invalid SCSS syntax', function () {
        $scss = <<<'SCSS'
        body {
            color: #zzz;
        }
        SCSS;

        expect(fn() => $this->compiler->compileString($scss))
            ->toThrow(InvalidColorException::class);
    });

    it('throws compilation error on missing SCSS file', function () {
        expect(fn() => (new Compiler())->compileFile('nonexistent.scss'))
            ->toThrow(CompilationException::class, 'File not found: nonexistent.scss');
    });
});

describe('SASS', function () {
    it('compiles SASS styles', function (string $sass, string $expected) {
        expect($this->compiler->compileString($sass, Syntax::SASS))
            ->toEqualCss($expected);
    })->with('sass styles');
});
