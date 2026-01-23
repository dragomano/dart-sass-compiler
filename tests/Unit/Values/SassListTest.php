<?php

declare(strict_types=1);

use DartSass\Values\SassList;

describe('SassList', function () {
    describe('__toString()', function () {
        it('formats list with space separator', function () {
            $list = new SassList(['a', 'b', 'c']);

            expect((string) $list)->toBe('a b c');
        });

        it('formats list with comma separator', function () {
            $list = new SassList(['a', 'b', 'c'], 'comma');

            expect((string) $list)->toBe('a, b, c');
        });

        it('formats list with slash separator', function () {
            $list = new SassList(['a', 'b', 'c'], 'slash');

            expect((string) $list)->toBe('a / b / c');
        });

        it('formats bracketed list', function () {
            $list = new SassList(['a', 'b', 'c'], 'space', true);

            expect((string) $list)->toBe('[a b c]');
        });

        it('formats empty list', function () {
            $list = new SassList([]);

            expect((string) $list)->toBe('');
        });

        it('formats list with numbers', function () {
            $list = new SassList([1, 2, 3]);

            expect((string) $list)->toBe('1 2 3');
        });
    });
})->covers(SassList::class);
