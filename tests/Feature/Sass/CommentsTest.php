<?php

declare(strict_types=1);

use DartSass\Compiler;
use DartSass\Loaders\LoaderInterface;

dataset('comment styles', [
    'multiline comment at top' => [
        <<<'SCSS'
        /* This is a multiline comment
            that spans multiple lines */
        body {
            color: red;
        }
        SCSS,
        <<<'CSS'
        /* This is a multiline comment
        that spans multiple lines */
        body {
          color: red;
        }
        CSS,
    ],

    'single line comment removed' => [
        <<<'SCSS'
        // This is a single line comment
        body {
            color: blue;
        }
        SCSS,
        <<<'CSS'
        body {
          color: blue;
        }
        CSS,
    ],

    'comment inside rule' => [
        <<<'SCSS'
        body {
            /* Color property */
            color: green;
        }
        SCSS,
        <<<'CSS'
        body {
          /* Color property */
          color: green;
        }
        CSS,
    ],

    'comment between rules' => [
        <<<'SCSS'
        .class1 {
            color: yellow;
        }
        /* Separator comment */
        .class2 {
            color: purple;
        }
        SCSS,
        /** @lang text */ <<<'CSS'
        .class1 {
          color: yellow;
        }
        /* Separator comment */
        .class2 {
          color: purple;
        }
        CSS,
    ],

    'mixed comments' => [
        <<<'SCSS'
        /* Multiline comment at start */
        // Single line comment
        body {
            color: black;
            /* Inline comment */
            font-size: 14px;
        }
        // Another single line
        /* Final multiline */
        SCSS,
        <<<'CSS'
        /* Multiline comment at start */
        body {
          color: black;
          /* Inline comment */
          font-size: 14px;
        }
        /* Final multiline */
        CSS,
    ],

    'important comments preserved' => [
        <<<'SCSS'
        /*! Important comment at start */
        body {
            color: red;
        }
        /*! Another important comment */
        SCSS,
        <<<'CSS'
        /*! Important comment at start */
        body {
          color: red;
        }
        /*! Another important comment */
        CSS,
    ],
]);

dataset('compressed comment styles', [
    'multiline comment removed' => [
        <<<'SCSS'
        /* This is a multiline comment
        that spans multiple lines */
        body {
            color: red;
        }
        SCSS,
        <<<'CSS'
        body{color:red}
        CSS,
    ],

    'single line comment removed' => [
        <<<'SCSS'
        // This is a single line comment
        body {
            color: blue;
        }
        SCSS,
        <<<'CSS'
        body{color:blue}
        CSS,
    ],

    'comment inside rule removed' => [
        <<<'SCSS'
        body {
            /* Color property */
            color: green;
        }
        SCSS,
        <<<'CSS'
        body{color:green}
        CSS,
    ],

    'important comments preserved' => [
        <<<'SCSS'
        /*! Important comment at start */
        body {
            color: red;
        }
        /*! Another important comment */
        SCSS,
        <<<'CSS'
        /*! Important comment at start */ body{color:red}/*! Another important comment */
        CSS,
    ],
]);

beforeEach(function () {
    $this->loader   = mock(LoaderInterface::class);
    $this->compiler = new Compiler(loader: $this->loader);

    $this->compressedCompiler = new Compiler(['style' => 'compressed'], $this->loader);
});

describe('Comments', function () {
    it('preserves multiline comments and removes single line comments', function (string $scss, string $expected) {
        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    })->with('comment styles');

    it('removes regular comments but preserves important comments in compressed mode', function (string $scss, string $expected) {
        expect($this->compressedCompiler->compileString($scss))
            ->toEqualCss($expected);
    })->with('compressed comment styles');
});
