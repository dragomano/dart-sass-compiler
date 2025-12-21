<?php declare(strict_types=1);

use DartSass\Compiler;

beforeEach(function () {
    $this->compiler = new Compiler();
});

it('handles basic ID selector', function () {
    $scss = <<<'SCSS'
    #header {
        background-color: blue;
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    #header {
      background-color: blue;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('handles nested ID selector', function () {
    $scss = <<<'SCSS'
    #main {
        .content {
            padding: 20px;
        }
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    #main .content {
      padding: 20px;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('handles ID selector with pseudo-class', function () {
    $scss = <<<'SCSS'
    #navigation {
        &:hover {
            background-color: red;
        }
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    #navigation:hover {
      background-color: red;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('handles ID selector with attribute selector', function () {
    $scss = <<<'SCSS'
    #form {
        [type="text"] {
            border: 1px solid gray;
        }
    }
    SCSS;

    $expected = <<<'CSS'
    #form [type=text] {
      border: 1px solid gray;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('handles multiple ID selectors', function () {
    $scss = <<<'SCSS'
    #header, #footer {
        background-color: black;
        color: white;
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    #header,
    #footer {
      background-color: black;
      color: white;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('handles ID selector with descendant combinator', function () {
    $scss = <<<'SCSS'
    #container {
        > .item {
            margin: 10px;
        }
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    #container > .item {
      margin: 10px;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('handles ID selector with child combinator', function () {
    $scss = <<<'SCSS'
    #sidebar {
        ul {
            list-style: none;

            li {
                padding: 5px;
            }
        }
    }
    SCSS;

    $expected = <<<'CSS'
    #sidebar ul {
      list-style: none;
    }
    #sidebar ul li {
      padding: 5px;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('handles ID selector with interpolation', function () {
    $scss = <<<'SCSS'
    $id: "main-content";

    ##{$id} {
        font-size: 16px;
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    #main-content {
      font-size: 16px;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('handles ID selector starting with number-like name', function () {
    $scss = <<<'SCSS'
    #main123 {
        width: 100%;
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    #main123 {
      width: 100%;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('handles ID selector with underscores and hyphens', function () {
    $scss = <<<'SCSS'
    #main-content_area {
        padding: 20px;
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    #main-content_area {
      padding: 20px;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('handles ID selector with complex nesting', function () {
    $scss = <<<'SCSS'
    #app {
        .header {
            background: #333;

            .logo {
                width: 100px;
            }
        }

        .content {
            padding: 20px;

            &:hover {
                background: #f0f0f0;
            }
        }
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    #app .header {
      background: #333;
    }
    #app .header .logo {
      width: 100px;
    }
    #app .content {
      padding: 20px;
    }
    #app .content:hover {
      background: #f0f0f0;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});
