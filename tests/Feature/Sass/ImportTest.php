<?php

declare(strict_types=1);

use DartSass\Compiler;

beforeEach(function () {
    $this->compiler = new Compiler(['loadPaths' => [__DIR__ . '/fixtures']]);
});

it('supports @import directive', function () {
    $scss = <<<'SCSS'
    @import "colors";

    .button {
        color: $primary;
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    .button {
      color: blue;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('supports @import directive with full filename', function () {
    $scss = <<<'SCSS'
    @import "_colors.scss";

    .button {
        color: $secondary;
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    .button {
      color: red;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('supports @import directive with CSS link', function () {
    $scss = <<<'SCSS'
    @import url('normalize.css');
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    @import url("normalize.css");
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('supports @import directive with https link', function () {
    $scss = <<<'SCSS'
    @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap');
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    @import url("https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap");
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('supports @import directive within @mixin', function () {
    $scss = <<<'SCSS'
    @mixin google-font($family) {
      @import url("http://fonts.googleapis.com/css?family=#{$family}");
    }

    @include google-font("Droid Sans");
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    @import url("http://fonts.googleapis.com/css?family=Droid Sans");
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('supports @import directive with media queries', function () {
    $scss = <<<'SCSS'
    @import "landscape" screen and (orientation: landscape);
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    @import "landscape" screen and (orientation: landscape);
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});
