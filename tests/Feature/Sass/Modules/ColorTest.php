<?php

declare(strict_types=1);

use DartSass\Compiler;

beforeEach(function () {
    $this->compiler = new Compiler();
});

describe('sass:color', function () {
    describe('supports color.adjust function', function () {
        it('adjusts RGB components', function () {
            $scss = <<<'SCSS'
            @use 'sass:color';

            $color: #6b717f;
            body {
                background: color.adjust($color, $red: 15);
            }
            SCSS;

            $expected = <<<'CSS'
            body {
              background: #7a717f;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);

            $scss = <<<'SCSS'
            @use 'sass:color';

            $color: #ff0000;

            body {
                background: color.adjust($color, $blue: 50);
            }
            SCSS;

            $expected = <<<'CSS'
            body {
              background: #ff0032;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);

            $scss = <<<'SCSS'
            @use 'sass:color';

            $color: #ff8800;

            body {
                background: color.adjust($color, $green: 100);
            }
            SCSS;

            $expected = <<<'CSS'
            body {
              background: #ffec00;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('adjusts HSL components', function () {
            $scss = <<<'SCSS'
            @use 'sass:color';

            $original-color: #ff8800;
            $adjusted-color: color.adjust($original-color, $hue: 60deg);

            div {
                background-color: $adjusted-color;
            }
            SCSS;

            $expected = <<<'CSS'
            div {
              background-color: #77ff00;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);

            $scss = <<<'SCSS'
            @use 'sass:color';

            $color: #cc6666;

            body {
                background: color.adjust($color, $saturation: 20%);
            }
            SCSS;

            $expected = <<<'CSS'
            body {
              background: #e05252;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('adjusts lightness, alpha, and HWB', function () {
            $scss = <<<'SCSS'
            @use 'sass:color';

            $color: #ff0000;

            body {
                background: color.adjust($color, $lightness: 20%);
            }
            SCSS;

            $expected = <<<'CSS'
            body {
              background: #ff6666;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);

            $scss = <<<'SCSS'
            @use 'sass:color';

            $color: rgba(255, 0, 0, 0.5);

            body {
                background: color.adjust($color, $alpha: 0.3);
            }
            SCSS;

            $expected = <<<'CSS'
            body {
              background: #ff0000cc;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);

            $scss = <<<'SCSS'
            @use 'sass:color';

            $color: #ff0000;

            body {
                background: color.adjust($color, $whiteness: 20%, $space: hwb);
            }
            SCSS;

            $expected = <<<'CSS'
            body {
              background: #ff3333;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('handles edge cases', function () {
            $scss = <<<'SCSS'
            @use 'sass:color';

            $color: #0000ff;

            body {
                background: color.adjust($color, $blue: 50);
            }
            SCSS;

            $expected = <<<'CSS'
            body {
              background: blue;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);

            $scss = <<<'SCSS'
            @use 'sass:color';

            $color: #ff0000;

            body {
                background: color.adjust($color, $saturation: -50%);
            }
            SCSS;

            $expected = <<<'CSS'
            body {
              background: #bf4040;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);

            $scss = <<<'SCSS'
            @use 'sass:color';

            $color: hsl(0, 100%, 50%);

            body {
                background: color.adjust($color, $hue: 120deg);
            }
            SCSS;

            $expected = <<<'CSS'
            body {
              background: lime;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });
    });

    it('supports color.change function', function () {
        $scss = <<<'SCSS'
        @use 'sass:color';

        $color: #ff0000;
        body {
            background: color.change($color, $lightness: 50%);
        }
        SCSS;

        $expected = <<<'CSS'
        body {
          background: red;
        }
        CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);

        $scss = <<<'SCSS'
        @use 'sass:color';

        $color: #ff0000;
        body {
            background: color.change($color, $hue: 120deg);
        }
        SCSS;

        $expected = <<<'CSS'
        body {
          background: lime;
        }
        CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);

        $scss = <<<'SCSS'
        @use 'sass:color';

        $color: #ff0000;
        body {
            background: color.change($color, $alpha: 0.5);
        }
        SCSS;

        $expected = <<<'CSS'
        body {
          background: #ff000080;
        }
        CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    });

    it('supports color.channel function', function () {
        $scss = <<<'SCSS'
        @use 'sass:color';

        div {
            red: color.channel(red, "red");
            green: color.channel(red, "green");
            blue: color.channel(red, "blue");
            alpha: color.channel(red, "alpha");
            hue: color.channel(hsl(120, 100%, 50%), "hue");
            saturation: color.channel(hsl(120, 100%, 50%), "saturation");
            lightness: color.channel(hsl(120, 100%, 50%), "lightness");
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        div {
          red: 255;
          green: 0;
          blue: 0;
          alpha: 1;
          hue: 120deg;
          saturation: 100%;
          lightness: 50%;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('supports color.complement function', function () {
        $scss = <<<'SCSS'
        @use 'sass:color';

        div {
            complement-red: color.complement(red);
            complement-green: color.complement(lime);
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        div {
          complement-red: cyan;
          complement-green: magenta;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('supports color.grayscale function', function () {
        $scss = <<<'SCSS'
        @use 'sass:color';

        div {
            grayscale-red: color.grayscale(red);
            grayscale-blue: color.grayscale(blue);
            grayscale-green: color.grayscale(lime);
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        div {
          grayscale-red: grey;
          grayscale-blue: grey;
          grayscale-green: grey;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('supports color.is-hex-str function', function () {
        $scss = <<<'SCSS'
        @use 'sass:color';

        div {
            ie-hex-str-red: color.ie-hex-str(red);
            ie-hex-str-blue: color.ie-hex-str(blue);
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        div {
          ie-hex-str-red: #FFFF0000;
          ie-hex-str-blue: #FF0000FF;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('supports color.invert function', function () {
        $scss = <<<'SCSS'
        @use 'sass:color';

        div {
            invert-red: color.invert(red, 100%);
            invert-white: color.invert(white, 100%);
            invert-black: color.invert(black, 100%);
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        div {
          invert-red: cyan;
          invert-white: black;
          invert-black: white;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('supports color.is-legacy function', function () {
        $scss = <<<'SCSS'
        @use 'sass:color';

        div {
            is-legacy-red: color.is-legacy(red);
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        div {
          is-legacy-red: true;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('supports color.is-missing function', function () {
        $scss = <<<'SCSS'
        @use 'sass:color';

        div {
            is-missing-red-red: color.is-missing(red, "red");
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        div {
          is-missing-red-red: false;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('supports color.is-powerless function', function () {
        $scss = <<<'SCSS'
        @use 'sass:color';

        div {
            is-powerless-gray-saturation: color.is-powerless(hsl(180deg 0% 40%), "hue");
            is-powerless-red-saturation: color.is-powerless(hsl(180deg 0% 40%), "saturation");
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        div {
          is-powerless-gray-saturation: true;
          is-powerless-red-saturation: false;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('supports color.mix function', function () {
        $scss = <<<'SCSS'
        @use 'sass:color';

        body {
            background: color.mix(#ff0000, #0000ff, 0.5);
        }
        SCSS;

        $expected = <<<'CSS'
        body {
          background: purple;
        }
        CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    });

    it('supports color.same function', function () {
        $scss = <<<'SCSS'
        @use 'sass:color';

        div {
            same-red-red: color.same(red, red);
            same-red-blue: color.same(red, blue);
            same-red-ff0000: color.same(red, #ff0000);
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        div {
          same-red-red: true;
          same-red-blue: false;
          same-red-ff0000: true;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('supports color.scale function', function () {
        $scss = <<<'SCSS'
        @use 'sass:color';

        $color: #ff0000;
        body {
            background: color.scale($color, $lightness: 20%);
        }
        SCSS;

        $expected = <<<'CSS'
        body {
          background: #ff3333;
        }
        CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);

        $scss = <<<'SCSS'
        @use 'sass:color';

        $color: #ff0000;
        body {
            background: color.scale($color, $saturation: -50%);
        }
        SCSS;

        $expected = <<<'CSS'
        body {
          background: #bf4040;
        }
        CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);

        $scss = <<<'SCSS'
        @use 'sass:color';

        $color: #ff0000;
        body {
            background: color.scale($color, $red: 20%);
        }
        SCSS;

        $expected = <<<'CSS'
        body {
          background: red;
        }
        CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    });

    it('supports color.space function', function () {
        $scss = <<<'SCSS'
        @use 'sass:color';

        div {
            space-red: color.space(red);
            space-hsl: color.space(hsl(0, 100%, 50%));
            space-hwb: color.space(color.hwb(0, 0%, 0%));
            space-lch: color.space(lch(60% 40 30deg));
            space-oklch: color.space(oklch(1% 20 40 / 0.5));
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        div {
          space-red: rgb;
          space-hsl: hsl;
          space-hwb: hwb;
          space-lch: lch;
          space-oklch: oklch;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('supports color.to-gamut function', function () {
        $scss = <<<'SCSS'
        @use 'sass:color';

        div {
            to-gamut-red: color.to-gamut(red, $method: clip);
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        div {
          to-gamut-red: red;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('supports color.to-space function', function () {
        $scss = <<<'SCSS'
        @use 'sass:color';

        div {
            to-space-red-to-hsl: color.to-space(red, hsl);
            to-space-hsl-to-rgb: color.to-space(hsl(0, 100%, 50%), rgb);
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        div {
          to-space-red-to-hsl: hsl(0, 100%, 50%);
          to-space-hsl-to-rgb: red;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('supports color.hwb function', function () {
        $scss = <<<'SCSS'
        @use 'sass:color';

        body {
            background: color.hwb(0, 0%, 0%);
        }
        SCSS;

        $expected = <<<'CSS'
        body {
          background: hwb(0 0% 0%);
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);

        $scss = <<<'SCSS'
        @use 'sass:color';

        body {
            background: color.hwb(0, 100%, 0%);
        }
        SCSS;

        $expected = <<<'CSS'
        body {
          background: hwb(0 100% 0%);
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    describe('supports deprecated color functions', function () {
        it('supports adjust-hue function', function () {
            $scss = <<<'SCSS'
            div {
                adjusted-hue: adjust-hue(red, 180deg);
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            div {
              adjusted-hue: cyan;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('supports color.alpha function', function () {
            $scss = <<<'SCSS'
            @use 'sass:color';

            div {
                alpha-red: color.alpha(red);
                alpha-rgba: color.alpha(rgba(255, 0, 0, 0.5));
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            div {
              alpha-red: 1;
              alpha-rgba: .5;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('supports alpha function', function () {
            $scss = <<<'SCSS'
            div {
                alpha-red: alpha(red);
                alpha-rgba: alpha(rgba(255, 0, 0, 0.5));
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            div {
              alpha-red: 1;
              alpha-rgba: .5;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('supports opacity function', function () {
            $scss = <<<'SCSS'
            div {
                opacity-red: opacity(red);
                opacity-rgba: opacity(rgba(255, 0, 0, 0.5));
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            div {
              opacity-red: 1;
              opacity-rgba: .5;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('supports color.blackness function', function () {
            $scss = <<<'SCSS'
            @use 'sass:color';

            div {
                blackness-hwb: color.blackness(hwb(0, 0%, 50%));
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            div {
              blackness-hwb: 50%;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('supports blackness function', function () {
            $scss = <<<'SCSS'
            div {
                blackness-hwb: blackness(hwb(0, 0%, 50%));
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            div {
              blackness-hwb: 50%;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('supports color.red function', function () {
            $scss = <<<'SCSS'
            @use 'sass:color';

            div {
                red-value: color.red(red);
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            div {
              red-value: 255;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('supports red function', function () {
            $scss = <<<'SCSS'
            div {
                red-value: red(red);
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            div {
              red-value: 255;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('supports color.green function', function () {
            $scss = <<<'SCSS'
            @use 'sass:color';

            div {
                green-value: color.green(red);
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            div {
              green-value: 0;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('supports green function', function () {
            $scss = <<<'SCSS'
            div {
                green-value: green(red);
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            div {
              green-value: 0;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('supports color.blue function', function () {
            $scss = <<<'SCSS'
            @use 'sass:color';

            div {
                blue-value: color.blue(red);
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            div {
              blue-value: 0;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('supports blue function', function () {
            $scss = <<<'SCSS'
            div {
                blue-value: blue(red);
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            div {
              blue-value: 0;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('supports color.hue function', function () {
            $scss = <<<'SCSS'
            @use 'sass:color';

            div {
                hue-value: color.hue(hsl(120, 100%, 50%));
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            div {
              hue-value: 120deg;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('supports hue function', function () {
            $scss = <<<'SCSS'
            div {
                hue-value: hue(hsl(120, 100%, 50%));
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            div {
              hue-value: 120deg;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('supports color.lightness function', function () {
            $scss = <<<'SCSS'
            @use 'sass:color';

            div {
                lightness-value: color.lightness(hsl(0, 100%, 75%));
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            div {
              lightness-value: 75%;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('supports lightness function', function () {
            $scss = <<<'SCSS'
            div {
                lightness-value: lightness(hsl(0, 100%, 75%));
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            div {
              lightness-value: 75%;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('supports color.whiteness function', function () {
            $scss = <<<'SCSS'
            @use 'sass:color';

            div {
                whiteness-hwb: color.whiteness(hwb(0, 50%, 0%));
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            div {
              whiteness-hwb: 50%;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('supports whiteness function', function () {
            $scss = <<<'SCSS'
            div {
                whiteness-hwb: whiteness(hwb(0, 50%, 0%));
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            div {
              whiteness-hwb: 50%;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('supports opacify function', function () {
            $scss = <<<'SCSS'
            body {
                background: opacify(rgba(255, 0, 0, 0.5), 0.3);
            }
            SCSS;

            $expected = <<<'CSS'
            body {
              background: #ff0000cc;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('supports fade-in function', function () {
            $scss = <<<'SCSS'
            div {
                fade-in: fade-in(rgba(255, 0, 0, 0.5), 0.3);
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            div {
              fade-in: #ff0000cc;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('supports transparentize function', function () {
            $scss = <<<'SCSS'
            body {
                background: transparentize(rgba(255, 0, 0, 0.8), 0.3);
            }
            SCSS;

            $expected = <<<'CSS'
            body {
              background: #ff000080;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('supports fade-out function', function () {
            $scss = <<<'SCSS'
            div {
                fade-out: fade-out(rgba(255, 0, 0, 0.8), 0.3);
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            div {
              fade-out: #ff000080;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('supports color.saturation function', function () {
            $scss = <<<'SCSS'
            @use 'sass:color';

            div {
                saturation-value: color.saturation(hsl(0, 75%, 50%));
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            div {
              saturation-value: 75%;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('supports saturation function', function () {
            $scss = <<<'SCSS'
            div {
                saturation-value: saturation(hsl(0, 75%, 50%));
            }
            SCSS;

            $expected = /** @lang text */ <<<'CSS'
            div {
              saturation-value: 75%;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('supports lighten function', function () {
            $scss = <<<'SCSS'
            body {
                background: lighten(#ff0000, 20%);
            }
            SCSS;

            $expected = <<<'CSS'
            body {
              background: #ff6666;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('supports darken function', function () {
            $scss = <<<'SCSS'
            body {
                background: darken(#ff6666, 20%);
            }
            SCSS;

            $expected = <<<'CSS'
            body {
              background: red;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('supports saturate function', function () {
            $scss = <<<'SCSS'
            body {
                background: saturate(#cc6666, 20%);
            }
            SCSS;

            $expected = <<<'CSS'
            body {
              background: #e05252;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });

        it('supports desaturate function', function () {
            $scss = <<<'SCSS'
            body {
                background: desaturate(#ff0000, 50%);
            }
            SCSS;

            $expected = <<<'CSS'
            body {
              background: #bf4040;
            }
            CSS;

            expect($this->compiler->compileString($scss))->toEqualCss($expected);
        });
    });
});
