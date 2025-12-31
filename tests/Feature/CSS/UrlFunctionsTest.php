<?php

declare(strict_types=1);

use DartSass\Compiler;

beforeEach(function () {
    $this->compiler = new Compiler();
});

describe('supports url() function', function () {
    it('handles url with single quotes', function () {
        $scss = <<<'SCSS'
        .basic-url {
            background-image: url('image.jpg');
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .basic-url {
          background-image: url("image.jpg");
        }
        CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    });

    it('handles url with double quotes', function () {
        $scss = <<<'SCSS'
        .basic-url {
            background-image: url("image.jpg");
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .basic-url {
          background-image: url("image.jpg");
        }
        CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    });

    it('handles url without quotes', function () {
        $scss = <<<'SCSS'
        .basic-url {
            background-image: url(image.jpg);
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .basic-url {
          background-image: url(image.jpg);
        }
        CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    });

    it('handles url with path containing slashes', function () {
        $scss = <<<'SCSS'
        .complex-url {
            background-image: url('path/to/image.png');
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .complex-url {
          background-image: url("path/to/image.png");
        }
        CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    });

    it('handles url without quotes with path containing slashes', function () {
        $scss = <<<'SCSS'
        .complex-url {
            background-image: url(path/to/image.png);
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .complex-url {
          background-image: url(path/to/image.png);
        }
        CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    });

    it('handles url with additional properties', function () {
        $scss = <<<'SCSS'
        .url-with-params {
            background-image: url('image.jpg') center;
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .url-with-params {
          background-image: url("image.jpg") center;
        }
        CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    });

    it('handles nested url', function () {
        $scss = <<<'SCSS'
        .nested-url {
            .class {
                background-image: url(image.jpg);
            }
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .nested-url .class {
          background-image: url(image.jpg);
        }
        CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    });

    it('handles multiple url formats in same selector', function () {
        $scss = <<<'SCSS'
        .multiple-urls {
            background-image: url('image.jpg');
            background-image: url("image.jpg");
            background-image: url(image.jpg);
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .multiple-urls {
          background-image: url(image.jpg);
        }
        CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    });

    it('handles url with absolute path', function () {
        $scss = <<<'SCSS'
        .absolute-url {
            background-image: url('/images/logo.png');
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .absolute-url {
          background-image: url("/images/logo.png");
        }
        CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    });

    it('handles url with query parameters', function () {
        $scss = <<<'SCSS'
        .query-url {
            background-image: url('image.jpg?v=1.0');
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .query-url {
          background-image: url("image.jpg?v=1.0");
        }
        CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    });

    it('handles url with fragment', function () {
        $scss = <<<'SCSS'
        .fragment-url {
            background-image: url('image.jpg#section');
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .fragment-url {
          background-image: url("image.jpg#section");
        }
        CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    });

    it('handles url in different CSS properties', function () {
        $scss = <<<'SCSS'
        .url-properties {
            background: url('bg.jpg');
            background-image: url('img.jpg');
            content: url('icon.png');
            list-style-image: url('bullet.png');
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .url-properties {
          background: url("bg.jpg");
          background-image: url("img.jpg");
          content: url("icon.png");
          list-style-image: url("bullet.png");
        }
        CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    });

    it('handles url with spaces in filename', function () {
        $scss = <<<'SCSS'
        .spaces-url {
            background-image: url('my image.jpg');
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .spaces-url {
          background-image: url("my image.jpg");
        }
        CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    });

    it('handles url with special characters', function () {
        $scss = <<<'SCSS'
        .special-url {
            background-image: url('image@2x.jpg');
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .special-url {
          background-image: url("image@2x.jpg");
        }
        CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    });

    it('handles relative URL with relative path', function () {
        $scss = <<<'SCSS'
        .relative-url {
            background-image: url('./images/photo.jpg');
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .relative-url {
          background-image: url("./images/photo.jpg");
        }
        CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    });

    it('handles relative URL with parent directory', function () {
        $scss = <<<'SCSS'
        .relative-url {
            background-image: url('../assets/logo.png');
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .relative-url {
          background-image: url("../assets/logo.png");
        }
        CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    });

    it('handles absolute URL with protocol', function () {
        $scss = <<<'SCSS'
        .absolute-url {
            background-image: url('https://example.com/image.jpg');
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .absolute-url {
          background-image: url("https://example.com/image.jpg");
        }
        CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    });

    it('handles URL with data URI scheme', function () {
        $scss = <<<'SCSS'
        .data-url {
            background-image: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .data-url {
          background-image: url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==");
        }
        CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    });

    it('handles URL with FTP protocol', function () {
        $scss = <<<'SCSS'
        .ftp-url {
            background-image: url('ftp://example.com/image.jpg');
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .ftp-url {
          background-image: url("ftp://example.com/image.jpg");
        }
        CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    });

    it('handles relative URL with complex path', function () {
        $scss = <<<'SCSS'
        .complex-relative {
            background-image: url('../../../images/icons/arrow.png');
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .complex-relative {
          background-image: url("../../../images/icons/arrow.png");
        }
        CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    });

    it('handles absolute URL with port number', function () {
        $scss = <<<'SCSS'
        .port-url {
            background-image: url('http://localhost:8080/images/logo.png');
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .port-url {
          background-image: url("http://localhost:8080/images/logo.png");
        }
        CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    });

    it('handles relative URL with query parameters', function () {
        $scss = <<<'SCSS'
        .relative-query {
            background-image: url('./image.jpg?v=1.0&format=webp');
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .relative-query {
          background-image: url("./image.jpg?v=1.0&format=webp");
        }
        CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    });

    it('handles mixed URL formats in single rule', function () {
        $scss = <<<'SCSS'
        .mixed-urls {
            background: url('./bg.jpg');
            background-image: url('https://cdn.example.com/banner.png');
            list-style-image: url('../icons/bullet.png');
            content: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg"/>');
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .mixed-urls {
          background: url("./bg.jpg");
          background-image: url("https://cdn.example.com/banner.png");
          list-style-image: url("../icons/bullet.png");
          content: url("data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg'/>");
        }
        CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    });

    it('handles variable interpolation in url()', function () {
        $scss = <<<'SCSS'
        $image-path: '../images';

        .variable-interpolation {
            background: url('#{$image-path}/background.jpg');
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .variable-interpolation {
          background: url("../images/background.jpg");
        }
        CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    });

    it('handles CDN URLs with variable interpolation', function () {
        $scss = <<<'SCSS'
        $cdn-url: 'https://cdn.example.com';

        .cdn-variable {
            background: url('#{$cdn-url}/assets/hero.png');
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .cdn-variable {
          background: url("https://cdn.example.com/assets/hero.png");
        }
        CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    });

    it('handles string concatenation with + operator in url()', function () {
        $scss = <<<'SCSS'
        $image-path: '../images';

        .string-concatenation {
            background: url($image-path + '/logo.svg');
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .string-concatenation {
          background: url("../images/logo.svg");
        }
        CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    });

    it('handles url in @font-face with multiple sources and format hints', function () {
        $scss = <<<'SCSS'
        @font-face {
            font-family: 'CustomFont';
            src: url('../fonts/custom.woff2') format('woff2'),
                 url('../fonts/custom.woff') format('woff'),
                 url('../fonts/custom.ttf') format('truetype');
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        @font-face {
          font-family: CustomFont;
          src: url("../fonts/custom.woff2") format("woff2"), url("../fonts/custom.woff") format("woff"), url("../fonts/custom.ttf") format("truetype");
        }
        CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    });

    it('handles @font-face with single source and absolute URL', function () {
        $scss = <<<'SCSS'
        @font-face {
            font-family: 'Icons';
            src: url('https://cdn.example.com/icons.woff2') format('woff2');
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        @font-face {
          font-family: Icons;
          src: url("https://cdn.example.com/icons.woff2") format("woff2");
        }
        CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    });

    it('handles @font-face with unquoted urls and mixed formats', function () {
        $scss = <<<'SCSS'
        @font-face {
            font-family: 'Mixed';
            src: url(../fonts/mixed.woff2) format("woff2"),
                 url(https://example.com/fallback.ttf) format('truetype');
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        @font-face {
          font-family: Mixed;
          src: url(../fonts/mixed.woff2) format("woff2"), url(https://example.com/fallback.ttf) format("truetype");
        }
        CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    });

    it('handles multiple @font-face rules with different url variations', function () {
        $scss = <<<'SCSS'
        @font-face {
            font-family: 'Custom Font';
            src: url('../fonts/custom.woff2') format('woff2'),
                 url('../fonts/custom.woff') format('woff');
        }

        @font-face {
            font-family: 'Icons';
            src: url("https://cdn.example.com/icons.woff2") format("woff2");
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        @font-face {
          font-family: "Custom Font";
          src: url("../fonts/custom.woff2") format("woff2"), url("../fonts/custom.woff") format("woff");
        }
        @font-face {
          font-family: Icons;
          src: url("https://cdn.example.com/icons.woff2") format("woff2");
        }
        CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    });

    it('handles multiple backgrounds', function () {
        $scss = <<<'SCSS'
        .multiple-backgrounds {
            background:
                url('overlay.png') repeat-x top,
                url('pattern.png') repeat,
                url('base.jpg') no-repeat center;
        }

        .multiple-with-variables {
            $bg1: 'texture.png';
            $bg2: 'gradient.svg';

            background:
                url('#{$bg1}') repeat,
                url('#{$bg2}') no-repeat;
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .multiple-backgrounds {
          background: url("overlay.png") repeat-x top, url("pattern.png") repeat, url("base.jpg") no-repeat center;
        }
        .multiple-with-variables {
          background: url("texture.png") repeat, url("gradient.svg") no-repeat;
        }
        CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    });
});
