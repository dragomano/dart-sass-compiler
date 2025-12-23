<?php

declare(strict_types=1);

use DartSass\Normalizers\SassToScssNormalizer;

beforeEach(function () {
    $this->normalizer = new SassToScssNormalizer();
});

it('converts variables and simple rules', function () {
    $sass = <<<'SASS'
    $primary-color: #333
    $padding: 10px

    .nav
      color: $primary-color
      padding: $padding
    SASS;

    $expected = <<<'SCSS'
    $primary-color: #333;
    $padding: 10px;

    .nav {
      color: $primary-color;
      padding: $padding;
    }
    SCSS;

    expect($this->normalizer->normalize($sass))->toBe($expected);
});

it('converts nested selectors', function () {
    $sass = <<<'SASS'
    .nav
      color: red
      &:hover
        color: blue
    SASS;

    $expected = <<<'SCSS'
    .nav {
      color: red;
      &:hover {
        color: blue;
      }
    }
    SCSS;

    expect($this->normalizer->normalize($sass))->toBe($expected);
});

it('converts mixins and includes to valid scss', function () {
    $sass = <<<'SASS'
    =button
      padding: 10px
      color: white

    .button
      +button
    SASS;

    $expected = <<<'SCSS'
    @mixin button {
      padding: 10px;
      color: white;
    }

    .button {
      @include button;
    }
    SCSS;

    expect($this->normalizer->normalize($sass))->toBe($expected);
});

it('converts mixins with parameters', function () {
    $sass = <<<'SASS'
    =button($size, $color)
      padding: $size
      background: $color

    .btn
      +button(10px, blue)
    SASS;

    $expected = <<<'SCSS'
    @mixin button($size, $color) {
      padding: $size;
      background: $color;
    }

    .btn {
      @include button(10px, blue);
    }
    SCSS;

    expect($this->normalizer->normalize($sass))->toBe($expected);
});

it('converts control directives', function () {
    $sass = <<<'SASS'
    $flag: true

    .container
      @if $flag
        color: green
      @else
        color: red
    SASS;

    $expected = <<<'SCSS'
    $flag: true;

    .container {
      @if $flag {
        color: green;
      }
      @else {
        color: red;
      }
    }
    SCSS;

    expect($this->normalizer->normalize($sass))->toBe($expected);
});

it('keeps comments and blank lines', function () {
    $sass = <<<'SASS'
    // Comment
    .container
      // inner comment
      color: red

    .footer
      color: blue
    SASS;

    $expected = <<<'SCSS'
    // Comment
    .container {
      // inner comment
      color: red;
    }

    .footer {
      color: blue;
    }
    SCSS;

    expect($this->normalizer->normalize($sass))->toBe($expected);
});

it('handles multiple nesting levels', function () {
    $sass = <<<'SASS'
    .a
      .b
        .c
          color: red
    SASS;

    $expected = <<<'SCSS'
    .a {
      .b {
        .c {
          color: red;
        }
      }
    }
    SCSS;

    expect($this->normalizer->normalize($sass))->toBe($expected);
});

it('handles @media blocks', function () {
    $sass = <<<'SASS'
    .container
      @media screen
        color: red
    SASS;

    $expected = <<<'SCSS'
    .container {
      @media screen {
        color: red;
      }
    }
    SCSS;

    expect($this->normalizer->normalize($sass))->toBe($expected);
});

it('handles standalone properties', function () {
    $sass = <<<'SASS'
    a
      color: red
      margin: 0
    SASS;

    $expected = <<<'SCSS'
    a {
      color: red;
      margin: 0;
    }
    SCSS;

    expect($this->normalizer->normalize($sass))->toBe($expected);
});

it('handles multiline comments', function () {
    $sass = <<<'SASS'
    /* This is a
       multiline comment */
    .box
      color: red
    SASS;

    $expected = <<<'SCSS'
    /* This is a
       multiline comment */
    .box {
      color: red;
    }
    SCSS;

    expect($this->normalizer->normalize($sass))->toBe($expected);
});

it('handles single line directives', function () {
    $sass = <<<'SASS'
    @import "variables"
    @use "mixins"
    @forward "base"

    .btn
      @extend %button-base
      color: blue
    SASS;

    $expected = <<<'SCSS'
    @import "variables";
    @use "mixins";
    @forward "base";

    .btn {
      @extend %button-base;
      color: blue;
    }
    SCSS;

    expect($this->normalizer->normalize($sass))->toBe($expected);
});

it('handles @function blocks', function () {
    $sass = <<<'SASS'
    @function double($n)
      @return $n * 2

    .box
      width: double(50px)
    SASS;

    $expected = <<<'SCSS'
    @function double($n) {
      @return $n * 2;
    }

    .box {
      width: double(50px);
    }
    SCSS;

    expect($this->normalizer->normalize($sass))->toBe($expected);
});

it('handles keyframes with percentages', function () {
    $sass = <<<'SASS'
    @keyframes fade
      0%
        opacity: 0
      100%
        opacity: 1
    SASS;

    $expected = <<<'SCSS'
    @keyframes fade {
      0% {
        opacity: 0;
      }
      100% {
        opacity: 1;
      }
    }
    SCSS;

    expect($this->normalizer->normalize($sass))->toBe($expected);
});

it('handles pseudo-classes and pseudo-elements', function () {
    $sass = <<<'SASS'
    a
      color: blue
      &:hover
        color: red
      &::before
        content: "→"
    SASS;

    $expected = <<<'SCSS'
    a {
      color: blue;
      &:hover {
        color: red;
      }
      &::before {
        content: "→";
      }
    }
    SCSS;

    expect($this->normalizer->normalize($sass))->toBe($expected);
});

it('handles nested properties', function () {
    $sass = <<<'SASS'
    .text
      font:
        size: 14px
        weight: bold
        family: Arial
    SASS;

    $expected = <<<'SCSS'
    .text {
      font: {
        size: 14px;
        weight: bold;
        family: Arial;
      }
    }
    SCSS;

    expect($this->normalizer->normalize($sass))->toBe($expected);
});

it('handles @for loops', function () {
    $sass = <<<'SASS'
    @for $i from 1 through 3
      .col-#{$i}
        width: 100% / $i
    SASS;

    $expected = <<<'SCSS'
    @for $i from 1 through 3 {
      .col-#{$i} {
        width: 100% / $i;
      }
    }
    SCSS;

    expect($this->normalizer->normalize($sass))->toBe($expected);
});

it('handles @each loops', function () {
    $sass = <<<'SASS'
    @each $color in red, green, blue
      .#{$color}
        background: $color
    SASS;

    $expected = <<<'SCSS'
    @each $color in red, green, blue {
      .#{$color} {
        background: $color;
      }
    }
    SCSS;

    expect($this->normalizer->normalize($sass))->toBe($expected);
});

it('handles @while loops', function () {
    $sass = <<<'SASS'
    $i: 6
    @while $i > 0
      .col-#{$i}
        width: $i * 10%
      $i: $i - 2
    SASS;

    $expected = <<<'SCSS'
    $i: 6;
    @while $i > 0 {
      .col-#{$i} {
        width: $i * 10%;
      }
      $i: $i - 2;
    }
    SCSS;

    expect($this->normalizer->normalize($sass))->toBe($expected);
});

it('handles placeholder selectors', function () {
    $sass = <<<'SASS'
    %button-base
      padding: 10px
      border: none

    .btn
      @extend %button-base
      color: blue
    SASS;

    $expected = <<<'SCSS'
    %button-base {
      padding: 10px;
      border: none;
    }

    .btn {
      @extend %button-base;
      color: blue;
    }
    SCSS;

    expect($this->normalizer->normalize($sass))->toBe($expected);
});

it('handles attribute selectors', function () {
    $sass = <<<'SASS'
    [data-active]
      color: green
      &[data-type="primary"]
        font-weight: bold
    SASS;

    $expected = <<<'SCSS'
    [data-active] {
      color: green;
      &[data-type="primary"] {
        font-weight: bold;
      }
    }
    SCSS;

    expect($this->normalizer->normalize($sass))->toBe($expected);
});

it('handles @charset directive', function () {
    $sass = <<<'SASS'
    @charset "UTF-8"

    .box
      content: "→"
    SASS;

    $expected = <<<'SCSS'
    @charset "UTF-8";

    .box {
      content: "→";
    }
    SCSS;

    expect($this->normalizer->normalize($sass))->toBe($expected);
});

it('handles complex nesting with multiple features', function () {
    $sass = <<<'SASS'
    =flex-center
      display: flex
      align-items: center
      justify-content: center

    .container
      +flex-center
      padding: 20px

      .header
        font:
          size: 24px
          weight: bold

        @media (max-width: 768px)
          font:
            size: 18px

      .content
        // Inner comment
        color: #333

        &:hover
          color: #000
    SASS;

    $expected = <<<'SCSS'
    @mixin flex-center {
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .container {
      @include flex-center;
      padding: 20px;
      .header {
        font: {
          size: 24px;
          weight: bold;
        }
        @media (max-width: 768px) {
          font: {
            size: 18px;
          }
        }
      }
      .content {
        // Inner comment
        color: #333;
        &:hover {
          color: #000;
        }
      }
    }
    SCSS;

    expect($this->normalizer->normalize($sass))->toBe($expected);
});

it('handles pseudo-classes as block headers', function () {
    $sass = <<<'SASS'
    body:hover
      color: red

    body:active
      background: blue
    SASS;

    $expected = <<<'SCSS'
    body:hover {
      color: red;
    }

    body:active {
      background: blue;
    }
    SCSS;

    expect($this->normalizer->normalize($sass))->toBe($expected);
});
