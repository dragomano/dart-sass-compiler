<?php

declare(strict_types=1);

use DartSass\Compiler;

beforeEach(function () {
    $this->compiler = new Compiler();
});

describe('sass:list', function () {
    it('supports list.append', function () {
        $scss = <<<'SCSS'
        @use 'sass:list';

        .demo {
            padding: list.append(10px 20px, 30px);
            border-color: list.append((blue, red), green);

            $spacing2: list.append(10px 20px, 30px 40px);
            margin: list.nth($spacing2, 1) list.nth($spacing2, 2);

            $sizes1: list.append(10px, 20px, $separator: comma);
            gap: list.nth($sizes1, 1);

            $colors2: list.append((blue, red), green, $separator: space);
            background: linear-gradient(to right, $colors2);
        }
        SCSS;

        $expected = /** @lang text */
            <<<'CSS'
            .demo {
              padding: 10px 20px 30px;
              border-color: blue, red, green;
              margin: 10px 20px;
              gap: 10px;
              background: linear-gradient(to right, blue red green);
            }
            CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    });

    it('supports list.index', function () {
        $scss = <<<'SCSS'
        @use 'sass:list';

        .demo {
            $border: 1px solid red;
            z-index: list.index($border, 1px);

            order: list.index($border, solid);

            $index3: list.index($border, dashed);
            @if $index3 == null {
                opacity: 0.5;
            }
        }
        SCSS;

        $expected = /** @lang text */
            <<<'CSS'
            .demo {
              z-index: 1;
              order: 2;
              opacity: .5;
            }
            CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    });

    it('supports list.is-bracketed', function () {
        $scss = <<<'SCSS'
        @use 'sass:list';

        .demo {
            $list1: 1px 2px 3px;
            @if list.is-bracketed($list1) == false {
                margin: 10px;
            }

            $list2: [1px, 2px, 3px];
            @if list.is-bracketed($list2) == true {
                padding: 20px;
            }
        }
        SCSS;

        $expected = /** @lang text */
            <<<'CSS'
            .demo {
              margin: 10px;
              padding: 20px;
            }
            CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    });

    it('supports list.join', function () {
        $scss = <<<'SCSS'
        @use 'sass:list';

        .demo {
            $joined1: list.join(10px 20px, 30px 40px);
            padding: $joined1;

            $joined2: list.join((blue, red), (#abc, #def));
            border-color: $joined2;

            $joined3: list.join(10px, 20px);
            margin: $joined3;

            $joined4: list.join(10px, 20px, $separator: comma);
            gap: list.nth($joined4, 1);

            $joined5: list.join((blue, red), (#abc, #def), $separator: space);
            background: linear-gradient(to right, $joined5);

            $joined6: list.join([10px], 20px);
            outline-width: list.nth($joined6, 1);

            $joined7: list.join(10px, 20px, $bracketed: true);
            border-width: list.nth($joined7, 1) list.nth($joined7, 2);
        }
        SCSS;

        $expected = /** @lang text */
            <<<'CSS'
            .demo {
              padding: 10px 20px 30px 40px;
              border-color: blue, red, #abc, #def;
              margin: 10px 20px;
              gap: 10px;
              background: linear-gradient(to right, blue red #abc #def);
              outline-width: 10px;
              border-width: 10px 20px;
            }
            CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    });

    it('supports list.length', function () {
        $scss = <<<'SCSS'
        @use 'sass:list';

        .demo {
            z-index: list.length(10px);
            order: list.length(10px 20px 30px);
            flex: list.length((width: 10px, height: 20px));
        }
        SCSS;

        $expected = /** @lang text */
            <<<'CSS'
            .demo {
              z-index: 1;
              order: 3;
              flex: 2;
            }
            CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    });

    it('supports list.separator', function () {
        $scss = <<<'SCSS'
        @use 'sass:list';

        .demo {
            $sep1: list.separator(1px 2px 3px);
            @if $sep1 == space {
                margin: 10px;
            }

            $sep2: list.separator((1px, 2px, 3px));
            @if $sep2 == comma {
                padding: 20px;
            }

            $sep3: list.separator('Helvetica');
            @if $sep3 == space {
                border: 1px solid;
            }

            $sep4: list.separator(());
            @if $sep4 == space {
                outline: 2px solid;
            }
        }
        SCSS;

        $expected = /** @lang text */
            <<<'CSS'
            .demo {
              margin: 10px;
              padding: 20px;
              border: 1px solid;
              outline: 2px solid;
            }
            CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    });

    it('supports list.nth', function () {
        $scss = <<<'SCSS'
        @use 'sass:list';

        .demo {
            font-size: list.nth(10px 12px 16px, 2);
            grid-row: list.nth([line1, line2, line3], -1);
        }
        SCSS;

        $expected = /** @lang text */
            <<<'CSS'
            .demo {
              font-size: 12px;
              grid-row: line3;
            }
            CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    });

    it('supports list.set-nth', function () {
        $scss = <<<'SCSS'
        @use 'sass:list';

        .demo {
            padding: list.set-nth(10px 20px 30px, 1, 2em);
            margin: list.set-nth(10px 20px 30px, -1, 8em);
            font-family: list.set-nth((Helvetica, Arial, sans-serif), 3, Roboto);
        }
        SCSS;

        $expected = /** @lang text */
            <<<'CSS'
            .demo {
              padding: 2em 20px 30px;
              margin: 10px 20px 8em;
              font-family: Helvetica, Arial, Roboto;
            }
            CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    });

    it('supports list.slash', function () {
        $scss = <<<'SCSS'
        @use 'sass:list';

        .demo {
            border-radius: list.slash(1px, 50px, 100px);
        }
        SCSS;

        $expected = /** @lang text */
            <<<'CSS'
            .demo {
              border-radius: 1px / 50px / 100px;
            }
            CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    });

    it('supports list.zip', function () {
        $scss = <<<'SCSS'
        @use 'sass:list';

        .demo {
            $zipped1: list.zip(10px 50px 100px, short mid long);
            $first-pair: list.nth($zipped1, 1);
            width: list.nth($first-pair, 1);

            $zipped2: list.zip(10px 50px 100px, short mid);
            $second-pair: list.nth($zipped2, 2);
            height: list.nth($second-pair, 1);
        }
        SCSS;

        $expected = /** @lang text */
            <<<'CSS'
            .demo {
              width: 10px;
              height: 50px;
            }
            CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    });
});
