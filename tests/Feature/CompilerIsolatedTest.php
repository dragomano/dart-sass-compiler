<?php

declare(strict_types = 1);

use DartSass\Compiler;

beforeEach(function () {
  $this->compiler = new Compiler();
});

it('compiles SCSS in isolated context', function () {
  $scss = <<<'SCSS'
  $primary-color: #007bff;
  $font-size: 16px;

  @if $primary-color == #007bff {
      .btn-primary {
          background-color: $primary-color;
          font-size: $font-size;
      }
  }

  @for $i from 1 through 5 {
      .col-#{$i} {
          width: 100% / 5 * $i;
      }
  }

  $counter: 0;
  @while $counter < 3 {
      .item-#{$counter} {
          margin: $counter * 10px;
      }
      $counter: $counter + 1;
  }
  SCSS;

  $expectedCss = /** @lang text */ <<<'CSS'
  .btn-primary {
    background-color: #007bff;
    font-size: 16px;
  }
  .col-1 {
    width: 20%;
  }
  .col-2 {
    width: 40%;
  }
  .col-3 {
    width: 60%;
  }
  .col-4 {
    width: 80%;
  }
  .col-5 {
    width: 100%;
  }
  .item-0 {
    margin: 0;
  }
  .item-1 {
    margin: 10px;
  }
  .item-2 {
    margin: 20px;
  }
  CSS;

  expect($this->compiler->compileInIsolatedContext($scss))
    ->toEqualCss($expectedCss);
});
