<?php

declare(strict_types=1);

use DartSass\Compiler;

beforeEach(function () {
    $this->compiler = new Compiler();
});

it('compiles mixins with include', function () {
    $scss = <<<'SCSS'
    @mixin bordered {
        border: 1px solid black;
    }
    .box {
        @include bordered;
        color: blue;
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    .box {
      border: 1px solid black;
      color: blue;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('handles mixins with arguments', function () {
    $scss = <<<'SCSS'
    @mixin pad($size: 10px) {
        padding: $size;
    }
    .container {
        @include pad(20px);
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    .container {
      padding: 20px;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('handles local mixins', function () {
    $scss = <<<'SCSS'
    @use "sass:meta";

    .test-keywords {
        @mixin test-kw($args...) {
            $kw: meta.keywords($args);
            @each $key, $val in $kw {
                .kw-#{$key} {
                    value: $val;
                }
            }
        }

        @include test-kw(
            $string: #080,
            $comment-val: #800
        );
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    .test-keywords .kw-string {
      value: #080;
    }
    .test-keywords .kw-comment-val {
      value: #800;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('handles complex mixins', function () {
    $oldMixin = <<<'SCSS'
    @mixin bRadius($topLeft: 6px, $topRight: null, $bottomRight: null, $bottomLeft: null) {
        @if $topRight == null and $bottomRight == null and $bottomLeft == null and $topLeft != null and $topLeft >= 0 {
            border-radius: $topLeft;
        } @else {
            $tl: if($topLeft == null or $topLeft < 0, 0, $topLeft);
            $tr: if($topRight == null or $topRight < 0, 0, $topRight);
            $br: if($bottomRight == null or $bottomRight < 0, 0, $bottomRight);
            $bl: if($bottomLeft == null or $bottomLeft < 0, 0, $bottomLeft);

            @if $tl == $tr and $tr == $br and $br == $bl {
                border-radius: $tl;
            } @else if $tl == $br and $tr == $bl {
                border-radius: $tl $tr;
            } @else if $tr == $bl {
                border-radius: $tl $tr $br;
            } @else {
                border-radius: $tl $tr $br $bl;
            }
        }
    }
    SCSS;

    $newMixin = <<<'SCSS'
    @mixin bRadius($topLeft: 6px, $topRight: null, $bottomRight: null, $bottomLeft: null) {
        @if $topRight == null and $bottomRight == null and $bottomLeft == null and $topLeft != null and $topLeft >= 0 {
            border-radius: $topLeft;
        } @else {
            $tl: if(sass($topLeft == null or $topLeft < 0): 0; else: $topLeft);
            $tr: if(sass($topRight == null or $topRight < 0): 0; else: $topRight);
            $br: if(sass($bottomRight == null or $bottomRight < 0): 0; else: $bottomRight);
            $bl: if(sass($bottomLeft == null or $bottomLeft < 0): 0; else: $bottomLeft);

            @if $tl == $tr and $tr == $br and $br == $bl {
                border-radius: $tl;
            } @else if $tl == $br and $tr == $bl {
                border-radius: $tl $tr;
            } @else if $tr == $bl {
                border-radius: $tl $tr $br;
            } @else {
                border-radius: $tl $tr $br $bl;
            }
        }
    }
    SCSS;

    $scss = <<<'SCSS'
    .single {
        @include bRadius(10px);
    }
    .all-same {
        @include bRadius(10px, 10px, 10px, 10px);
    }
    .all-zeros {
        @include bRadius(0, 0, 0, 0);
    }
    .two-values-1 {
        @include bRadius(8px, 12px, 8px, 12px);
    }
    .two-values-2 {
        @include bRadius(5px, 10px, 5px, 10px);
    }
    .two-values-3 {
        @include bRadius(null, 8px, null, 8px);
    }
    .three-values-1 {
        @include bRadius(8px, 12px, 15px, 12px);
    }
    .three-values-2 {
        @include bRadius(5px, 10px, 20px, 10px);
    }
    .three-values-3 {
        @include bRadius(8px, null, 12px, null);
    }
    .four-values-1 {
        @include bRadius(5px, 10px, 15px, 20px);
    }
    .four-values-2 {
        @include bRadius(8px, 12px, 0, 0);
    }
    .four-values-3 {
        @include bRadius(null, 8px, 12px, 16px);
    }
    .partial-null-1 {
        @include bRadius(8px, 12px);
    }
    .partial-null-2 {
        @include bRadius(null, 8px, 12px);
    }
    .partial-null-3 {
        @include bRadius(null, null, 8px, 12px);
    }
    .partial-null-4 {
        @include bRadius(8px, null, null, 12px);
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    .single {
      border-radius: 10px;
    }
    .all-same {
      border-radius: 10px;
    }
    .all-zeros {
      border-radius: 0;
    }
    .two-values-1 {
      border-radius: 8px 12px;
    }
    .two-values-2 {
      border-radius: 5px 10px;
    }
    .two-values-3 {
      border-radius: 0 8px;
    }
    .three-values-1 {
      border-radius: 8px 12px 15px;
    }
    .three-values-2 {
      border-radius: 5px 10px 20px;
    }
    .three-values-3 {
      border-radius: 8px 0 12px;
    }
    .four-values-1 {
      border-radius: 5px 10px 15px 20px;
    }
    .four-values-2 {
      border-radius: 8px 12px 0 0;
    }
    .four-values-3 {
      border-radius: 0 8px 12px 16px;
    }
    .partial-null-1 {
      border-radius: 8px 12px 0 0;
    }
    .partial-null-2 {
      border-radius: 0 8px 12px 0;
    }
    .partial-null-3 {
      border-radius: 0 0 8px 12px;
    }
    .partial-null-4 {
      border-radius: 8px 0 0 12px;
    }
    CSS;

    expect($this->compiler->compileString($oldMixin . $scss))
        ->toEqualCss($expected)
        ->and($this->compiler->compileString($newMixin . $scss))
        ->toEqualCss($expected);
});

it('handles different complex mixins', function () {
    $scss = <<<'SCSS'
    $primary-color: #007bff;
    $border-radius: 5px;
    $min-padding: min(10px, 2vw);
    @mixin button-style($color) {
        background-color: lighten($color, 5%);
        border: 1px solid saturate($color, 20%);
        border-radius: calc($border-radius + 2px);
        padding: max(8px, $min-padding) max(15px, calc($min-padding * 2));
        &:hover {
            background-color: desaturate($color, 10%);
            transform: scale(calc(1.05));
        }
    }
    header {
        @include button-style(lighten($primary-color, 10%));
    }
    SCSS;

    $expected = <<<'CSS'
    header {
      background-color: #4da2ff;
      border: 1px solid #3395ff;
      border-radius: 7px;
      padding: max(8px, min(10px, 2vw)) max(15px, calc(min(10px, 2vw) * 2));
    }
    header:hover {
      background-color: #3d95f5;
      transform: scale(1.05);
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('supports @content in mixins', function () {
    $scss = <<<'SCSS'
    @mixin media($query) {
        @media ($query) {
            @content;
        }
    }
    .box {
        @include media(min-width: 600px) {
            width: 50%;
        }
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    @media (min-width: 600px) {
      .box {
        width: 50%;
      }
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('compiles mixins within @media', function () {
    $scss = <<<'SCSS'
    @mixin bordered {
        border: 1px solid black;
    }
    .box {
        @media (min-width: 768px) {
            @include bordered;
            color: blue;
        }
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    @media (min-width: 768px) {
      .box {
        border: 1px solid black;
        color: blue;
      }
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('compiles mixin with @content directive', function () {
    $scss = <<<'SCSS'
    @mixin desktop {
        @media (min-width: 1024px) {
            @content;
        }
    }

    .button {
        width: 100%;

        @include desktop {
            width: auto;
            background: blue;
        }
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    .button {
      width: 100%;
    }
    @media (min-width: 1024px) {
      .button {
        width: auto;
        background: blue;
      }
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('compiles mixin with @for directive', function () {
    $scss = <<<'SCSS'
    $color-names: red, green, blue;
    $color-values: #ff0000, #00ff00, #0000ff;
    @for $i from 1 through length($color-names) {
      $name: nth($color-names, $i);
      $color: nth($color-values, $i);
      .color-#{"#{$name}"} {
        background-color: lighten($color, 10%);
        border: 2px solid saturate($color, 20%);
        &:hover {
          background-color: desaturate($color, 15%);
          transform: rotate(calc(var(--rotation, 0deg) + 5deg));
        }
      }
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    .color-red {
      background-color: #ff3333;
      border: 2px solid red;
    }
    .color-red:hover {
      background-color: #ec1313;
      transform: rotate(calc(var(--rotation, 0deg) + 5deg));
    }
    .color-green {
      background-color: #33ff33;
      border: 2px solid lime;
    }
    .color-green:hover {
      background-color: #13ec13;
      transform: rotate(calc(var(--rotation, 0deg) + 5deg));
    }
    .color-blue {
      background-color: #3333ff;
      border: 2px solid blue;
    }
    .color-blue:hover {
      background-color: #1313ec;
      transform: rotate(calc(var(--rotation, 0deg) + 5deg));
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('handles mixin with @media and url()', function () {
    $scss = <<<'SCSS'
    @mixin retina-background($file, $type, $width, $height) {
        background-image: url('#{$file}.#{$type}');

        @media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
            background-image: url('#{$file}@2x.#{$type}');
            background-size: $width $height;
        }
    }

    .logo {
        @include retina-background('logo', 'png', 200px, 100px);
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    .logo {
      background-image: url("logo.png");
    }
    @media (-webkit-min-device-pixel-ratio: 2),(min-resolution: 192dpi) {
      .logo {
        background-image: url("logo@2x.png");
        background-size: 200px 100px;
      }
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});


it('handles mixin with Unicode', function () {
    $scss = <<<'SCSS'
    @mixin define-emoji($name, $glyph) {
        span.emoji-#{$name} {
            font-family: IconFont;
            font-variant: normal;
            font-weight: normal;
            content: $glyph;
        }
    }

    @include define-emoji("women-holding-hands", "👭");
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    @charset "UTF-8";
    span.emoji-women-holding-hands {
      font-family: IconFont;
      font-variant: normal;
      font-weight: normal;
      content: "👭";
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('compiles mixin with declarations and @media', function () {
    $scss = <<<'SCSS'
    @mixin test {
        border: 1px solid black;
        @media (min-width: 600px) {
            color: red;
        }
    }
    .rule {
        @include test;
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    .rule {
      border: 1px solid black;
    }
    @media (min-width: 600px) {
      .rule {
        color: red;
      }
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});
