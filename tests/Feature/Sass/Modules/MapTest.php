<?php

declare(strict_types=1);

use DartSass\Compiler;

beforeEach(function () {
    $this->compiler = new Compiler();
});

describe('sass:map', function () {
    it('supports map.get', function () {
        $scss = <<<'SCSS'
        @use 'sass:map';

        .demo {
            $map: ('a': 1, 'b': 2, 'c': 3);
            value: map.get($map, 'b');

            $nested: ('outer': ('inner': 'value'));
            nested-value: map.get($nested, 'outer', 'inner');
        }
        SCSS;

        $expected /** @lang text */ = <<<'CSS'
        .demo {
          value: 2;
          nested-value: value;
        }
        CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    });

    it('supports map.has-key', function () {
        $scss = <<<'SCSS'
        @use 'sass:map';

        .demo {
            $map: ('a': 1, 'b': 2, 'c': 3);
            @if map.has-key($map, 'b') {
                has-b: true;
            }

            @if map.has-key($map, 'nonexistent') == false {
                no-missing: true;
            }

            $nested: ('outer': ('inner': 'value'));
            @if map.has-key($nested, 'outer', 'inner') {
                has-nested: true;
            }

            @if map.has-key($nested, 'outer', 'missing') == false {
                no-deep-missing: true;
            }

            @if map.has-key((), 'key') == false {
                empty-map: true;
            }
        }
        SCSS;

        $expected /** @lang text */ = <<<'CSS'
        .demo {
          has-b: true;
          no-missing: true;
          has-nested: true;
          no-deep-missing: true;
          empty-map: true;
        }
        CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    });

    it('supports map.keys', function () {
        $scss = <<<'SCSS'
        @use 'sass:map';

        .demo {
            $map: ('a': 1, 'b': 2, 'c': 3);
            keys: map.keys($map);

            $single: map.keys(('key': 'value'));
            single-key: $single;
        }
        SCSS;

        $expected /** @lang text */ = <<<'CSS'
        .demo {
          keys: a, b, c;
          single-key: key;
        }
        CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    });

    it('supports map.merge', function () {
        $scss = <<<'SCSS'
        @use 'sass:map';

        .demo {
            $map1: ('a': 1, 'b': 2);
            $map2: ('b': 20, 'c': 3);
            $merged: map.merge($map1, $map2);
            merged: map.get($merged, 'b') map.get($merged, 'c');

            $nested: ('outer': ('inner': 'old'));
            $update: ('inner': 'new', 'extra': 'added');
            $deep-merged: map.merge($nested, 'outer', $update);
            deep-merged: map.get($deep-merged, 'outer', 'inner') map.get($deep-merged, 'outer', 'extra');
        }
        SCSS;

        $expected /** @lang text */ = <<<'CSS'
        .demo {
          merged: 20 3;
          deep-merged: new added;
        }
        CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    });

    it('supports map.remove', function () {
        $scss = <<<'SCSS'
        @use 'sass:map';

        .demo {
            $map: ('a': 1, 'b': 2, 'c': 3);
            $removed: map.remove($map, 'b');
            after-remove: map.keys($removed);

            $multi-removed: map.remove($map, 'a', 'c');
            after-multi: map.keys($multi-removed);
        }
        SCSS;

        $expected /** @lang text */ = <<<'CSS'
        .demo {
          after-remove: a, c;
          after-multi: b;
        }
        CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    });

    it('supports map.set', function () {
        $scss = <<<'SCSS'
        @use 'sass:map';

        .demo {
            $map: ('a': 1, 'b': 2);
            $updated: map.set($map, 'c', 3);
            updated: map.get($updated, 'c');

            $nested: ('outer': ('inner': 'old'));
            $deep-updated: map.set($nested, 'outer', 'inner', 'new');
            deep-updated: map.get($deep-updated, 'outer', 'inner');

            $new-nested: map.set((), 'a', 'b', 'value');
            new-nested: map.get($new-nested, 'a', 'b');
        }
        SCSS;

        $expected /** @lang text */ = <<<'CSS'
        .demo {
          updated: 3;
          deep-updated: new;
          new-nested: value;
        }
        CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    });

    it('supports map.values', function () {
        $scss = <<<'SCSS'
        @use 'sass:map';

        .demo {
            $map: ('a': 1, 'b': 2, 'c': 3);
            values: map.values($map);

            $single: map.values(('key': 'value'));
            single-value: $single;
        }
        SCSS;

        $expected /** @lang text */ = <<<'CSS'
        .demo {
          values: 1, 2, 3;
          single-value: value;
        }
        CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    });

    it('supports map.deep-merge', function () {
        $scss = <<<'SCSS'
        @use 'sass:map';

        .demo {
            $nav: ('bg': 'gray', 'color': ('hover': ('search': 'yellow', 'home': 'red')));
            $update: ('bg': 'white', 'color': ('hover': ('search': 'green', 'logo': 'orange')));
            $deep-merged: map.deep-merge($nav, $update);
            bg: map.get($deep-merged, 'bg');
            search: map.get($deep-merged, 'color', 'hover', 'search');
            home: map.get($deep-merged, 'color', 'hover', 'home');
            logo: map.get($deep-merged, 'color', 'hover', 'logo');
        }
        SCSS;

        $expected /** @lang text */ = <<<'CSS'
        .demo {
          bg: white;
          search: green;
          home: red;
          logo: orange;
        }
        CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    });

    it('supports map.deep-remove', function () {
        $scss = <<<'SCSS'
        @use 'sass:map';

        .demo {
            $nav: ('bg': 'gray', 'color': ('hover': ('search': 'yellow', 'home': 'red', 'filter': 'blue')));
            $deep-removed: map.deep-remove($nav, 'color', 'hover', 'search');
            has-home: map.has-key($deep-removed, 'color', 'hover', 'home');
            has-filter: map.has-key($deep-removed, 'color', 'hover', 'filter');
            no-search: map.has-key($deep-removed, 'color', 'hover', 'search');
        }
        SCSS;

        $expected /** @lang text */ = <<<'CSS'
        .demo {
          has-home: true;
          has-filter: true;
          no-search: false;
        }
        CSS;

        expect($this->compiler->compileString($scss))
            ->toEqualCss($expected);
    });
});

describe('global map functions', function () {
    describe('supports global map-get function', function () {
        it('gets value from map globally', function () {
            $scss = <<<'SCSS'
            .demo {
                $map: ('a': 1, 'b': 2, 'c': 3);
                value: map-get($map, 'b');

                $nested: ('outer': ('inner': 'value'));
                nested-value: map-get($nested, 'outer', 'inner');
            }
            SCSS;

            $expected /** @lang text */ = <<<'CSS'
            .demo {
              value: 2;
              nested-value: value;
            }
            CSS;

            expect($this->compiler->compileString($scss))
                ->toEqualCss($expected);
        });
    });

    describe('supports global map-has-key function', function () {
        it('checks if key exists in map globally', function () {
            $scss = <<<'SCSS'
            .demo {
                $map: ('a': 1, 'b': 2, 'c': 3);
                @if map-has-key($map, 'b') {
                    has-b: true;
                }

                @if map-has-key($map, 'nonexistent') == false {
                    no-missing: true;
                }

                $nested: ('outer': ('inner': 'value'));
                @if map-has-key($nested, 'outer', 'inner') {
                    has-nested: true;
                }
            }
            SCSS;

            $expected /** @lang text */ = <<<'CSS'
            .demo {
              has-b: true;
              no-missing: true;
              has-nested: true;
            }
            CSS;

            expect($this->compiler->compileString($scss))
                ->toEqualCss($expected);
        });
    });

    describe('supports global map-keys function', function () {
        it('returns keys of map globally', function () {
            $scss = <<<'SCSS'
            .demo {
                $map: ('a': 1, 'b': 2, 'c': 3);
                keys: map-keys($map);

                $single: map-keys(('key': 'value'));
                single-key: $single;
            }
            SCSS;

            $expected /** @lang text */ = <<<'CSS'
            .demo {
              keys: a, b, c;
              single-key: key;
            }
            CSS;

            expect($this->compiler->compileString($scss))
                ->toEqualCss($expected);
        });
    });

    describe('supports global map-merge function', function () {
        it('merges two maps globally', function () {
            $scss = <<<'SCSS'
            .demo {
                $map1: ('a': 1, 'b': 2);
                $map2: ('b': 20, 'c': 3);
                $merged: map-merge($map1, $map2);
                merged: map-get($merged, 'b') map-get($merged, 'c');
            }
            SCSS;

            $expected /** @lang text */ = <<<'CSS'
            .demo {
              merged: 20 3;
            }
            CSS;

            expect($this->compiler->compileString($scss))
                ->toEqualCss($expected);
        });
    });

    describe('supports global map-remove function', function () {
        it('removes keys from map globally', function () {
            $scss = <<<'SCSS'
            .demo {
                $map: ('a': 1, 'b': 2, 'c': 3);
                $removed: map-remove($map, 'b');
                after-remove: map-keys($removed);

                $multi-removed: map-remove($map, 'a', 'c');
                after-multi: map-keys($multi-removed);
            }
            SCSS;

            $expected /** @lang text */ = <<<'CSS'
            .demo {
              after-remove: a, c;
              after-multi: b;
            }
            CSS;

            expect($this->compiler->compileString($scss))
                ->toEqualCss($expected);
        });
    });

    describe('supports global map-values function', function () {
        it('returns values of map globally', function () {
            $scss = <<<'SCSS'
            .demo {
                $map: ('a': 1, 'b': 2, 'c': 3);
                values: map-values($map);

                $single: map-values(('key': 'value'));
                single-value: $single;
            }
            SCSS;

            $expected /** @lang text */ = <<<'CSS'
            .demo {
              values: 1, 2, 3;
              single-value: value;
            }
            CSS;

            expect($this->compiler->compileString($scss))
                ->toEqualCss($expected);
        });
    });
});
