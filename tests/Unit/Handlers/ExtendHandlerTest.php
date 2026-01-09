<?php

declare(strict_types=1);

use DartSass\Handlers\ExtendHandler;
use Tests\ReflectionAccessor;

describe('ExtendHandler', function () {
    beforeEach(function () {
        $this->handler  = new ExtendHandler();
        $this->accessor = new ReflectionAccessor($this->handler);
    });

    describe('applyExtends method', function () {
        it('applies extends when matches', function () {
            $this->handler->registerExtend('.featured-article', '.article');
            $css = ".article {\n  color: red;\n}";

            $result = $this->handler->applyExtends($css);

            expect($result)->toBe(".article, .featured-article {\n  color: red;\n}");
        });

        it('does not apply when no extends', function () {
            $css = '.article { color: red; }';

            $result = $this->handler->applyExtends($css);

            expect($result)->toBe($css);
        });
    });

    describe('selectorMatches method', function () {
        it('returns true for exact match', function () {
            $result = $this->accessor->callMethod('selectorMatches', ['.article', '.article']);

            expect($result)->toBeTrue();
        });

        it('returns true when base selector matches with spaces', function () {
            $result = $this->accessor->callMethod('selectorMatches', ['.container .article', '.article']);

            expect($result)->toBeTrue();
        });

        it('returns true when target is at the end with space', function () {
            $result = $this->accessor->callMethod('selectorMatches', ['.container .article', '.article']);

            expect($result)->toBeTrue();
        });

        it('returns true when target is the last part', function () {
            $result = $this->accessor->callMethod('selectorMatches', ['.article', '.article']);

            expect($result)->toBeTrue();
        });

        it('returns false when no match', function () {
            $result = $this->accessor->callMethod('selectorMatches', ['.article', '.button']);

            expect($result)->toBeFalse();
        });

        it('returns true for selector with pseudo-class', function () {
            $result = $this->accessor->callMethod('selectorMatches', ['.article:hover', '.article']);

            expect($result)->toBeTrue();
        });

        it('returns true for complex selector ending with target', function () {
            $result = $this->accessor->callMethod('selectorMatches', ['.container .article', '.article']);

            expect($result)->toBeTrue();
        });

        it('returns true when target is surrounded by spaces', function () {
            $result = $this->accessor->callMethod('selectorMatches', ['.container .article .button', '.article']);

            expect($result)->toBeTrue();
        });

        it('returns true when target is at the end of selector', function () {
            $result = $this->accessor->callMethod('selectorMatches', ['.container.article', '.article']);

            expect($result)->toBeTrue();
        });
    });

    describe('replaceInSelector method', function () {
        it('replaces simple selector at the end', function () {
            $result = $this->accessor->callMethod('replaceInSelector', ['.container .article', '.article', '.featured-article']);

            expect($result)->toBe('.container .featured-article');
        });

        it('replaces simple selector with multiple occurrences', function () {
            $result = $this->accessor->callMethod('replaceInSelector', ['.article .container .article', '.article', '.featured-article']);

            expect($result)->toBe('.article .container .featured-article');
        });

        it('replaces selector with pseudo-classes', function () {
            $result = $this->accessor->callMethod('replaceInSelector', ['.container .article:hover', '.article', '.featured-article']);

            expect($result)->toBe('.container .featured-article:hover');
        });

        it('uses fallback string replacement', function () {
            $result = $this->accessor->callMethod('replaceInSelector', ['.article', '.article', '.featured-article']);

            expect($result)->toBe('.featured-article');
        });

        it('handles complex target selector', function () {
            $result = $this->accessor->callMethod('replaceInSelector', ['.container .article', '.article', '.featured .article']);

            expect($result)->toBe('.container .article');
        });

        it('replaces when target has multiple parts but matches end', function () {
            // When selector does not start with space before target, falls to fallback
            $result = $this->accessor->callMethod('replaceInSelector', ['div.article', '.article', '.featured-article']);

            expect($result)->toBe('div.featured-article');
        });

        it('does not match when selector does not end with target', function () {
            $result = $this->accessor->callMethod('selectorMatches', ['.article .button', '.article']);

            expect($result)->toBeFalse();
        });
    });
});
