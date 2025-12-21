<?php declare(strict_types=1);

use DartSass\Compiler;
use DartSass\Parsers\Syntax;

dataset('control flow scss styles', [
    '@if directive' => [
        <<<'SCSS'
        $theme: dark;
        body {
            @if $theme == dark {
                background: black;
            } @else {
                background: white;
            }
        }
        SCSS,
        <<<'CSS'
        body {
          background: black;
        }
        CSS,
    ],

    '@if with complex condition' => [
        <<<'SCSS'
        $value: 42;
        body {
            @if $value > 40 {
                font-size: 16px;
            }
        }
        SCSS,
        <<<'CSS'
        body {
          font-size: 16px;
        }
        CSS,
    ],

    '@each directive' => [
        <<<'SCSS'
        $colors: red, green, blue;
        @each $color in $colors {
            .box-#{$color} {
                background: $color;
            }
        }
        SCSS,
        /** @lang text */ <<<'CSS'
        .box-red {
          background: red;
        }
        .box-green {
          background: green;
        }
        .box-blue {
          background: blue;
        }
        CSS,
    ],

    '@for directive' => [
        <<<'SCSS'
        @for $i from 1 through 3 {
            .item-#{$i} {
                width: $i * 10px;
            }
        }
        SCSS,
        /** @lang text */ <<<'CSS'
        .item-1 {
          width: 10px;
        }
        .item-2 {
          width: 20px;
        }
        .item-3 {
          width: 30px;
        }
        CSS,
    ],

    '@for directive with @include' => [
        <<<'SCSS'
        @mixin button-style($color) {
            background-color: lighten($color, 10%);
            border: 1px solid $color;
        }

        @for $i from 1 through 2 {
            .button-#{$i} {
                @include button-style(#007bff);
            }
        }
        SCSS,
        /** @lang text */ <<<'CSS'
        .button-1 {
          background-color: #3395ff;
          border: 1px solid #007bff;
        }
        .button-2 {
          background-color: #3395ff;
          border: 1px solid #007bff;
        }
        CSS,
    ],

    '@while directive with variable update' => [
        <<<'SCSS'
        $i: 3;
        @while $i > 0 {
            .col-#{$i} {
                width: $i * 10px;
            }
            $i: $i - 1;
        }
        SCSS,
        /** @lang text */ <<<'CSS'
        .col-3 {
          width: 30px;
        }
        .col-2 {
          width: 20px;
        }
        .col-1 {
          width: 10px;
        }
        CSS,
    ],

    'media queries in control structures' => [
        <<<'SCSS'
        .article-container {
            @media (max-width: 767px) {
                grid-template-columns: 1fr !important;

                .featured-article,
                .article {
                    grid-column: span 1 !important;
                }
            }
        }
        SCSS,
        /** @lang text */ <<<'CSS'
        @media (max-width: 767px) {
          .article-container {
            grid-template-columns: 1fr !important;
          }
          .article-container .featured-article,
          .article-container .article {
            grid-column: span 1 !important;
          }
        }
        CSS,
    ],

    'compact media queries' => [
        <<<'SCSS'
        .frontpage_toolbar {
            @media (max-width: 768px) {
                .left {
                    display: none;
                }
            }
        }
        SCSS,
        /** @lang text */ <<<'CSS'
        @media (max-width: 768px) {
          .frontpage_toolbar .left {
            display: none;
          }
        }
        CSS,
    ],

    'media queries in nested selectors' => [
        <<<'SCSS'
        .footer {
            @media screen and (max-width: 500px) {
                display: grid;
                gap: 4px;
            }
        }
        SCSS,
        /** @lang text */ <<<'CSS'
        @media screen and (max-width: 500px) {
          .footer {
            display: grid;
            gap: 4px;
          }
        }
        CSS,
    ],

    'deeply nested media queries' => [
        <<<'SCSS'
        .lp_tabs {
          section {
            .add_option {
              @media screen and (max-width: 680px) {
                .plugin_options {
                  td:first-child {
                    display: none;
                  }
                }
              }
            }
          }
        }
        SCSS,
        <<<'CSS'
        @media screen and (max-width: 680px) {
          .lp_tabs section .add_option .plugin_options td:first-child {
            display: none;
          }
        }
        CSS,
    ],

    'unary boolean not operator' => [
        <<<'SCSS'
        $is-visible: false;
        $is-active: true;
        .sidebar {
            @if not $is-visible {
                display: none;
            }
            @if not ($is-active and $is-visible) {
                opacity: 0.5;
            }
        }
        SCSS,
        /** @lang text */ <<<'CSS'
        .sidebar {
          display: none;
          opacity: .5;
        }
        CSS,
    ],

    'while loop directive' => [
        <<<'SCSS'
        $counter: 1;
        @while $counter <= 15 {
          .while-class-#{$counter} {
            opacity: calc(0.1 * $counter);
            z-index: $counter;
            font-size: max(10px, calc(8px + $counter * 0.5px));
          }
          $counter: $counter + 1;
        }
        SCSS,
        /** @lang text */ <<<'CSS'
        .while-class-1 {
          opacity: .1;
          z-index: 1;
          font-size: 10px;
        }
        .while-class-2 {
          opacity: .2;
          z-index: 2;
          font-size: 10px;
        }
        .while-class-3 {
          opacity: .3;
          z-index: 3;
          font-size: 10px;
        }
        .while-class-4 {
          opacity: .4;
          z-index: 4;
          font-size: 10px;
        }
        .while-class-5 {
          opacity: .5;
          z-index: 5;
          font-size: 10.5px;
        }
        .while-class-6 {
          opacity: .6;
          z-index: 6;
          font-size: 11px;
        }
        .while-class-7 {
          opacity: .7;
          z-index: 7;
          font-size: 11.5px;
        }
        .while-class-8 {
          opacity: .8;
          z-index: 8;
          font-size: 12px;
        }
        .while-class-9 {
          opacity: .9;
          z-index: 9;
          font-size: 12.5px;
        }
        .while-class-10 {
          opacity: 1;
          z-index: 10;
          font-size: 13px;
        }
        .while-class-11 {
          opacity: 1.1;
          z-index: 11;
          font-size: 13.5px;
        }
        .while-class-12 {
          opacity: 1.2;
          z-index: 12;
          font-size: 14px;
        }
        .while-class-13 {
          opacity: 1.3;
          z-index: 13;
          font-size: 14.5px;
        }
        .while-class-14 {
          opacity: 1.4;
          z-index: 14;
          font-size: 15px;
        }
        .while-class-15 {
          opacity: 1.5;
          z-index: 15;
          font-size: 15.5px;
        }
        CSS,
    ],
]);

dataset('control flow sass styles', [
    '@if directive' => [
        <<<'SASS'
        $theme: dark
        body
          @if $theme == dark
            background: black
          @else
            background: white
        SASS,
        <<<'CSS'
        body {
          background: black;
        }
        CSS,
    ],

    '@if with complex condition' => [
        <<<'SASS'
        $value: 42
        body
          @if $value > 40
            font-size: 16px
        SASS,
        <<<'CSS'
        body {
          font-size: 16px;
        }
        CSS,
    ],

    '@each directive' => [
        <<<'SASS'
        $colors: red, green, blue
        @each $color in $colors
          .box-#{$color}
            background: $color
        SASS,
        /** @lang text */ <<<'CSS'
        .box-red {
          background: red;
        }
        .box-green {
          background: green;
        }
        .box-blue {
          background: blue;
        }
        CSS,
    ],

    '@for directive' => [
        <<<'SASS'
        @for $i from 1 through 3
          .item-#{$i}
            width: $i * 10px
        SASS,
        /** @lang text */ <<<'CSS'
        .item-1 {
          width: 10px;
        }
        .item-2 {
          width: 20px;
        }
        .item-3 {
          width: 30px;
        }
        CSS,
    ],

    '@for directive with @include' => [
        <<<'SASS'
        @mixin button-style($color)
          background-color: lighten($color, 10%)
          border: 1px solid $color

        @for $i from 1 through 2
          .button-#{$i}
            @include button-style(#007bff)
        SASS,
        /** @lang text */ <<<'CSS'
        .button-1 {
          background-color: #3395ff;
          border: 1px solid #007bff;
        }
        .button-2 {
          background-color: #3395ff;
          border: 1px solid #007bff;
        }
        CSS,
    ],

    '@while directive with variable update' => [
        <<<'SASS'
        $i: 3
        @while $i > 0
          .col-#{$i}
            width: $i * 10px
          $i: $i - 1
        SASS,
        /** @lang text */ <<<'CSS'
        .col-3 {
          width: 30px;
        }
        .col-2 {
          width: 20px;
        }
        .col-1 {
          width: 10px;
        }
        CSS,
    ],

    'media queries in control structures' => [
        <<<'SASS'
        .article-container
          @media (max-width: 767px)
            grid-template-columns: 1fr !important

            .featured-article,
            .article
              grid-column: span 1 !important
        SASS,
        /** @lang text */ <<<'CSS'
        @media (max-width: 767px) {
          .article-container {
            grid-template-columns: 1fr !important;
          }
          .article-container .featured-article,
          .article-container .article {
            grid-column: span 1 !important;
          }
        }
        CSS,
    ],

    'compact media queries' => [
        <<<'SASS'
        .frontpage_toolbar
          @media (max-width: 768px)
            .left
              display: none
        SASS,
        /** @lang text */ <<<'CSS'
        @media (max-width: 768px) {
          .frontpage_toolbar .left {
            display: none;
          }
        }
        CSS,
    ],

    'media queries in nested selectors' => [
        <<<'SASS'
        .footer
          @media screen and (max-width: 500px)
            display: grid
            gap: 4px
        SASS,
        /** @lang text */ <<<'CSS'
        @media screen and (max-width: 500px) {
          .footer {
            display: grid;
            gap: 4px;
          }
        }
        CSS,
    ],

    'deeply nested media queries' => [
        <<<'SASS'
        .lp_tabs
          section
            .add_option
              @media screen and (max-width: 680px)
                .plugin_options
                  td:first-child
                    display: none
        SASS,
        <<<'CSS'
        @media screen and (max-width: 680px) {
          .lp_tabs section .add_option .plugin_options td:first-child {
            display: none;
          }
        }
        CSS,
    ],

    'unary boolean not operator' => [
        <<<'SASS'
        $is-visible: false
        $is-active: true
        .sidebar
          @if not $is-visible
            display: none
          @if not ($is-active and $is-visible)
            opacity: 0.5
        SASS,
        /** @lang text */ <<<'CSS'
        .sidebar {
          display: none;
          opacity: .5;
        }
        CSS,
    ],

    'while loop directive' => [
        <<<'SASS'
        $counter: 1
        @while $counter <= 15
          .while-class-#{$counter}
            opacity: calc(0.1 * $counter)
            z-index: $counter
            font-size: max(10px, calc(8px + $counter * 0.5px))
          $counter: $counter + 1
        SASS,
        /** @lang text */ <<<'CSS'
        .while-class-1 {
          opacity: .1;
          z-index: 1;
          font-size: 10px;
        }
        .while-class-2 {
          opacity: .2;
          z-index: 2;
          font-size: 10px;
        }
        .while-class-3 {
          opacity: .3;
          z-index: 3;
          font-size: 10px;
        }
        .while-class-4 {
          opacity: .4;
          z-index: 4;
          font-size: 10px;
        }
        .while-class-5 {
          opacity: .5;
          z-index: 5;
          font-size: 10.5px;
        }
        .while-class-6 {
          opacity: .6;
          z-index: 6;
          font-size: 11px;
        }
        .while-class-7 {
          opacity: .7;
          z-index: 7;
          font-size: 11.5px;
        }
        .while-class-8 {
          opacity: .8;
          z-index: 8;
          font-size: 12px;
        }
        .while-class-9 {
          opacity: .9;
          z-index: 9;
          font-size: 12.5px;
        }
        .while-class-10 {
          opacity: 1;
          z-index: 10;
          font-size: 13px;
        }
        .while-class-11 {
          opacity: 1.1;
          z-index: 11;
          font-size: 13.5px;
        }
        .while-class-12 {
          opacity: 1.2;
          z-index: 12;
          font-size: 14px;
        }
        .while-class-13 {
          opacity: 1.3;
          z-index: 13;
          font-size: 14.5px;
        }
        .while-class-14 {
          opacity: 1.4;
          z-index: 14;
          font-size: 15px;
        }
        .while-class-15 {
          opacity: 1.5;
          z-index: 15;
          font-size: 15.5px;
        }
        CSS,
    ],
]);

beforeEach(function () {
  $this->compiler = new Compiler();
});

describe('SCSS', function () {
    it('compiles control flow styles', function (string $scss, string $expected) {
        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    })->with('control flow scss styles');
});

describe('SASS', function () {
    it('compiles control flow styles', function (string $sass, string $expected) {
        expect($this->compiler->compileString($sass, Syntax::SASS))
            ->toEqualCss($expected);
    })->with('control flow sass styles');
});
