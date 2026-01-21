<?php

declare(strict_types=1);

use DartSass\Exceptions\CompilationException;
use DartSass\Values\SassNumber;

describe('SassNumber', function () {
    describe('__construct()', function () {
        it('creates SassNumber with value and unit', function () {
            $number = new SassNumber(10.5, 'px');

            expect($number->getValue())->toBe(10.5)
                ->and($number->getUnit())->toBe('px')
                ->and($number->hasUnit())->toBeTrue();
        });

        it('creates SassNumber without unit', function () {
            $number = new SassNumber(42.0);

            expect($number->getValue())->toBe(42.0)
                ->and($number->getUnit())->toBeNull()
                ->and($number->hasUnit())->toBeFalse();
        });

        it('creates SassNumber with null unit', function () {
            $number = new SassNumber(5.0, null);

            expect($number->getValue())->toBe(5.0)
                ->and($number->getUnit())->toBeNull()
                ->and($number->hasUnit())->toBeFalse();
        });

        it('creates SassNumber with empty string unit', function () {
            $number = new SassNumber(7.0, '');

            expect($number->getValue())->toBe(7.0)
                ->and($number->getUnit())->toBeNull()
                ->and($number->hasUnit())->toBeFalse();
        });
    });

    describe('__toString()', function () {
        it('formats to string', function () {
            $number = new SassNumber(10.5, 'px');

            expect((string) $number)->toBe('10.5px');
        });

        it('formats unitless number to string', function () {
            $number = new SassNumber(42.0);

            expect((string) $number)->toBe('42');
        });

        it('formats zero to string', function () {
            $number = new SassNumber(0.0, 'px');

            expect((string) $number)->toBe('0px');
        });

        it('formats decimal numbers correctly', function () {
            $number1 = new SassNumber(1.23456789, 'px');
            $number2 = new SassNumber(0.1, 'px');
            $number3 = new SassNumber(0.0001, 'px');

            expect((string) $number1)->toBe('1.23456789px')
                ->and((string) $number2)->toBe('0.1px')
                ->and((string) $number3)->toBe('0.0001px');
        });
    });

    describe('isCompatibleWith()', function () {
        it('checks unit compatibility', function () {
            $px = new SassNumber(10, 'px');
            $em = new SassNumber(5, 'em');
            $unitless = new SassNumber(2);

            expect($px->isCompatibleWith($px))->toBeTrue()
                ->and($px->isCompatibleWith($unitless))->toBeTrue()
                ->and($unitless->isCompatibleWith($px))->toBeTrue()
                ->and($px->isCompatibleWith($em))->toBeFalse();
        });
    });

    describe('convertTo()', function () {
        it('converts to same unit', function () {
            $number = new SassNumber(10, 'px');

            $converted = $number->convertTo('px');

            expect($converted->getValue())->toBe(10.0)
                ->and($converted->getUnit())->toBe('px');
        });

        it('assigns unit to unitless number', function () {
            $number = new SassNumber(5.5);

            $converted = $number->convertTo('em');

            expect($converted->getValue())->toBe(5.5)
                ->and($converted->getUnit())->toBe('em');
        });

        it('converts length units', function () {
            $number = new SassNumber(96, 'px');

            $converted = $number->convertTo('in');

            expect($converted->getValue())->toBe(1.0)
                ->and($converted->getUnit())->toBe('in');
        });

        it('converts angle units', function () {
            $number = new SassNumber(180, 'deg');

            $converted = $number->convertTo('rad');

            expect($converted->getValue())->toBeCloseTo(3.1415926535, 5)
                ->and($converted->getUnit())->toBe('rad');
        });

        it('converts time units', function () {
            $number = new SassNumber(1, 's');

            $converted = $number->convertTo('ms');

            expect($converted->getValue())->toBe(1000.0)
                ->and($converted->getUnit())->toBe('ms');
        });

        it('throws on incompatible unit conversion', function () {
            $number = new SassNumber(10, 'px');

            expect(fn() => $number->convertTo('deg'))
                ->toThrow(CompilationException::class, 'Cannot convert px to deg: incompatible units');
        });
    });

    describe('add()', function () {
        it('adds compatible units', function () {
            $left  = new SassNumber(10, 'px');
            $right = new SassNumber(5, 'px');

            $result = $left->add($right);

            expect($result->getValue())->toBe(15.0)
                ->and($result->getUnit())->toBe('px');
        });

        it('adds unitless numbers', function () {
            $left  = new SassNumber(10);
            $right = new SassNumber(5);

            $result = $left->add($right);

            expect($result->getValue())->toBe(15.0)
                ->and($result->getUnit())->toBeNull();
        });

        it('adds unitless to unit', function () {
            $left  = new SassNumber(10, 'px');
            $right = new SassNumber(5);

            $result = $left->add($right);

            expect($result->getValue())->toBe(15.0)
                ->and($result->getUnit())->toBe('px');
        });

        it('adds numbers with unit conversion', function () {
            $px = new SassNumber(10, 'px');
            $in = new SassNumber(1, 'in');

            $result = $px->add($in);

            expect($result->getValue())->toBe(10 + 96.0)
                ->and($result->getUnit())->toBe('px');
        });

        it('throws on incompatible units addition', function () {
            $left  = new SassNumber(10, 'px');
            $right = new SassNumber(5, 'em');

            expect(fn() => $left->add($right))
                ->toThrow(CompilationException::class, "Incompatible units for '+': px and em");
        });
    });

    describe('subtract()', function () {
        it('subtracts compatible units', function () {
            $left  = new SassNumber(10, 'px');
            $right = new SassNumber(3, 'px');

            $result = $left->subtract($right);

            expect($result->getValue())->toBe(7.0)
                ->and($result->getUnit())->toBe('px');
        });
    });

    describe('multiply()', function () {
        it('multiplies numbers', function () {
            $left  = new SassNumber(6, 'px');
            $right = new SassNumber(2);

            $result = $left->multiply($right);

            expect($result->getValue())->toBe(12.0)
                ->and($result->getUnit())->toBe('px');
        });

        it('multiplies unitless numbers', function () {
            $left  = new SassNumber(6);
            $right = new SassNumber(2);

            $result = $left->multiply($right);

            expect($result->getValue())->toBe(12.0)
                ->and($result->getUnit())->toBeNull();
        });
    });

    describe('divide()', function () {
        it('divides compatible units', function () {
            $left  = new SassNumber(10, 'px');
            $right = new SassNumber(2, 'px');

            $result = $left->divide($right);

            expect($result->getValue())->toBe(5.0)
                ->and($result->getUnit())->toBeNull();
        });

        it('divides unit by unitless', function () {
            $left  = new SassNumber(10, 'px');
            $right = new SassNumber(2);

            $result = $left->divide($right);

            expect($result->getValue())->toBe(5.0)
                ->and($result->getUnit())->toBe('px');
        });

        it('throws on division by zero', function () {
            $left  = new SassNumber(10, 'px');
            $right = new SassNumber(0);

            expect(fn() => $left->divide($right))
                ->toThrow(CompilationException::class, 'Division by zero');
        });

        it('throws on incompatible units division', function () {
            $left  = new SassNumber(10, 'px');
            $right = new SassNumber(2, 'em');

            expect(fn() => $left->divide($right))
                ->toThrow(CompilationException::class, 'Cannot divide px by em: incompatible units');
        });

        it('throws on unitless divided by unit', function () {
            $left  = new SassNumber(10);
            $right = new SassNumber(2, 'px');

            expect(fn() => $left->divide($right))
                ->toThrow(CompilationException::class, 'Cannot divide unitless number by px');
        });

        it('divides compatible units with conversion', function () {
            $left  = new SassNumber(96, 'px');
            $right = new SassNumber(1, 'in');

            $result = $left->divide($right);

            expect($result->getValue())->toBe(1.0)
                ->and($result->getUnit())->toBeNull();
        });
    });

    describe('equals()', function () {
        it('compares equal numbers', function () {
            $left  = new SassNumber(10.0, 'px');
            $right = new SassNumber(10.0, 'px');

            expect($left->equals($right))->toBeTrue();
        });

        it('compares equal numbers with different compatible units', function () {
            $left  = new SassNumber(96, 'px');
            $right = new SassNumber(1, 'in');

            expect($left->equals($right))->toBeTrue();
        });

        it('compares unequal numbers', function () {
            $left  = new SassNumber(10, 'px');
            $right = new SassNumber(11, 'px');

            expect($left->equals($right))->toBeFalse();
        });

        it('compares with custom epsilon', function () {
            $left  = new SassNumber(1.0000001);
            $right = new SassNumber(1.0000002);

            expect($left->equals($right, 1e-6))->toBeTrue()
                ->and($left->equals($right, 1e-8))->toBeFalse();
        });

        it('returns false for incompatible units in equals', function () {
            $left  = new SassNumber(10, 'px');
            $right = new SassNumber(10, 'deg');

            expect($left->equals($right))->toBeFalse();
        });
    });

    describe('lessThan()', function () {
        it('compares less than', function () {
            $left  = new SassNumber(5, 'px');
            $right = new SassNumber(10, 'px');

            expect($left->lessThan($right))->toBeTrue()
                ->and($right->lessThan($left))->toBeFalse();
        });
    });

    describe('greaterThan()', function () {
        it('compares greater than', function () {
            $left  = new SassNumber(15, 'px');
            $right = new SassNumber(10, 'px');

            expect($left->greaterThan($right))->toBeTrue()
                ->and($right->greaterThan($left))->toBeFalse();
        });
    });

    describe('lessThanOrEqual()', function () {
        it('compares less than or equal', function () {
            $left  = new SassNumber(10, 'px');
            $right = new SassNumber(10, 'px');
            $other = new SassNumber(15, 'px');

            expect($left->lessThanOrEqual($right))->toBeTrue()
                ->and($left->lessThanOrEqual($other))->toBeTrue()
                ->and($other->lessThanOrEqual($left))->toBeFalse();
        });
    });

    describe('greaterThanOrEqual()', function () {
        it('compares greater than or equal', function () {
            $left  = new SassNumber(10, 'px');
            $right = new SassNumber(10, 'px');
            $other = new SassNumber(5, 'px');

            expect($left->greaterThanOrEqual($right))->toBeTrue()
                ->and($left->greaterThanOrEqual($other))->toBeTrue()
                ->and($other->greaterThanOrEqual($left))->toBeFalse();
        });
    });

    describe('negate()', function () {
        it('negates number', function () {
            $number  = new SassNumber(10, 'px');
            $negated = $number->negate();

            expect($negated->getValue())->toBe(-10.0)
                ->and($negated->getUnit())->toBe('px');
        });
    });

    describe('abs()', function () {
        it('takes absolute value', function () {
            $positive = new SassNumber(10, 'px');
            $negative = new SassNumber(-5, 'px');

            expect($positive->abs()->getValue())->toBe(10.0)
                ->and($negative->abs()->getValue())->toBe(5.0);
        });
    });

    describe('fromString()', function () {
        it('creates from string with unit', function () {
            $number = SassNumber::fromString('10.5px');

            expect($number->getValue())->toBe(10.5)
                ->and($number->getUnit())->toBe('px');
        });

        it('creates from string without unit', function () {
            $number = SassNumber::fromString('42');

            expect($number->getValue())->toBe(42.0)
                ->and($number->getUnit())->toBeNull();
        });

        it('creates from string with negative value', function () {
            $number = SassNumber::fromString('-5.5em');

            expect($number->getValue())->toBe(-5.5)
                ->and($number->getUnit())->toBe('em');
        });

        it('creates from numeric string in scientific notation', function () {
            $number = SassNumber::fromString('1e5');

            expect($number->getValue())->toBe(100000.0)
                ->and($number->getUnit())->toBeNull();
        });

        it('throws on invalid string format', function () {
            expect(fn() => SassNumber::fromString('invalid'))
                ->toThrow(CompilationException::class, "Cannot parse 'invalid' as a number");
        });

        it('throws on empty string', function () {
            expect(fn() => SassNumber::fromString(''))
                ->toThrow(CompilationException::class, "Cannot parse '' as a number");
        });
    });

    describe('tryFrom()', function () {
        it('tries from valid SassNumber', function () {
            $original = new SassNumber(10, 'px');

            $result = SassNumber::tryFrom($original);

            expect($result)->toBe($original);
        });

        it('tries from numeric value', function () {
            $result = SassNumber::tryFrom(42.5);

            expect($result->getValue())->toBe(42.5)
                ->and($result->getUnit())->toBeNull();
        });

        it('tries from string', function () {
            $result = SassNumber::tryFrom('10px');

            expect($result->getValue())->toBe(10.0)
                ->and($result->getUnit())->toBe('px');
        });

        it('tries from invalid string returns null', function () {
            $result = SassNumber::tryFrom('invalid');

            expect($result)->toBeNull();
        });

        it('tries from array with value', function () {
            $result = SassNumber::tryFrom(['value' => 15.5, 'unit' => 'rem']);

            expect($result->getValue())->toBe(15.5)
                ->and($result->getUnit())->toBe('rem');
        });

        it('tries from array with empty unit', function () {
            $result = SassNumber::tryFrom(['value' => 20, 'unit' => '']);

            expect($result->getValue())->toBe(20.0)
                ->and($result->getUnit())->toBeNull();
        });

        it('tries from invalid value returns null', function () {
            expect(SassNumber::tryFrom(null))->toBeNull()
                ->and(SassNumber::tryFrom([]))->toBeNull()
                ->and(SassNumber::tryFrom(new stdClass()))->toBeNull();
        });
    });

    describe('toArray()', function () {
        it('converts to array', function () {
            $number = new SassNumber(10.5, 'px');
            $array  = $number->toArray();

            expect($array)->toBe([
                'value' => 10.5,
                'unit'  => 'px',
            ]);
        });

        it('converts unitless to array', function () {
            $number = new SassNumber(42.0);
            $array  = $number->toArray();

            expect($array)->toBe([
                'value' => 42.0,
                'unit'  => '',
            ]);
        });
    });
})->covers(SassNumber::class);
