<?php

declare(strict_types=1);

use DartSass\Compiler;

beforeEach(function () {
    $this->compiler = new Compiler();
});

describe('sass:selector', function () {
    it('supports selector.is-superselector function', function () {
        $scss = <<<'SCSS'
        @use 'sass:selector';

        .demo {
            is-super: selector.is-superselector(".class", ".class .inner");
            not-super: selector.is-superselector(".inner", ".class");
            same: selector.is-superselector(".class", ".class");
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .demo {
          is-super: false;
          not-super: false;
          same: true;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('supports selector.append function', function () {
        $scss = <<<'SCSS'
        @use 'sass:selector';

        .demo {
            appended: selector.append("a", ".disabled");
            with-combinator: selector.append(".accordion", "__copy");
            multiple: selector.append(".accordion", "__copy, __image");
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .demo {
          appended: a.disabled;
          with-combinator: .accordion__copy;
          multiple: .accordion__copy, .accordion__image;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('supports selector.extend function', function () {
        $scss = <<<'SCSS'
        @use 'sass:selector';

        .demo {
            extended: selector.extend(".class .inner", ".inner", ".extended");
            no-match: selector.extend(".class .inner", ".other", ".extended");
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .demo {
          extended: .class .inner, .class .extended;
          no-match: .class .inner;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('supports selector.nest function', function () {
        $scss = <<<'SCSS'
        @use 'sass:selector';

        .demo {
            nested: selector.nest(".class", ".inner");
            multiple: selector.nest(".class", ".inner", ".deep");
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .demo {
          nested: .class .inner;
          multiple: .class .inner .deep;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('supports selector.parse function', function () {
        $scss = <<<'SCSS'
        @use 'sass:selector';
        @use 'sass:list';

        .demo {
            $selectors: selector.parse("a, b, c");
            length: list.length($selectors);
            first: list.nth($selectors, 1);
            second: list.nth($selectors, 2);
            third: list.nth($selectors, 3);
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .demo {
          length: 3;
          first: a;
          second: b;
          third: c;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('supports selector.replace function', function () {
        $scss = <<<'SCSS'
        @use 'sass:selector';

        .demo {
            replaced: selector.replace(".class .inner", ".inner", ".replaced");
            no-match: selector.replace(".class .inner", ".other", ".replaced");
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .demo {
          replaced: .class .replaced;
          no-match: .class .inner;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('supports selector.unify function', function () {
        $scss = <<<'SCSS'
        @use 'sass:selector';

        .demo {
            unified-class: selector.unify(".class1", ".class2");
            unified-tag-class: selector.unify("div", ".class");
            unified-tag-id: selector.unify("div", "#id");
            conflict-tag: selector.unify("div", "span");
            conflict-id: selector.unify("#id1", "#id2");
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .demo {
          unified-class: .class1.class2;
          unified-tag-class: div.class;
          unified-tag-id: div#id;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('supports selector.simple-selectors function', function () {
        $scss = <<<'SCSS'
        @use 'sass:selector';
        @use 'sass:list';

        .demo {
            $simple: selector.simple-selectors("div.class#id:hover");
            length: list.length($simple);
            first: list.nth($simple, 1);
            second: list.nth($simple, 2);
            third: list.nth($simple, 3);
            fourth: list.nth($simple, 4);
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .demo {
          length: 4;
          first: div;
          second: .class;
          third: #id;
          fourth: :hover;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });
});

describe('global selector functions', function () {
    it('supports global is-superselector function', function () {
        $scss = <<<'SCSS'
        .demo {
            is-super: is-superselector(".class", ".class .inner");
            not-super: is-superselector(".inner", ".class");
            same: is-superselector(".class", ".class");
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .demo {
          is-super: false;
          not-super: false;
          same: true;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('supports global selector-append function', function () {
        $scss = <<<'SCSS'
        .demo {
            appended: selector-append("a", ".disabled");
            with-combinator: selector-append(".accordion", "__copy");
            multiple: selector-append(".accordion", "__copy, __image");
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .demo {
          appended: a.disabled;
          with-combinator: .accordion__copy;
          multiple: .accordion__copy, .accordion__image;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('supports global selector-extend function', function () {
        $scss = <<<'SCSS'
        .demo {
            extended: selector-extend(".class .inner", ".inner", ".extended");
            no-match: selector-extend(".class .inner", ".other", ".extended");
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .demo {
          extended: .class .inner, .class .extended;
          no-match: .class .inner;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('supports global selector-nest function', function () {
        $scss = <<<'SCSS'
        .demo {
            nested: selector-nest(".class", ".inner");
            multiple: selector-nest(".class", ".inner", ".deep");
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .demo {
          nested: .class .inner;
          multiple: .class .inner .deep;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('supports global selector-parse function', function () {
        $scss = <<<'SCSS'
        .demo {
            $selectors: selector-parse("a, b, c");
            length: list.length($selectors);
            first: list.nth($selectors, 1);
            second: list.nth($selectors, 2);
            third: list.nth($selectors, 3);
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .demo {
          length: 3;
          first: a;
          second: b;
          third: c;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('supports global selector-replace function', function () {
        $scss = <<<'SCSS'
        .demo {
            replaced: selector-replace(".class .inner", ".inner", ".replaced");
            no-match: selector-replace(".class .inner", ".other", ".replaced");
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .demo {
          replaced: .class .replaced;
          no-match: .class .inner;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('supports global selector-unify function', function () {
        $scss = <<<'SCSS'
        .demo {
            unified-class: selector-unify(".class1", ".class2");
            unified-tag-class: selector-unify("div", ".class");
            unified-tag-id: selector-unify("div", "#id");
            conflict-tag: selector-unify("div", "span");
            conflict-id: selector-unify("#id1", "#id2");
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .demo {
          unified-class: .class1.class2;
          unified-tag-class: div.class;
          unified-tag-id: div#id;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });

    it('supports global simple-selectors function', function () {
        $scss = <<<'SCSS'
        .demo {
            $simple: simple-selectors("div.class#id:hover");
            length: length($simple);
            first: nth($simple, 1);
            second: nth($simple, 2);
            third: nth($simple, 3);
            fourth: nth($simple, 4);
        }
        SCSS;

        $expected = /** @lang text */ <<<'CSS'
        .demo {
          length: 4;
          first: div;
          second: .class;
          third: #id;
          fourth: :hover;
        }
        CSS;

        expect($this->compiler->compileString($scss))->toEqualCss($expected);
    });
});
