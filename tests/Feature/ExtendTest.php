<?php

declare(strict_types=1);

use DartSass\Compiler;
use DartSass\Loaders\LoaderInterface;

beforeEach(function () {
    $this->loader = mock(LoaderInterface::class);
    $this->compiler = new Compiler(loader: $this->loader);
});

it('handles @extend directive', function () {
    $scss = <<<'SCSS'
    .base {
      color: blue;
    }
    .extended {
      @extend .base;
      font-size: 14px;
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    .base,
    .extended {
      color: blue;
    }
    .extended {
      font-size: 14px;
    }
    CSS;

    $this->loader->shouldReceive('load')->andReturn('');

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});

it('handles @extend directive (complex css)', function () {
    $scss = <<<'SCSS'
    $date_color: #888;

    .article-container {
      display: grid;
      gap: 1.25rem;

      .article {
        border-radius: .625rem;
        overflow: hidden;
        box-shadow: 0 .25rem .5rem rgba(0, 0, 0, 0.1);
        display: flex;
        flex-direction: column;

        img {
          width: 100%;
          height: auto;
        }

        .article-content {
          padding: 1.25rem;
        }

        .article-title {
          font-size: .625rem;
          font-weight: bold;
          margin-bottom: .625rem;
        }

        .article-date {
          font-size: .875rem;
          color: $date_color;
        }
      }

      .featured-article {
        @extend .article;

        .article-content {
          padding: 1.25rem;
        }

        .article-title {
          font-size: 1.5rem;
        }
      }
    }
    SCSS;

    $expected = /** @lang text */ <<<'CSS'
    .article-container {
      display: grid;
      gap: 1.25rem;
    }
    .article-container .article,
    .article-container .featured-article {
      border-radius: .625rem;
      overflow: hidden;
      box-shadow: 0 .25rem .5rem rgba(0, 0, 0, .1);
      display: flex;
      flex-direction: column;
    }
    .article-container .article img,
    .article-container .featured-article img {
      width: 100%;
      height: auto;
    }
    .article-container .article .article-content,
    .article-container .featured-article .article-content {
      padding: 1.25rem;
    }
    .article-container .article .article-title,
    .article-container .featured-article .article-title {
      font-size: .625rem;
      font-weight: bold;
      margin-bottom: .625rem;
    }
    .article-container .article .article-date,
    .article-container .featured-article .article-date {
      font-size: .875rem;
      color: #888;
    }
    .article-container .featured-article .article-content {
      padding: 1.25rem;
    }
    .article-container .featured-article .article-title {
      font-size: 1.5rem;
    }
    CSS;

    $this->loader->shouldReceive('load')->andReturn('');

    expect($this->compiler->compileString($scss))
        ->toEqualCss($expected);
});
