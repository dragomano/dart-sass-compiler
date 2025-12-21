<?php declare(strict_types=1);

use DartSass\Compiler;

beforeEach(function () {
    $this->compiler = new Compiler();
});

it('handles complex styles with multiple integrated features', function () {
    $scss = <<<'SCSS'
    $primary-color: #007bff;
    $font-size: 16px;

    .btn {
        background-color: $primary-color;
        font-size: $font-size;

        &:hover {
            background-color: darken($primary-color, 10%);
        }
    }

    @for $i from 1 through 3 {
        .col-#{$i} {
            width: 100% / 3 * $i;
        }
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    .btn {
      background-color: #007bff;
      font-size: 16px;
    }
    .btn:hover {
      background-color: #0062cc;
    }
    .col-1 {
      width: 33.333333333333%;
    }
    .col-2 {
      width: 66.666666666667%;
    }
    .col-3 {
      width: 100%;
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('handles complex integration with multiple features', function () {
    $scss = <<<'SCSS'
    $primary-color: #007bff;
    $secondary-color: #6c757d;
    $font-size: 14px;
    $border-radius: 5px;
    $max-width: max(800px, 50vw);
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
    @for $i from 1 through 1 {
      .for-class-#{$i} {
        width: calc(10px * $i);
        height: min(50px, calc(20px + $i * 2px));
        @include button-style(saturate($secondary-color, calc($i * 2%)));
        border-radius: clamp(3px, calc($i * 2px), 15px);
        filter: hue-rotate(calc($i * 18deg));
      }
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    .for-class-1 {
      background-color: #76828d;
      border: 1px solid #537696;
      padding: max(8px, min(10px, 2vw)) max(15px, calc(min(10px, 2vw) * 2));
      width: 10px;
      height: 22px;
      border-radius: 3px;
      filter: hue-rotate(18deg);
    }
    .for-class-1:hover {
      background-color: #757575;
      transform: scale(1.05);
    }
    CSS;

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('handles complex SCSS with list/color modules', function () {
  $scss = <<<'SCSS'
  @use 'sass:list';
  @use 'sass:color';

  $color-names: red, green, blue;
  $color-values: #ff0000, #00ff00, #0000ff;
  @for $i from 1 through list.length($color-names) {
    $name: list.nth($color-names, $i);
    $color: list.nth($color-values, $i);
    .color-#{"#{$name}"} {
      background-color: color.adjust($color, $lightness: 10%);
      border: 2px solid color.adjust($color, $saturation: 20%);
      &:hover {
        background-color: color.adjust($color, $saturation: -15%);
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
