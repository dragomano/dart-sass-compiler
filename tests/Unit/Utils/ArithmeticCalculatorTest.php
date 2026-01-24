<?php

declare(strict_types=1);

use DartSass\Exceptions\CompilationException;
use DartSass\Utils\ArithmeticCalculator;
use DartSass\Values\SassNumber;

describe('ArithmeticCalculator', function () {
    describe('add()', function () {
        it('adds SassNumber instances', function () {
            $left  = new SassNumber(10, 'px');
            $right = new SassNumber(5, 'px');

            $result = ArithmeticCalculator::add($left, $right);

            expect($result->getValue())->toBe(15.0)
                ->and($result->getUnit())->toBe('px');
        });

        it('adds numeric values', function () {
            $result = ArithmeticCalculator::add(10.5, 2.5);

            expect($result->getValue())->toBe(13.0)
                ->and($result->getUnit())->toBeNull();
        });

        it('adds mixed types', function () {
            $left  = new SassNumber(10, 'px');
            $right = 5;

            $result = ArithmeticCalculator::add($left, $right);

            expect($result->getValue())->toBe(15.0)
                ->and($result->getUnit())->toBe('px');
        });
    });

    describe('subtract()', function () {
        it('subtracts SassNumber instances', function () {
            $left  = new SassNumber(10, 'px');
            $right = new SassNumber(3, 'px');

            $result = ArithmeticCalculator::subtract($left, $right);

            expect($result->getValue())->toBe(7.0)
                ->and($result->getUnit())->toBe('px');
        });

        it('subtracts numeric values', function () {
            $result = ArithmeticCalculator::subtract(10.5, 2.5);

            expect($result->getValue())->toBe(8.0)
                ->and($result->getUnit())->toBeNull();
        });
    });

    describe('multiply()', function () {
        it('multiplies SassNumber instances', function () {
            $left  = new SassNumber(6, 'px');
            $right = new SassNumber(2);

            $result = ArithmeticCalculator::multiply($left, $right);

            expect($result->getValue())->toBe(12.0)
                ->and($result->getUnit())->toBe('px');
        });

        it('multiplies numeric values', function () {
            $result = ArithmeticCalculator::multiply(6.5, 2.0);

            expect($result->getValue())->toBe(13.0)
                ->and($result->getUnit())->toBeNull();
        });
    });

    describe('divide()', function () {
        it('divides SassNumber instances', function () {
            $left  = new SassNumber(10, 'px');
            $right = new SassNumber(2, 'px');

            $result = ArithmeticCalculator::divide($left, $right);

            expect($result->getValue())->toBe(5.0)
                ->and($result->getUnit())->toBeNull();
        });

        it('divides numeric values', function () {
            $result = ArithmeticCalculator::divide(10.5, 2.0);

            expect($result->getValue())->toBe(5.25)
                ->and($result->getUnit())->toBeNull();
        });

        it('throws on division by zero', function () {
            expect(fn() => ArithmeticCalculator::divide(10, 0))
                ->toThrow(CompilationException::class, 'Division by zero');
        });

        it('divides with unit conversion', function () {
            $left  = new SassNumber(96, 'px');
            $right = new SassNumber(1, 'in'); // 1 in = 96 px

            $result = ArithmeticCalculator::divide($left, $right);

            expect($result->getValue())->toBe(1.0)
                ->and($result->getUnit())->toBeNull();
        });

        it('throws on division with incompatible units', function () {
            $left  = new SassNumber(10, 'px');
            $right = new SassNumber(5, 'deg');

            expect(fn() => ArithmeticCalculator::divide($left, $right))
                ->toThrow(CompilationException::class, 'Cannot divide px by deg: incompatible units');
        });

        it('divides same non-convertible units', function () {
            $left  = new SassNumber(10, '%');
            $right = new SassNumber(2, '%');

            $result = ArithmeticCalculator::divide($left, $right);

            expect($result->getValue())->toBe(5.0)
                ->and($result->getUnit())->toBeNull();
        });
    });

    describe('modulo()', function () {
        it('calculates modulo', function () {
            $left  = new SassNumber(10, 'px');
            $right = new SassNumber(3, 'px');

            $result = ArithmeticCalculator::modulo($left, $right);

            expect($result->getValue())->toBe(1.0)
                ->and($result->getUnit())->toBe('px');
        });

        it('calculates modulo with unit conversion', function () {
            $left  = new SassNumber(10, 'px');
            $right = new SassNumber(0.125, 'in'); // 0.125 in = 12 px

            $result = ArithmeticCalculator::modulo($left, $right);

            expect($result->getValue())->toBe(10.0)
                ->and($result->getUnit())->toBe('px');
        });

        it('throws on modulo by zero', function () {
            expect(fn() => ArithmeticCalculator::modulo(10, 0))
                ->toThrow(CompilationException::class, 'Modulo by zero');
        });

        it('throws on modulo with incompatible units', function () {
            $left  = new SassNumber(10, 'px');
            $right = new SassNumber(3, 'deg');

            expect(fn() => ArithmeticCalculator::modulo($left, $right))
                ->toThrow(CompilationException::class, "Incompatible units for '%': px and deg");
        });
    });

    describe('negate()', function () {
        it('negates SassNumber', function () {
            $number = new SassNumber(10, 'px');

            $result = ArithmeticCalculator::negate($number);

            expect($result->getValue())->toBe(-10.0)
                ->and($result->getUnit())->toBe('px');
        });

        it('negates numeric value', function () {
            $result = ArithmeticCalculator::negate(5.5);

            expect($result->getValue())->toBe(-5.5)
                ->and($result->getUnit())->toBeNull();
        });
    });

    describe('calculate()', function () {
        it('calculates with add operator', function () {
            $left  = new SassNumber(10, 'px');
            $right = new SassNumber(5, 'px');

            $result = ArithmeticCalculator::calculate('+', $left, $right);

            expect($result->getValue())->toBe(15.0)
                ->and($result->getUnit())->toBe('px');
        });

        it('calculates with subtract operator', function () {
            $result = ArithmeticCalculator::calculate('-', 10, 3);

            expect($result->getValue())->toBe(7.0)
                ->and($result->getUnit())->toBeNull();
        });

        it('calculates with multiply operator', function () {
            $result = ArithmeticCalculator::calculate('*', 6, 7);

            expect($result->getValue())->toBe(42.0)
                ->and($result->getUnit())->toBeNull();
        });

        it('calculates with divide operator', function () {
            $result = ArithmeticCalculator::calculate('/', 15, 3);

            expect($result->getValue())->toBe(5.0)
                ->and($result->getUnit())->toBeNull();
        });

        it('calculates with modulo operator', function () {
            $result = ArithmeticCalculator::calculate('%', 17, 5);

            expect($result->getValue())->toBe(2.0)
                ->and($result->getUnit())->toBeNull();
        });

        it('returns null for invalid operands in calculate', function () {
            $result = ArithmeticCalculator::calculate('+', 'invalid', 5);

            expect($result)->toBeNull();
        });

        it('throws on unknown operator', function () {
            expect(fn() => ArithmeticCalculator::calculate('^', 2, 3))
                ->toThrow(CompilationException::class, 'Unknown arithmetic operator: ^');
        });
    });

    describe('normalizeUnits()', function () {
        it('normalizes units for same units', function () {
            $left  = new SassNumber(10, 'px');
            $right = new SassNumber(5, 'px');

            [$leftVal, $rightVal, $unit] = ArithmeticCalculator::normalizeUnits($left, $right);

            expect($leftVal)->toBe(10.0)
                ->and($rightVal)->toBe(5.0)
                ->and($unit)->toBe('px');
        });

        it('normalizes units for compatible units', function () {
            $left  = new SassNumber(96, 'px');
            $right = new SassNumber(1, 'in');

            [$leftVal, $rightVal, $unit] = ArithmeticCalculator::normalizeUnits($left, $right);

            expect($leftVal)->toBe(96.0)
                ->and($rightVal)->toBe(96.0)
                ->and($unit)->toBe('px');
        });

        it('normalizes units for unitless numbers', function () {
            $left  = new SassNumber(10);
            $right = new SassNumber(5);

            [$leftVal, $rightVal, $unit] = ArithmeticCalculator::normalizeUnits($left, $right);

            expect($leftVal)->toBe(10.0)
                ->and($rightVal)->toBe(5.0)
                ->and($unit)->toBeNull();
        });

        it('normalizes units for mixed unit and unitless', function () {
            $left  = new SassNumber(10, 'px');
            $right = new SassNumber(5);

            [$leftVal, $rightVal, $unit] = ArithmeticCalculator::normalizeUnits($left, $right);

            expect($leftVal)->toBe(10.0)
                ->and($rightVal)->toBe(5.0)
                ->and($unit)->toBe('px');
        });

        it('normalizes units for unitless and unit', function () {
            $left  = new SassNumber(10);
            $right = new SassNumber(5, 'px');

            [$leftVal, $rightVal, $unit] = ArithmeticCalculator::normalizeUnits($left, $right);

            expect($leftVal)->toBe(10.0)
                ->and($rightVal)->toBe(5.0)
                ->and($unit)->toBe('px');
        });

        it('throws on incompatible units in normalizeUnits', function () {
            $left  = new SassNumber(10, 'px');
            $right = new SassNumber(5, 'deg');

            expect(fn() => ArithmeticCalculator::normalizeUnits($left, $right))
                ->toThrow(CompilationException::class, 'Incompatible units: px and deg');
        });

        it('normalizes units for same non-convertible units', function () {
            $left  = new SassNumber(10, '%');
            $right = new SassNumber(2, '%');

            [$leftVal, $rightVal, $unit] = ArithmeticCalculator::normalizeUnits($left, $right);

            expect($leftVal)->toBe(10.0)
                ->and($rightVal)->toBe(2.0)
                ->and($unit)->toBe('%');
        });
    });

    describe('resolveResultUnit()', function () {
        it('resolves result unit for addition', function () {
            expect(ArithmeticCalculator::resolveResultUnit('+', 'px', 'px'))->toBe('px')
                ->and(ArithmeticCalculator::resolveResultUnit('+', 'px', null))->toBe('px')
                ->and(ArithmeticCalculator::resolveResultUnit('+', null, 'px'))->toBe('px')
                ->and(ArithmeticCalculator::resolveResultUnit('+', null, null))->toBeNull();
        });

        it('resolves result unit for subtraction', function () {
            expect(ArithmeticCalculator::resolveResultUnit('-', 'px', 'px'))->toBe('px')
                ->and(ArithmeticCalculator::resolveResultUnit('-', 'px', null))->toBe('px');
        });

        it('resolves result unit for multiplication', function () {
            expect(ArithmeticCalculator::resolveResultUnit('*', 'px', null))->toBe('px')
                ->and(ArithmeticCalculator::resolveResultUnit('*', null, 'px'))->toBe('px')
                ->and(ArithmeticCalculator::resolveResultUnit('*', null, null))->toBeNull();
        });

        it('resolves result unit for division', function () {
            expect(ArithmeticCalculator::resolveResultUnit('/', 'px', 'px'))->toBeNull()
                ->and(ArithmeticCalculator::resolveResultUnit('/', 'px', null))->toBe('px')
                ->and(ArithmeticCalculator::resolveResultUnit('/', null, 'px'))->toBeNull();
        });

        it('resolves result unit for modulo', function () {
            expect(ArithmeticCalculator::resolveResultUnit('%', 'px', 'px'))->toBe('px')
                ->and(ArithmeticCalculator::resolveResultUnit('%', 'px', null))->toBe('px');
        });
    });

    describe('toSassNumber()', function () {
        it('converts SassNumber to SassNumber', function () {
            $original = new SassNumber(10, 'px');

            $result = ArithmeticCalculator::toSassNumber($original);

            expect($result)->toBe($original);
        });

        it('converts int to SassNumber', function () {
            $result = ArithmeticCalculator::toSassNumber(42);

            expect($result->getValue())->toBe(42.0)
                ->and($result->getUnit())->toBeNull();
        });

        it('converts float to SassNumber', function () {
            $result = ArithmeticCalculator::toSassNumber(3.14);

            expect($result->getValue())->toBe(3.14)
                ->and($result->getUnit())->toBeNull();
        });

        it('converts string with unit to SassNumber', function () {
            $result = ArithmeticCalculator::toSassNumber('10px');

            expect($result->getValue())->toBe(10.0)
                ->and($result->getUnit())->toBe('px');
        });

        it('converts numeric string to SassNumber', function () {
            $result = ArithmeticCalculator::toSassNumber('42.5');

            expect($result->getValue())->toBe(42.5)
                ->and($result->getUnit())->toBeNull();
        });

        it('converts array with value to SassNumber', function () {
            $result = ArithmeticCalculator::toSassNumber(['value' => 15.5, 'unit' => 'rem']);

            expect($result->getValue())->toBe(15.5)
                ->and($result->getUnit())->toBe('rem');
        });

        it('throws on invalid value conversion', function () {
            expect(fn() => ArithmeticCalculator::toSassNumber('invalid'))
                ->toThrow(CompilationException::class, 'Cannot convert value to SassNumber: "invalid"');
        });

        it('throws on object conversion', function () {
            expect(fn() => ArithmeticCalculator::toSassNumber(new stdClass()))
                ->toThrow(CompilationException::class, 'Cannot convert value to SassNumber: stdClass');
        });

        it('throws on null conversion', function () {
            expect(fn() => ArithmeticCalculator::toSassNumber(null))
                ->toThrow(CompilationException::class, 'Cannot convert value to SassNumber: null');
        });

        it('throws on bool conversion', function () {
            expect(fn() => ArithmeticCalculator::toSassNumber(true))
                ->toThrow(CompilationException::class, 'Cannot convert value to SassNumber: true')
                ->and(fn() => ArithmeticCalculator::toSassNumber(false))
                ->toThrow(CompilationException::class, 'Cannot convert value to SassNumber: false');
        });

        it('throws on array conversion', function () {
            expect(fn() => ArithmeticCalculator::toSassNumber([1, 2, 3]))
                ->toThrow(CompilationException::class, 'Cannot convert value to SassNumber: array');
        });

        it('throws on resource conversion', function () {
            $resource = fopen('php://temp', 'r');

            try {
                expect(fn() => ArithmeticCalculator::toSassNumber($resource))
                    ->toThrow(function (CompilationException $e) {
                        expect($e)->toBeInstanceOf(CompilationException::class)
                            ->and($e->getMessage())->toStartWith('Cannot convert value to SassNumber: Resource id #');
                    });
            } finally {
                fclose($resource);
            }
        });
    });

    describe('tryToSassNumber()', function () {
        it('tries to convert valid value', function () {
            $result = ArithmeticCalculator::tryToSassNumber(42);

            expect($result->getValue())->toBe(42.0);
        });

        it('tries to convert invalid value returns null', function () {
            $result = ArithmeticCalculator::tryToSassNumber('invalid');

            expect($result)->toBeNull();
        });
    });

    describe('areUnitsCompatible()', function () {
        it('checks unit compatibility', function () {
            expect(ArithmeticCalculator::areUnitsCompatible('px', 'px'))->toBeTrue()
                ->and(ArithmeticCalculator::areUnitsCompatible('px', 'em'))->toBeFalse()
                ->and(ArithmeticCalculator::areUnitsCompatible('', ''))->toBeTrue()
                ->and(ArithmeticCalculator::areUnitsCompatible('%', '%'))->toBeTrue();
        });
    });

    describe('convertUnit()', function () {
        it('converts unit values', function () {
            $result = ArithmeticCalculator::convertUnit(96, 'px', 'in');

            expect($result)->toBe(1.0);
        });

        it('converts same units', function () {
            $result = ArithmeticCalculator::convertUnit(10, 'px', 'px');

            expect($result)->toBe(10.0);
        });

        it('converts same non-convertible units', function () {
            $result = ArithmeticCalculator::convertUnit(10, '%', '%');

            expect($result)->toBe(10.0);
        });
    });
})->covers(ArithmeticCalculator::class);
