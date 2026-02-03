<?php

declare(strict_types=1);

use DartSass\Parsers\Nodes\ListNode;
use DartSass\Utils\SpreadHelper;
use DartSass\Values\SassList;
use DartSass\Values\SassMap;

describe('SpreadHelper', function () {
    describe('isSpread', function () {
        it('identifies array spread argument', function () {
            $arg = ['type' => 'spread', 'value' => 'test'];

            expect(SpreadHelper::isSpread($arg))->toBeTrue();
        });

        it('does not identify non-spread arguments', function () {
            expect(SpreadHelper::isSpread('test'))->toBeFalse()
                ->and(SpreadHelper::isSpread(123))->toBeFalse()
                ->and(SpreadHelper::isSpread(true))->toBeFalse()
                ->and(SpreadHelper::isSpread(null))->toBeFalse()
                ->and(SpreadHelper::isSpread([]))->toBeFalse()
                ->and(SpreadHelper::isSpread(['value' => 'test']))->toBeFalse()
                ->and(SpreadHelper::isSpread(['type' => 'regular', 'value' => 'test']))->toBeFalse();
        });
    });

    describe('expand', function () {
        it('expands SassList spread arguments', function () {
            $sassList = new SassList(['a', 'b', 'c']);

            $args   = [['type' => 'spread', 'value' => $sassList]];
            $result = SpreadHelper::expand($args, fn($x) => $x);

            expect($result)->toEqual(['a', 'b', 'c']);
        });

        it('expands ListNode spread arguments', function () {
            $listNode = new ListNode(['x', 'y']);

            $args   = [['type' => 'spread', 'value' => $listNode]];
            $result = SpreadHelper::expand($args, fn($x) => $x instanceof ListNode ? $x->values : $x);

            expect($result)->toEqual(['x', 'y']);
        });

        it('evaluates each item in ListNode when spreading', function () {
            $listNode = new ListNode([10, 20]);

            $args = [['type' => 'spread', 'value' => $listNode]];

            $result = SpreadHelper::expand($args, function ($x) {
                if ($x instanceof ListNode) {
                    return $x;
                }

                return $x * 2;
            });

            expect($result)->toEqual([20, 40]);
        });

        it('preserves regular arguments', function () {
            $args   = ['arg1', ['type' => 'spread', 'value' => new SassList(['a', 'b'])], 'arg2'];
            $result = SpreadHelper::expand($args, fn($x) => $x);

            expect($result)->toEqual(['arg1', 'a', 'b', 'arg2']);
        });

        it('preserves associative array keys', function () {
            $args = [
                'arg1',
                ['type' => 'spread', 'value' => new SassList(['a', 'b'])],
                '$separator' => 'comma',
                '$bracketed' => true,
            ];

            $result = SpreadHelper::expand($args, fn($x) => $x);
            expect($result)->toEqual([
                'arg1',
                'a',
                'b',
                '$separator' => 'comma',
                '$bracketed' => true,
            ]);
        });

        it('expands nested spread arguments', function () {
            $nestedList = new SassList(['inner1', 'inner2']);

            $args = [['type' => 'spread', 'value' => new SassList(['outer', $nestedList])]];

            $result = SpreadHelper::expand($args, function ($x) {
                if ($x instanceof SassList) {
                    return $x->value;
                }

                return $x;
            });

            expect($result)->toEqual(['outer', ['inner1', 'inner2']]);
        });

        it('handles non-array spread values', function () {
            $args   = [['type' => 'spread', 'value' => 'single value']];
            $result = SpreadHelper::expand($args, fn($x) => $x);

            expect($result)->toEqual(['single value']);
        });

        it('returns empty array when input is empty', function () {
            $args   = [];
            $result = SpreadHelper::expand($args, fn($x) => $x);

            expect($result)->toEqual([]);
        });
    });

    describe('collect', function () {
        it('collects remaining positional arguments', function () {
            $arguments = ['a', 'b', 'c', 'd'];
            $usedKeys  = [0, 1];
            $result    = SpreadHelper::collect($arguments, $usedKeys);

            expect($result->value)->toEqual(['c', 'd']);
        });

        it('collects all arguments when no keys are used', function () {
            $arguments = ['a', 'b', 'c'];
            $result    = SpreadHelper::collect($arguments, []);

            expect($result->value)->toEqual(['a', 'b', 'c']);
        });

        it('returns empty list when all arguments are used', function () {
            $arguments = ['a', 'b'];
            $result    = SpreadHelper::collect($arguments, [0, 1]);

            expect($result->value)->toEqual([]);
        });
    });

    describe('collectWithKeywords', function () {
        it('collects remaining positional and keyword arguments', function () {
            $arguments = ['a', 'b', '$c' => 3, 'd', '$e' => 5];
            $usedKeys  = [0, '$c'];
            $result    = SpreadHelper::collectWithKeywords($arguments, $usedKeys);

            expect($result)->toBeInstanceOf(SassMap::class)
                ->and($result->value)->toEqual(['e' => 5]);
        });

        it('collects only keyword arguments when all positional are used', function () {
            $arguments = ['a', 'b', '$c' => 3, '$d' => 4];
            $usedKeys  = [0, 1];
            $result    = SpreadHelper::collectWithKeywords($arguments, $usedKeys);

            expect($result->value)->toEqual(['c' => 3, 'd' => 4]);
        });

        it('returns empty list when all arguments are used', function () {
            $arguments = ['a', '$b' => 2];
            $usedKeys  = [0, '$b'];
            $result    = SpreadHelper::collectWithKeywords($arguments, $usedKeys);

            expect($result->value)->toEqual([]);
        });
    });

    describe('integration', function () {
        it('works with actual Sass arguments structure', function () {
            $args = [
                new SassList([10, 20]),
                new SassList([30, 40]),
                '$separator' => 'comma',
                '$bracketed' => true,
                ['type' => 'spread', 'value' => new SassList(['additional', 'items'])],
            ];

            $result = SpreadHelper::expand($args, fn($x) => $x);

            expect($result[0])->toBeInstanceOf(SassList::class)
                ->and($result[0]->value)->toEqual([10, 20])
                ->and($result[1])->toBeInstanceOf(SassList::class)
                ->and($result[1]->value)->toEqual([30, 40])
                ->and($result['$separator'])->toBe('comma')
                ->and($result['$bracketed'])->toBeTrue()
                ->and($result[2])->toBe('additional')
                ->and($result[3])->toBe('items');
        });
    });
});
