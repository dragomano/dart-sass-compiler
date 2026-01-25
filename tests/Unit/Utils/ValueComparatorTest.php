<?php

declare(strict_types=1);

use DartSass\Exceptions\CompilationException;
use DartSass\Utils\ValueComparator;
use DartSass\Values\SassList;
use DartSass\Values\SassMap;
use DartSass\Values\SassNumber;

describe('ValueComparator', function () {
    describe('equals()', function () {
        it('compares identical values with strict equality', function () {
            $obj = new stdClass();
            expect(ValueComparator::equals($obj, $obj))->toBeTrue()
                ->and(ValueComparator::equals('test', 'test'))->toBeTrue()
                ->and(ValueComparator::equals(42, 42))->toBeTrue();
        });

        it('compares arrays with different lengths', function () {
            expect(ValueComparator::equals([1, 2, 3], [1, 2]))->toBeFalse();
        });

        it('compares arrays with string and numeric keys of same value', function () {
            expect(ValueComparator::equals(['1' => 'a'], [1 => 'a']))->toBeTrue();
        });

        it('compares arrays containing SassNumber instances', function () {
            $left  = ['num' => new SassNumber(96, 'px')];
            $right = ['num' => new SassNumber(1, 'in')];

            expect(ValueComparator::equals($left, $right))->toBeTrue();
        });

        it('compares null values', function () {
            expect(ValueComparator::equals(null, null))->toBeTrue()
                ->and(ValueComparator::equals(null, 0))->toBeFalse()
                ->and(ValueComparator::equals(null, 10))->toBeFalse()
                ->and(ValueComparator::equals(null, 'string'))->toBeFalse()
                ->and(ValueComparator::equals(null, []))->toBeFalse()
                ->and(ValueComparator::equals(10, null))->toBeFalse()
                ->and(ValueComparator::equals('string', null))->toBeFalse()
                ->and(ValueComparator::equals([], null))->toBeFalse();
        });

        it('compares equal numbers', function () {
            expect(ValueComparator::equals(10, 10))->toBeTrue()
                ->and(ValueComparator::equals(10.5, 10.5))->toBeTrue()
                ->and(ValueComparator::equals(new SassNumber(10, 'px'), new SassNumber(10, 'px')))->toBeTrue();
        });

        it('compares equal compatible units', function () {
            $left  = new SassNumber(96, 'px');
            $right = new SassNumber(1, 'in');

            expect(ValueComparator::equals($left, $right))->toBeTrue();
        });

        it('compares incompatible SassNumbers', function () {
            $left  = new SassNumber(10, 'px');
            $right = new SassNumber(10, 's');

            expect(ValueComparator::equals($left, $right))->toBeFalse();
        });

        it('compares unequal values', function () {
            expect(ValueComparator::equals(10, 11))->toBeFalse()
                ->and(ValueComparator::equals('hello', 'world'))->toBeFalse();
        });

        it('compares boolean values', function () {
            expect(ValueComparator::equals(true, true))->toBeTrue()
                ->and(ValueComparator::equals(false, false))->toBeTrue()
                ->and(ValueComparator::equals(true, false))->toBeFalse();
        });

        it('compares SassLists', function () {
            $list1 = new SassList([1, 2, 3]);
            $list2 = new SassList([1, 2, 3]);
            $list3 = new SassList([1, 2, 4]);
            $list4 = new SassList([1, 2]);

            expect(ValueComparator::equals($list1, $list2))->toBeTrue()
                ->and(ValueComparator::equals($list1, $list3))->toBeFalse()
                ->and(ValueComparator::equals($list1, $list4))->toBeFalse();
        });

        it('compares SassLists with different separators', function () {
            $list1 = new SassList([1, 2, 3], 'space');
            $list2 = new SassList([1, 2, 3], 'comma');

            expect(ValueComparator::equals($list1, $list2))->toBeTrue();
        });

        it('compares SassMaps', function () {
            $map1 = new SassMap(['a' => 1, 'b' => 2]);
            $map2 = new SassMap(['a' => 1, 'b' => 2]);
            $map3 = new SassMap(['a' => 1, 'b' => 3]);
            $map4 = new SassMap(['a' => 1]);
            $map5 = new SassMap(['b' => 1]);

            expect(ValueComparator::equals($map1, $map2))->toBeTrue()
                ->and(ValueComparator::equals($map1, $map3))->toBeFalse()
                ->and(ValueComparator::equals($map1, $map4))->toBeFalse()
                ->and(ValueComparator::equals($map4, $map5))->toBeFalse();
        });

        it('compares arrays', function () {
            expect(ValueComparator::equals([1, 2, 3], [1, 2, 3]))->toBeTrue()
                ->and(ValueComparator::equals([1, 2, 3], [1, 2, 4]))->toBeFalse()
                ->and(ValueComparator::equals(['a' => 1], ['b' => 1]))->toBeFalse()
                ->and(ValueComparator::equals(['a' => 1], ['a' => 2]))->toBeFalse();
        });

        it('compares nested arrays', function () {
            expect(ValueComparator::equals([1, [2, 3]], [1, [2, 3]]))->toBeTrue()
                ->and(ValueComparator::equals([1, [2, 3]], [1, [2, 4]]))->toBeFalse();
        });

        it('compares structured values without unit to numeric', function () {
            $structured1 = ['value' => 10, 'unit' => ''];
            $structured2 = ['value' => 10];

            expect(ValueComparator::equals($structured1, 10))->toBeTrue()
                ->and(ValueComparator::equals(10, $structured1))->toBeTrue()
                ->and(ValueComparator::equals($structured1, 11))->toBeFalse()
                ->and(ValueComparator::equals($structured2, 10))->toBeTrue()
                ->and(ValueComparator::equals(10, $structured2))->toBeTrue()
                ->and(ValueComparator::equals($structured2, 11))->toBeFalse();
        });

        it('compares structured arrays with same value and unit', function () {
            $left  = ['value' => 10, 'unit' => 'px'];
            $right = ['value' => 10, 'unit' => 'px'];

            expect(ValueComparator::equals($left, $right))->toBeTrue();
        });

        it('compares structured arrays with different units', function () {
            $left  = ['value' => 10, 'unit' => 'px'];
            $right = ['value' => 10, 'unit' => 'em'];

            expect(ValueComparator::equals($left, $right))->toBeFalse();
        });

        it('compares structured arrays with different values', function () {
            $left  = ['value' => 10, 'unit' => 'px'];
            $right = ['value' => 20, 'unit' => 'px'];

            expect(ValueComparator::equals($left, $right))->toBeFalse();
        });

        it('compares strings', function () {
            expect(ValueComparator::equals('hello', 'hello'))->toBeTrue()
                ->and(ValueComparator::equals('"hello"', 'hello'))->toBeTrue()
                ->and(ValueComparator::equals("'hello'", 'hello'))->toBeTrue()
                ->and(ValueComparator::equals('"hello"', "'hello'"))->toBeTrue()
                ->and(ValueComparator::equals('hello', 'world'))->toBeFalse()
                ->and(ValueComparator::equals('"hello"', '"world"'))->toBeFalse()
                ->and(ValueComparator::equals("'hello'", "'world'"))->toBeFalse();
        });

        it('compares numeric strings', function () {
            expect(ValueComparator::equals('10', 10))->toBeTrue()
                ->and(ValueComparator::equals(10, '10'))->toBeTrue();
        });

        it('compares numeric values', function () {
            expect(ValueComparator::equals(5, 5))->toBeTrue()
                ->and(ValueComparator::equals(5.0, 5))->toBeTrue()
                ->and(ValueComparator::equals(10, 10.0))->toBeTrue()
                ->and(ValueComparator::equals(5, 6))->toBeFalse();
        });

        it('uses loose equality as fallback', function () {
            expect(ValueComparator::equals(1, '1'))->toBeTrue()
                ->and(ValueComparator::equals(0, false))->toBeTrue()
                ->and(ValueComparator::equals('', false))->toBeTrue();
        });
    });

    describe('notEquals()', function () {
        it('compares not equal', function () {
            expect(ValueComparator::notEquals(10, 11))->toBeTrue()
                ->and(ValueComparator::notEquals(10, 10))->toBeFalse();
        });
    });

    describe('lessThan()', function () {
        it('compares less than', function () {
            expect(ValueComparator::lessThan(5, 10))->toBeTrue()
                ->and(ValueComparator::lessThan(10, 5))->toBeFalse()
                ->and(ValueComparator::lessThan(10, 10))->toBeFalse();
        });

        it('compares less than with SassNumbers', function () {
            $left  = new SassNumber(5, 'px');
            $right = new SassNumber(10, 'px');

            expect(ValueComparator::lessThan($left, $right))->toBeTrue();
        });

        it('throws on non-numeric comparison', function () {
            expect(fn() => ValueComparator::lessThan('hello', 'world'))
                ->toThrow(CompilationException::class, 'Cannot compare non-numeric values');
        });
    });

    describe('greaterThan()', function () {
        it('compares greater than', function () {
            expect(ValueComparator::greaterThan(15, 10))->toBeTrue()
                ->and(ValueComparator::greaterThan(10, 15))->toBeFalse()
                ->and(ValueComparator::greaterThan(10, 10))->toBeFalse();
        });

        it('compares greater than with SassNumbers', function () {
            $left  = new SassNumber(15, 'px');
            $right = new SassNumber(10, 'px');

            expect(ValueComparator::greaterThan($left, $right))->toBeTrue();
        });
    });

    describe('lessThanOrEqual()', function () {
        it('compares less than or equal', function () {
            expect(ValueComparator::lessThanOrEqual(5, 10))->toBeTrue()
                ->and(ValueComparator::lessThanOrEqual(10, 10))->toBeTrue()
                ->and(ValueComparator::lessThanOrEqual(15, 10))->toBeFalse();
        });
    });

    describe('greaterThanOrEqual()', function () {
        it('compares greater than or equal', function () {
            expect(ValueComparator::greaterThanOrEqual(15, 10))->toBeTrue()
                ->and(ValueComparator::greaterThanOrEqual(10, 10))->toBeTrue()
                ->and(ValueComparator::greaterThanOrEqual(5, 10))->toBeFalse();
        });
    });

    describe('and()', function () {
        it('performs logical and', function () {
            expect(ValueComparator::and(true, true))->toBeTrue()
                ->and(ValueComparator::and(true, false))->toBeFalse()
                ->and(ValueComparator::and(false, true))->toBeFalse()
                ->and(ValueComparator::and(false, false))->toBeFalse();
        });
    });

    describe('or()', function () {
        it('performs logical or', function () {
            expect(ValueComparator::or(true, true))->toBeTrue()
                ->and(ValueComparator::or(true, false))->toBeTrue()
                ->and(ValueComparator::or(false, true))->toBeTrue()
                ->and(ValueComparator::or(false, false))->toBeFalse();
        });
    });

    describe('not()', function () {
        it('performs logical not', function () {
            expect(ValueComparator::not(true))->toBeFalse()
                ->and(ValueComparator::not(false))->toBeTrue();
        });
    });

    describe('compare()', function () {
        it('compares with equality operator', function () {
            expect(ValueComparator::compare('==', 10, 10))->toBeTrue()
                ->and(ValueComparator::compare('==', 10, 11))->toBeFalse();
        });

        it('compares with inequality operator', function () {
            expect(ValueComparator::compare('!=', 10, 11))->toBeTrue()
                ->and(ValueComparator::compare('!=', 10, 10))->toBeFalse();
        });

        it('compares with less than operator', function () {
            expect(ValueComparator::compare('<', 5, 10))->toBeTrue()
                ->and(ValueComparator::compare('<', 10, 5))->toBeFalse();
        });

        it('compares with greater than operator', function () {
            expect(ValueComparator::compare('>', 15, 10))->toBeTrue()
                ->and(ValueComparator::compare('>', 10, 15))->toBeFalse();
        });

        it('compares with less than or equal operator', function () {
            expect(ValueComparator::compare('<=', 5, 10))->toBeTrue()
                ->and(ValueComparator::compare('<=', 10, 10))->toBeTrue()
                ->and(ValueComparator::compare('<=', 15, 10))->toBeFalse();
        });

        it('compares with greater than or equal operator', function () {
            expect(ValueComparator::compare('>=', 15, 10))->toBeTrue()
                ->and(ValueComparator::compare('>=', 10, 10))->toBeTrue()
                ->and(ValueComparator::compare('>=', 5, 10))->toBeFalse();
        });

        it('performs and operation', function () {
            expect(ValueComparator::compare('and', true, true))->toBeTrue()
                ->and(ValueComparator::compare('and', true, false))->toBeFalse();
        });

        it('performs or operation', function () {
            expect(ValueComparator::compare('or', false, true))->toBeTrue()
                ->and(ValueComparator::compare('or', false, false))->toBeFalse();
        });

        it('performs not operation', function () {
            expect(ValueComparator::compare('not', false, null))->toBeTrue()
                ->and(ValueComparator::compare('not', true, null))->toBeFalse();
        });

        it('throws on unknown comparison operator', function () {
            expect(fn() => ValueComparator::compare('unknown', 1, 2))
                ->toThrow(CompilationException::class, 'Unknown comparison operator: unknown');
        });
    });

    describe('isTruthy()', function () {
        it('checks truthy values', function () {
            expect(ValueComparator::isTruthy(true))->toBeTrue()
                ->and(ValueComparator::isTruthy(1))->toBeTrue()
                ->and(ValueComparator::isTruthy('hello'))->toBeTrue()
                ->and(ValueComparator::isTruthy([]))->toBeTrue()
                ->and(ValueComparator::isTruthy(new stdClass()))->toBeTrue();
        });

        it('checks falsy values', function () {
            expect(ValueComparator::isTruthy(false))->toBeFalse()
                ->and(ValueComparator::isTruthy(null))->toBeFalse();
        });
    });

    describe('isComparisonOperator()', function () {
        it('identifies comparison operators', function () {
            expect(ValueComparator::isComparisonOperator('=='))->toBeTrue()
                ->and(ValueComparator::isComparisonOperator('!='))->toBeTrue()
                ->and(ValueComparator::isComparisonOperator('<'))->toBeTrue()
                ->and(ValueComparator::isComparisonOperator('>'))->toBeTrue()
                ->and(ValueComparator::isComparisonOperator('<='))->toBeTrue()
                ->and(ValueComparator::isComparisonOperator('>='))->toBeTrue()
                ->and(ValueComparator::isComparisonOperator('and'))->toBeFalse()
                ->and(ValueComparator::isComparisonOperator('unknown'))->toBeFalse();
        });
    });

    describe('isLogicalOperator()', function () {
        it('identifies logical operators', function () {
            expect(ValueComparator::isLogicalOperator('and'))->toBeTrue()
                ->and(ValueComparator::isLogicalOperator('or'))->toBeTrue()
                ->and(ValueComparator::isLogicalOperator('not'))->toBeTrue()
                ->and(ValueComparator::isLogicalOperator('=='))->toBeFalse()
                ->and(ValueComparator::isLogicalOperator('unknown'))->toBeFalse();
        });
    });

    describe('getComparisonOperators()', function () {
        it('gets comparison operators', function () {
            $operators = ValueComparator::getComparisonOperators();

            expect($operators)->toBe(['==', '!=', '<', '>', '<=', '>=']);
        });
    });

    describe('getLogicalOperators()', function () {
        it('gets logical operators', function () {
            $operators = ValueComparator::getLogicalOperators();

            expect($operators)->toBe(['and', 'or', 'not']);
        });
    });
})->covers(ValueComparator::class);
