<?php

declare(strict_types=1);

use DartSass\Exceptions\CompilationException;
use DartSass\Modules\MathModule;
use Tests\ReflectionAccessor;

beforeEach(function () {
    $this->mathModule = new MathModule();
    $this->accessor   = new ReflectionAccessor($this->mathModule);
});

describe('MathModule', function () {
    describe('ceil()', function () {
        it('rounds up positive number', function () {
            $result = $this->mathModule->ceil([5.1]);

            expect($result['value'])->toEqual(6.0)
                ->and($result['unit'])->toEqual('');
        });

        it('rounds up negative number', function () {
            $result = $this->mathModule->ceil([-5.9]);

            expect($result['value'])->toEqual(-5.0)
                ->and($result['unit'])->toEqual('');
        });

        it('rounds up number with unit', function () {
            $result = $this->mathModule->ceil([['value' => 5.7, 'unit' => 'px']]);

            expect($result['value'])->toEqual(6.0)
                ->and($result['unit'])->toEqual('px');
        });

        it('leaves integer unchanged', function () {
            $result = $this->mathModule->ceil([5]);

            expect($result['value'])->toEqual(5.0)
                ->and($result['unit'])->toEqual('');
        });

        it('throws exception for wrong argument count', function () {
            expect(fn() => $this->mathModule->ceil([]))
                ->toThrow(CompilationException::class, 'requires exactly one argument')
                ->and(fn() => $this->mathModule->ceil([1, 2]))
                ->toThrow(CompilationException::class, 'requires exactly one argument');
        });

        it('throws exception for non-numeric argument', function () {
            expect(fn() => $this->mathModule->ceil(['invalid']))
                ->toThrow(CompilationException::class, 'argument must be a number');
        });
    });

    describe('clamp()', function () {
        it('clamps value within range', function () {
            $result = $this->mathModule->clamp([
                ['value' => 5, 'unit' => 'px'],
                ['value' => 10, 'unit' => 'px'],
                ['value' => 15, 'unit' => 'px'],
            ]);

            expect($result['value'])->toEqual(10)
                ->and($result['unit'])->toEqual('px');
        });

        it('clamps value below minimum', function () {
            $result = $this->mathModule->clamp([
                ['value' => 3, 'unit' => 'px'],
                ['value' => 10, 'unit' => 'px'],
                ['value' => 15, 'unit' => 'px'],
            ]);

            expect($result['value'])->toEqual(10)
                ->and($result['unit'])->toEqual('px');
        });

        it('clamps value above maximum', function () {
            $result = $this->mathModule->clamp([
                ['value' => 20, 'unit' => 'px'],
                ['value' => 5, 'unit' => 'px'],
                ['value' => 15, 'unit' => 'px'],
            ]);

            expect($result['value'])->toEqual(15)
                ->and($result['unit'])->toEqual('px');
        });

        it('returns CSS function for incompatible units', function () {
            $result = $this->mathModule->clamp([
                ['value' => 5, 'unit' => 'px'],
                ['value' => 10, 'unit' => 'em'],
                ['value' => 15, 'unit' => 'px'],
            ]);

            expect($result)->toEqual(['css', [
                ['value' => 5, 'unit' => 'px'],
                ['value' => 10, 'unit' => 'em'],
                ['value' => 15, 'unit' => 'px'],
            ]]);
        });

        it('returns CSS function for non-numeric arguments', function () {
            $result = $this->mathModule->clamp(['var(--min)', '10px', 'var(--max)']);

            expect($result)->toEqual(['css', ['var(--min)', '10px', 'var(--max)']]);
        });

        it('throws exception for wrong argument count', function () {
            expect(fn() => $this->mathModule->clamp([5, 10]))
                ->toThrow(CompilationException::class, 'requires exactly three arguments')
                ->and(fn() => $this->mathModule->clamp([5, 10, 15, 20]))
                ->toThrow(CompilationException::class, 'requires exactly three arguments');
        });
    });

    describe('floor()', function () {
        it('rounds down positive number', function () {
            $result = $this->mathModule->floor([5.9]);

            expect($result['value'])->toEqual(5.0)
                ->and($result['unit'])->toEqual('');
        });

        it('rounds down negative number', function () {
            $result = $this->mathModule->floor([-5.9]);

            expect($result['value'])->toEqual(-6.0)
                ->and($result['unit'])->toEqual('');
        });

        it('rounds down number with unit', function () {
            $result = $this->mathModule->floor([['value' => 5.7, 'unit' => 'px']]);

            expect($result['value'])->toEqual(5.0)
                ->and($result['unit'])->toEqual('px');
        });

        it('leaves integer unchanged', function () {
            $result = $this->mathModule->floor([5]);

            expect($result['value'])->toEqual(5.0)
                ->and($result['unit'])->toEqual('');
        });

        it('throws exception for wrong argument count', function () {
            expect(fn() => $this->mathModule->floor([]))
                ->toThrow(CompilationException::class, 'requires exactly one argument')
                ->and(fn() => $this->mathModule->floor([1, 2]))
                ->toThrow(CompilationException::class, 'requires exactly one argument');
        });

        it('throws exception for non-numeric argument', function () {
            expect(fn() => $this->mathModule->floor(['invalid']))
                ->toThrow(CompilationException::class, 'argument must be a number');
        });
    });

    describe('max()', function () {
        it('returns maximum of simple numbers', function () {
            $result = $this->mathModule->max([5, 3, 8, 1]);

            expect($result['value'])->toEqual(8)
                ->and($result['unit'])->toEqual('');
        });

        it('returns maximum of numbers with same units', function () {
            $result = $this->mathModule->max([
                ['value' => 10, 'unit' => 'px'],
                ['value' => 5, 'unit' => 'px'],
                ['value' => 8, 'unit' => 'px'],
            ]);

            expect($result['value'])->toEqual(10)
                ->and($result['unit'])->toEqual('px');
        });

        it('returns maximum of mixed units as CSS function', function () {
            $result = $this->mathModule->max([
                ['value' => 10, 'unit' => 'px'],
                ['value' => 5, 'unit' => 'em'],
            ]);

            expect($result)->toEqual(['css', [
                ['value' => 10, 'unit' => 'px'],
                ['value' => 5, 'unit' => 'em'],
            ]]);
        });

        it('returns CSS function for non-numeric arguments', function () {
            $result = $this->mathModule->max(['var(--test)', 10]);

            expect($result)->toEqual(['css', ['var(--test)', 10]]);
        });

        it('throws exception for no arguments', function () {
            expect(fn() => $this->mathModule->max([]))
                ->toThrow(CompilationException::class, 'requires at least one argument');
        });
    });

    describe('min()', function () {
        it('returns minimum of simple numbers', function () {
            $result = $this->mathModule->min([5, 3, 8, 1]);

            expect($result['value'])->toEqual(1)
                ->and($result['unit'])->toEqual('');
        });

        it('returns minimum of numbers with same units', function () {
            $result = $this->mathModule->min([
                ['value' => 10, 'unit' => 'px'],
                ['value' => 5, 'unit' => 'px'],
                ['value' => 8, 'unit' => 'px'],
            ]);

            expect($result['value'])->toEqual(5)
                ->and($result['unit'])->toEqual('px');
        });

        it('returns minimum of mixed units as CSS function', function () {
            $result = $this->mathModule->min([
                ['value' => 10, 'unit' => 'px'],
                ['value' => 5, 'unit' => 'em'],
            ]);

            expect($result)->toEqual(['css', [
                ['value' => 10, 'unit' => 'px'],
                ['value' => 5, 'unit' => 'em'],
            ]]);
        });

        it('returns minimum when some values have no units', function () {
            $result = $this->mathModule->min([5, ['value' => 3, 'unit' => 'px']]);

            expect($result['value'])->toEqual(3)
                ->and($result['unit'])->toEqual('');
        });

        it('returns CSS function for non-numeric arguments', function () {
            $result = $this->mathModule->min(['var(--test)', 10]);

            expect($result)->toEqual(['css', ['var(--test)', 10]]);
        });

        it('throws exception for no arguments', function () {
            expect(fn() => $this->mathModule->min([]))
                ->toThrow(CompilationException::class, 'requires at least one argument');
        });
    });

    describe('round()', function () {
        it('rounds number', function () {
            $result = $this->mathModule->round([5.7]);

            expect($result['value'])->toEqual(6.0)
                ->and($result['unit'])->toEqual('');
        });

        it('rounds number with precision', function () {
            $result = $this->mathModule->round([5.678, ['value' => 2, 'unit' => '']]);

            expect($result['value'])->toEqual(5.68)
                ->and($result['unit'])->toEqual('');
        });

        it('rounds number with precision unit ignored', function () {
            $result = $this->mathModule->round([5.678, ['value' => 1, 'unit' => 'px']]);

            expect($result['value'])->toEqual(5.7)
                ->and($result['unit'])->toEqual('');
        });

        it('throws exception for wrong argument count', function () {
            expect(fn() => $this->mathModule->round([]))
                ->toThrow(CompilationException::class, 'requires one or two arguments')
                ->and(fn() => $this->mathModule->round([1, 2, 3]))
                ->toThrow(CompilationException::class, 'requires one or two arguments');
        });

        it('throws exception for non-numeric first argument', function () {
            expect(fn() => $this->mathModule->round(['invalid']))
                ->toThrow(CompilationException::class, 'argument must be a number');
        });
    });

    describe('abs()', function () {
        it('returns absolute value for positive number', function () {
            $result = $this->mathModule->abs([5.5]);

            expect($result['value'])->toEqual(5.5)
                ->and($result['unit'])->toEqual('');
        });

        it('returns absolute value for negative number', function () {
            $result = $this->mathModule->abs([-5.5]);

            expect($result['value'])->toEqual(5.5)
                ->and($result['unit'])->toEqual('');
        });

        it('returns absolute value for number with unit', function () {
            $result = $this->mathModule->abs([['value' => -10, 'unit' => 'px']]);

            expect($result['value'])->toEqual(10)
                ->and($result['unit'])->toEqual('px');
        });

        it('returns absolute value for zero', function () {
            $result = $this->mathModule->abs([0]);

            expect($result['value'])->toEqual(0)
                ->and($result['unit'])->toEqual('');
        });

        it('throws exception for wrong argument count', function () {
            expect(fn() => $this->mathModule->abs([]))
                ->toThrow(CompilationException::class, 'requires exactly one argument')
                ->and(fn() => $this->mathModule->abs([1, 2]))
                ->toThrow(CompilationException::class, 'requires exactly one argument');
        });

        it('throws exception for non-numeric argument', function () {
            expect(fn() => $this->mathModule->abs(['invalid']))
                ->toThrow(CompilationException::class, 'argument must be a number');
        });
    });

    describe('hypot()', function () {
        it('returns hypotenuse for two positive numbers', function () {
            $result = $this->mathModule->hypot([3, 4]);

            expect($result['value'])->toEqual(5.0)
                ->and($result['unit'])->toEqual('');
        });

        it('returns input for single argument', function () {
            $result = $this->mathModule->hypot([5]);

            expect($result['value'])->toEqual(5.0)
                ->and($result['unit'])->toEqual('');
        });

        it('handles negative numbers correctly', function () {
            $result = $this->mathModule->hypot([-3, 4]);

            expect($result['value'])->toEqual(5.0)
                ->and($result['unit'])->toEqual('');
        });

        it('preserves units for numbers with units', function () {
            $result = $this->mathModule->hypot([['value' => 3, 'unit' => 'px'], ['value' => 4, 'unit' => 'px']]);

            expect($result['value'])->toEqual(5.0)
                ->and($result['unit'])->toEqual('px');
        });

        it('handles zero values', function () {
            $result = $this->mathModule->hypot([0, 0]);

            expect($result['value'])->toEqual(0.0)
                ->and($result['unit'])->toEqual('');
        });

        it('throws exception for arguments with different units', function () {
            expect(fn() => $this->mathModule->hypot([['value' => 3, 'unit' => 'px'], ['value' => 4, 'unit' => 'em']]))
                ->toThrow(CompilationException::class, 'arguments must have the same unit');
        });

        it('throws exception for empty arguments array', function () {
            expect(fn() => $this->mathModule->hypot([]))
                ->toThrow(CompilationException::class, 'hypot() requires at least one argument');
        });

        it('throws exception for non-numeric arguments', function () {
            expect(fn() => $this->mathModule->hypot(['invalid']))
                ->toThrow(CompilationException::class, 'hypot() argument must be a number')
                ->and(fn() => $this->mathModule->hypot([3, 'invalid']))
                ->toThrow(CompilationException::class, 'hypot() argument must be a number');
        });
    });

    describe('log()', function () {
        it('calculates natural logarithm', function () {
            $result = $this->mathModule->log([10]);

            expect($result['value'])->toEqual(2.302585092994046)
                ->and($result['unit'])->toEqual('');
        });

        it('calculates logarithm with base', function () {
            $result = $this->mathModule->log([10, 10]);

            expect($result['value'])->toEqual(1.0)
                ->and($result['unit'])->toEqual('');
        });

        it('calculates logarithm of 1', function () {
            $result = $this->mathModule->log([1]);

            expect($result['value'])->toEqual(0.0)
                ->and($result['unit'])->toEqual('');
        });

        it('throws exception for wrong argument count', function () {
            expect(fn() => $this->mathModule->log([]))
                ->toThrow(CompilationException::class, 'requires one or two arguments')
                ->and(fn() => $this->mathModule->log([1, 2, 3]))
                ->toThrow(CompilationException::class, 'requires one or two arguments');
        });

        it('throws exception for non-numeric argument', function () {
            expect(fn() => $this->mathModule->log(['invalid']))
                ->toThrow(CompilationException::class, 'first argument must be a number');
        });

        it('throws exception for negative number', function () {
            expect(fn() => $this->mathModule->log([-1]))
                ->toThrow(CompilationException::class, 'first argument must be greater than zero');
        });

        it('throws exception for invalid base', function () {
            expect(fn() => $this->mathModule->log([10, -1]))
                ->toThrow(CompilationException::class, 'base must be greater than zero and not equal to one')
                ->and(fn() => $this->mathModule->log([10, 1]))
                ->toThrow(CompilationException::class, 'base must be greater than zero and not equal to one');
        });

        it('throws exception for arguments with units', function () {
            expect(fn() => $this->mathModule->log([['value' => 10, 'unit' => 'px']]))
                ->toThrow(CompilationException::class, 'arguments must be unitless');
        });

        it('throws exception for second argument null', function () {
            expect(fn() => $this->mathModule->log([10, 'invalid']))
                ->toThrow(CompilationException::class, 'second argument must be a number');
        });

        it('throws exception for second argument with units', function () {
            expect(fn() => $this->mathModule->log([10, ['value' => 2, 'unit' => 'px']]))
                ->toThrow(CompilationException::class, 'arguments must be unitless');
        });
    });

    describe('pow()', function () {
        it('calculates power of numbers', function () {
            $result = $this->mathModule->pow([2, 3]);

            expect($result['value'])->toEqual(8.0)
                ->and($result['unit'])->toEqual('');
        });

        it('calculates power with decimal numbers', function () {
            $result = $this->mathModule->pow([2.5, 2]);

            expect($result['value'])->toEqual(6.25)
                ->and($result['unit'])->toEqual('');
        });

        it('throws exception for wrong argument count', function () {
            expect(fn() => $this->mathModule->pow([]))
                ->toThrow(CompilationException::class, 'requires exactly two arguments')
                ->and(fn() => $this->mathModule->pow([1]))
                ->toThrow(CompilationException::class, 'requires exactly two arguments')
                ->and(fn() => $this->mathModule->pow([1, 2, 3]))
                ->toThrow(CompilationException::class, 'requires exactly two arguments');
        });

        it('throws exception for non-numeric arguments', function () {
            expect(fn() => $this->mathModule->pow(['invalid', 2]))
                ->toThrow(CompilationException::class, 'first argument must be a number')
                ->and(fn() => $this->mathModule->pow([2, 'invalid']))
                ->toThrow(CompilationException::class, 'second argument must be a number');
        });

        it('throws exception for arguments with units', function () {
            expect(fn() => $this->mathModule->pow([['value' => 2, 'unit' => 'px'], 3]))
                ->toThrow(CompilationException::class, 'arguments must be unitless')
                ->and(fn() => $this->mathModule->pow([2, ['value' => 3, 'unit' => 'px']]))
                ->toThrow(CompilationException::class, 'arguments must be unitless');
        });
    });

    describe('sqrt()', function () {
        it('calculates square root of perfect squares', function () {
            $result = $this->mathModule->sqrt([16]);

            expect($result['value'])->toEqual(4.0)
                ->and($result['unit'])->toEqual('');
        });

        it('calculates square root of decimal numbers', function () {
            $result = $this->mathModule->sqrt([2]);

            expect($result['value'])->toEqual(1.4142135623730951)
                ->and($result['unit'])->toEqual('');
        });

        it('calculates square root of zero', function () {
            $result = $this->mathModule->sqrt([0]);

            expect($result['value'])->toEqual(0.0)
                ->and($result['unit'])->toEqual('');
        });

        it('throws exception for wrong argument count', function () {
            expect(fn() => $this->mathModule->sqrt([]))
                ->toThrow(CompilationException::class, 'requires exactly one argument')
                ->and(fn() => $this->mathModule->sqrt([1, 2]))
                ->toThrow(CompilationException::class, 'requires exactly one argument');
        });

        it('throws exception for non-numeric argument', function () {
            expect(fn() => $this->mathModule->sqrt(['invalid']))
                ->toThrow(CompilationException::class, 'argument must be a number');
        });

        it('throws exception for negative numbers', function () {
            expect(fn() => $this->mathModule->sqrt([-1]))
                ->toThrow(CompilationException::class, 'argument must be non-negative');
        });

        it('throws exception for arguments with units', function () {
            expect(fn() => $this->mathModule->sqrt([['value' => 16, 'unit' => 'px']]))
                ->toThrow(CompilationException::class, 'argument must be unitless');
        });
    });

    describe('trigonometric functions', function () {
        describe('cos()', function () {
            it('calculates cosine of zero', function () {
                $result = $this->mathModule->cos([0]);

                expect($result['value'])->toEqual(1.0)
                    ->and($result['unit'])->toEqual('');
            });

            it('calculates cosine with radians', function () {
                $result = $this->mathModule->cos([['value' => pi(), 'unit' => 'rad']]);

                expect($result['value'])->toBeCloseTo(-1.0, 10)
                    ->and($result['unit'])->toBe('');
            });

            it('calculates cosine with degrees', function () {
                $result = $this->mathModule->cos([['value' => 180, 'unit' => 'deg']]);

                expect($result['value'])->toBeCloseTo(-1.0, 10)
                    ->and($result['unit'])->toBe('');
            });

            it('throws exception for wrong argument count', function () {
                expect(fn() => $this->mathModule->cos([]))
                    ->toThrow(CompilationException::class, 'requires exactly one argument')
                    ->and(fn() => $this->mathModule->cos([1, 2]))
                    ->toThrow(CompilationException::class, 'requires exactly one argument');
            });

            it('throws exception for non-numeric argument', function () {
                expect(fn() => $this->mathModule->cos(['invalid']))
                    ->toThrow(CompilationException::class, 'argument must be a number');
            });

            it('throws exception for invalid units', function () {
                expect(fn() => $this->mathModule->cos([['value' => 1, 'unit' => 'px']]))
                    ->toThrow(CompilationException::class, 'argument must be unitless, or have rad or deg units');
            });
        });

        describe('sin()', function () {
            it('calculates sine of zero', function () {
                $result = $this->mathModule->sin([0]);

                expect($result)->toEqual(['value' => 0.0, 'unit' => '']);
            });

            it('calculates sine with radians', function () {
                $result = $this->mathModule->sin([['value' => pi() / 2, 'unit' => 'rad']]);

                expect($result['value'])->toBeCloseTo(1.0, 10)
                    ->and($result['unit'])->toBe('');
            });

            it('calculates sine with degrees', function () {
                $result = $this->mathModule->sin([['value' => 90, 'unit' => 'deg']]);

                expect($result['value'])->toBeCloseTo(1.0, 10)
                    ->and($result['unit'])->toBe('');
            });

            it('throws exception for wrong argument count', function () {
                expect(fn() => $this->mathModule->sin([]))
                    ->toThrow(CompilationException::class, 'requires exactly one argument')
                    ->and(fn() => $this->mathModule->sin([1, 2]))
                    ->toThrow(CompilationException::class, 'requires exactly one argument');
            });

            it('throws exception for non-numeric argument', function () {
                expect(fn() => $this->mathModule->sin(['invalid']))
                    ->toThrow(CompilationException::class, 'argument must be a number');
            });

            it('throws exception for invalid units', function () {
                expect(fn() => $this->mathModule->sin([['value' => 1, 'unit' => 'px']]))
                    ->toThrow(CompilationException::class, 'argument must be unitless, or have rad or deg units');
            });
        });

        describe('tan()', function () {
            it('calculates tangent of zero', function () {
                $result = $this->mathModule->tan([0]);

                expect($result)->toEqual(['value' => 0.0, 'unit' => '']);
            });

            it('calculates tangent with radians', function () {
                $result = $this->mathModule->tan([['value' => pi() / 4, 'unit' => 'rad']]);

                expect($result['value'])->toBeCloseTo(1.0, 10)
                    ->and($result['unit'])->toBe('');
            });

            it('calculates tangent with degrees', function () {
                $result = $this->mathModule->tan([['value' => 45, 'unit' => 'deg']]);

                expect($result['value'])->toBeCloseTo(1.0, 10)
                    ->and($result['unit'])->toBe('');
            });

            it('throws exception for wrong argument count', function () {
                expect(fn() => $this->mathModule->tan([]))
                    ->toThrow(CompilationException::class, 'requires exactly one argument')
                    ->and(fn() => $this->mathModule->tan([1, 2]))
                    ->toThrow(CompilationException::class, 'requires exactly one argument');
            });

            it('throws exception for non-numeric argument', function () {
                expect(fn() => $this->mathModule->tan(['invalid']))
                    ->toThrow(CompilationException::class, 'argument must be a number');
            });

            it('throws exception for invalid units', function () {
                expect(fn() => $this->mathModule->tan([['value' => 1, 'unit' => 'px']]))
                    ->toThrow(CompilationException::class, 'argument must be unitless, or have rad or deg units');
            });
        });
    });

    describe('inverse trigonometric functions', function () {
        describe('acos()', function () {
            it('calculates arc cosine', function () {
                $result = $this->mathModule->acos([0]);

                expect($result['value'])->toBeCloseTo(90, 10)
                    ->and($result['unit'])->toBe('deg');
            });

            it('calculates arc cosine of one', function () {
                $result = $this->mathModule->acos([1]);

                expect($result)->toEqual(['value' => 0.0, 'unit' => 'deg']);
            });

            it('throws exception for wrong argument count', function () {
                expect(fn() => $this->mathModule->acos([]))
                    ->toThrow(CompilationException::class, 'requires exactly one argument')
                    ->and(fn() => $this->mathModule->acos([1, 2]))
                    ->toThrow(CompilationException::class, 'requires exactly one argument');
            });

            it('throws exception for non-numeric argument', function () {
                expect(fn() => $this->mathModule->acos(['invalid']))
                    ->toThrow(CompilationException::class, 'argument must be a number');
            });

            it('throws exception for values outside domain', function () {
                expect(fn() => $this->mathModule->acos([2]))
                    ->toThrow(CompilationException::class, 'argument must be between -1 and 1')
                    ->and(fn() => $this->mathModule->acos([-2]))
                    ->toThrow(CompilationException::class, 'argument must be between -1 and 1');
            });

            it('throws exception for arguments with units', function () {
                expect(fn() => $this->mathModule->acos([['value' => 0, 'unit' => 'px']]))
                    ->toThrow(CompilationException::class, 'argument must be unitless');
            });
        });

        describe('asin()', function () {
            it('calculates arc sine', function () {
                $result = $this->mathModule->asin([0]);

                expect($result)->toEqual(['value' => 0.0, 'unit' => 'deg']);
            });

            it('calculates arc sine of one', function () {
                $result = $this->mathModule->asin([1]);

                expect($result['value'])->toBeCloseTo(90, 10)
                    ->and($result['unit'])->toBe('deg');
            });

            it('throws exception for wrong argument count', function () {
                expect(fn() => $this->mathModule->asin([]))
                    ->toThrow(CompilationException::class, 'requires exactly one argument')
                    ->and(fn() => $this->mathModule->asin([1, 2]))
                    ->toThrow(CompilationException::class, 'requires exactly one argument');
            });

            it('throws exception for non-numeric argument', function () {
                expect(fn() => $this->mathModule->asin(['invalid']))
                    ->toThrow(CompilationException::class, 'argument must be a number');
            });

            it('throws exception for values outside domain', function () {
                expect(fn() => $this->mathModule->asin([2]))
                    ->toThrow(CompilationException::class, 'argument must be between -1 and 1')
                    ->and(fn() => $this->mathModule->asin([-2]))
                    ->toThrow(CompilationException::class, 'argument must be between -1 and 1');
            });

            it('throws exception for arguments with units', function () {
                expect(fn() => $this->mathModule->asin([['value' => 0, 'unit' => 'px']]))
                    ->toThrow(CompilationException::class, 'argument must be unitless');
            });
        });

        describe('atan()', function () {
            it('calculates arc tangent', function () {
                $result = $this->mathModule->atan([0]);

                expect($result)->toEqual(['value' => 0.0, 'unit' => 'deg']);
            });

            it('calculates arc tangent of one', function () {
                $result = $this->mathModule->atan([1]);

                expect($result['value'])->toBeCloseTo(45, 10)
                    ->and($result['unit'])->toBe('deg');
            });

            it('throws exception for wrong argument count', function () {
                expect(fn() => $this->mathModule->atan([]))
                    ->toThrow(CompilationException::class, 'requires exactly one argument')
                    ->and(fn() => $this->mathModule->atan([1, 2]))
                    ->toThrow(CompilationException::class, 'requires exactly one argument');
            });

            it('throws exception for non-numeric argument', function () {
                expect(fn() => $this->mathModule->atan(['invalid']))
                    ->toThrow(CompilationException::class, 'argument must be a number');
            });

            it('throws exception for arguments with units', function () {
                expect(fn() => $this->mathModule->atan([['value' => 0, 'unit' => 'px']]))
                    ->toThrow(CompilationException::class, 'argument must be unitless');
            });
        });

        describe('atan2()', function () {
            it('calculates arc tangent of two variables', function () {
                $result = $this->mathModule->atan2([1, 1]);

                expect($result['value'])->toBeCloseTo(45, 10)
                    ->and($result['unit'])->toBe('deg');
            });

            it('calculates arc tangent with zero y', function () {
                $result = $this->mathModule->atan2([0, 1]);

                expect($result)->toEqual(['value' => 0.0, 'unit' => 'deg']);
            });

            it('throws exception for wrong argument count', function () {
                expect(fn() => $this->mathModule->atan2([]))
                    ->toThrow(CompilationException::class, 'requires exactly two arguments')
                    ->and(fn() => $this->mathModule->atan2([1]))
                    ->toThrow(CompilationException::class, 'requires exactly two arguments')
                    ->and(fn() => $this->mathModule->atan2([1, 2, 3]))
                    ->toThrow(CompilationException::class, 'requires exactly two arguments');
            });

            it('throws exception for non-numeric arguments', function () {
                expect(fn() => $this->mathModule->atan2(['invalid', 1]))
                    ->toThrow(CompilationException::class, 'first argument must be a number')
                    ->and(fn() => $this->mathModule->atan2([1, 'invalid']))
                    ->toThrow(CompilationException::class, 'second argument must be a number');
            });

            it('throws exception for arguments with units', function () {
                expect(fn() => $this->mathModule->atan2([['value' => 1, 'unit' => 'px'], 1]))
                    ->toThrow(CompilationException::class, 'arguments must be unitless')
                    ->and(fn() => $this->mathModule->atan2([1, ['value' => 1, 'unit' => 'px']]))
                    ->toThrow(CompilationException::class, 'arguments must be unitless');
            });
        });
    });

    describe('utility functions', function () {
        describe('compatible()', function () {
            it('returns true for compatible units', function () {
                $result = $this->mathModule->compatible([
                    ['value' => 10, 'unit' => 'px'],
                    ['value' => 5, 'unit' => 'px'],
                ]);

                expect($result)->toEqual(['value' => 'true', 'unit' => '']);
            });

            it('returns true for unitless values', function () {
                $result = $this->mathModule->compatible([[ 'value' => 10, 'unit' => '' ], [ 'value' => 5, 'unit' => '' ]]);

                expect($result)->toEqual(['value' => 'true', 'unit' => '']);
            });

            it('returns true for unitless and unit values', function () {
                $result = $this->mathModule->compatible([
                    ['value' => 10, 'unit' => ''],
                    ['value' => 5, 'unit' => 'px'],
                ]);

                expect($result)->toEqual(['value' => 'true', 'unit' => '']);
            });

            it('returns false for incompatible units', function () {
                $result = $this->mathModule->compatible([
                    ['value' => 10, 'unit' => 'px'],
                    ['value' => 5, 'unit' => 'em'],
                ]);

                expect($result)->toEqual(['value' => 'false', 'unit' => '']);
            });

            it('throws exception for wrong argument count', function () {
                expect(fn() => $this->mathModule->compatible([]))
                    ->toThrow(CompilationException::class, 'requires exactly two arguments')
                    ->and(fn() => $this->mathModule->compatible([1]))
                    ->toThrow(CompilationException::class, 'requires exactly two arguments')
                    ->and(fn() => $this->mathModule->compatible([1, 2, 3]))
                    ->toThrow(CompilationException::class, 'requires exactly two arguments');
            });

            it('throws exception for non-numeric arguments', function () {
                expect(fn() => $this->mathModule->compatible(['invalid', 2]))
                    ->toThrow(CompilationException::class, 'arguments must be numbers')
                    ->and(fn() => $this->mathModule->compatible([1, 'invalid']))
                    ->toThrow(CompilationException::class, 'arguments must be numbers');
            });
        });

        describe('isUnitless()', function () {
            it('returns true for unitless value', function () {
                $result = $this->mathModule->isUnitless([10]);

                expect($result)->toEqual(['value' => 'true', 'unit' => '']);
            });

            it('returns false for value with unit', function () {
                $result = $this->mathModule->isUnitless([['value' => 10, 'unit' => 'px']]);

                expect($result)->toEqual(['value' => 'false', 'unit' => '']);
            });

            it('throws exception for wrong argument count', function () {
                expect(fn() => $this->mathModule->isUnitless([]))
                    ->toThrow(CompilationException::class, 'requires exactly one argument')
                    ->and(fn() => $this->mathModule->isUnitless([1, 2]))
                    ->toThrow(CompilationException::class, 'requires exactly one argument');
            });

            it('throws exception for non-numeric argument', function () {
                expect(fn() => $this->mathModule->isUnitless(['invalid']))
                    ->toThrow(CompilationException::class, 'argument must be a number');
            });
        });

        describe('unit()', function () {
            it('returns unit for value with unit', function () {
                $result = $this->mathModule->unit([['value' => 10, 'unit' => 'px']]);

                expect($result)->toEqual(['value' => '"px"', 'unit' => '']);
            });

            it('returns empty string for unitless value', function () {
                $result = $this->mathModule->unit([10]);

                expect($result)->toEqual(['value' => '""', 'unit' => '']);
            });

            it('throws exception for wrong argument count', function () {
                expect(fn() => $this->mathModule->unit([]))
                    ->toThrow(CompilationException::class, 'requires exactly one argument')
                    ->and(fn() => $this->mathModule->unit([1, 2]))
                    ->toThrow(CompilationException::class, 'requires exactly one argument');
            });

            it('throws exception for non-numeric argument', function () {
                expect(fn() => $this->mathModule->unit(['invalid']))
                    ->toThrow(CompilationException::class, 'argument must be a number');
            });
        });

        describe('div()', function () {
            it('divides two numbers', function () {
                $result = $this->mathModule->div([10, 2]);

                expect($result)->toEqual(['value' => 5.0, 'unit' => '']);
            });

            it('divides numbers with same units', function () {
                $result = $this->mathModule->div([
                    ['value' => 10, 'unit' => 'px'],
                    ['value' => 2, 'unit' => 'px'],
                ]);

                expect($result)->toEqual(['value' => 5.0, 'unit' => '']);
            });

            it('divides numbers with different units', function () {
                $result = $this->mathModule->div([
                    ['value' => 10, 'unit' => 'px'],
                    ['value' => 2, 'unit' => 'em'],
                ]);

                expect($result)->toEqual(['value' => 5.0, 'unit' => 'px/em']);
            });

            it('divides unitless number by number with unit', function () {
                $result = $this->mathModule->div([
                    10,
                    ['value' => 2, 'unit' => 'px'],
                ]);

                expect($result)->toEqual(['value' => 5.0, 'unit' => '/px']);
            });

            it('divides number with unit by unitless number', function () {
                $result = $this->mathModule->div([
                    ['value' => 10, 'unit' => 'px'],
                    2,
                ]);

                expect($result)->toEqual(['value' => 5.0, 'unit' => 'px']);
            });

            it('throws exception for wrong argument count', function () {
                expect(fn() => $this->mathModule->div([]))
                    ->toThrow(CompilationException::class, 'requires exactly two arguments')
                    ->and(fn() => $this->mathModule->div([1]))
                    ->toThrow(CompilationException::class, 'requires exactly two arguments')
                    ->and(fn() => $this->mathModule->div([1, 2, 3]))
                    ->toThrow(CompilationException::class, 'requires exactly two arguments');
            });

            it('throws exception for non-numeric arguments', function () {
                expect(fn() => $this->mathModule->div(['invalid', 2]))
                    ->toThrow(CompilationException::class, 'first argument must be a number')
                    ->and(fn() => $this->mathModule->div([1, 'invalid']))
                    ->toThrow(CompilationException::class, 'second argument must be a number');
            });

            it('throws exception for division by zero', function () {
                expect(fn() => $this->mathModule->div([10, 0]))
                    ->toThrow(CompilationException::class, 'second argument must not be zero');
            });
        });

        describe('percentage()', function () {
            it('converts decimal to percentage', function () {
                $result = $this->mathModule->percentage([0.5]);

                expect($result)->toEqual(['value' => 50.0, 'unit' => '%']);
            });

            it('converts zero to percentage', function () {
                $result = $this->mathModule->percentage([0]);

                expect($result)->toEqual(['value' => 0.0, 'unit' => '%']);
            });

            it('converts whole number to percentage', function () {
                $result = $this->mathModule->percentage([2]);

                expect($result)->toEqual(['value' => 200.0, 'unit' => '%']);
            });

            it('throws exception for wrong argument count', function () {
                expect(fn() => $this->mathModule->percentage([]))
                    ->toThrow(CompilationException::class, 'requires exactly one argument')
                    ->and(fn() => $this->mathModule->percentage([1, 2]))
                    ->toThrow(CompilationException::class, 'requires exactly one argument');
            });

            it('throws exception for non-numeric argument', function () {
                expect(fn() => $this->mathModule->percentage(['invalid']))
                    ->toThrow(CompilationException::class, 'argument must be a number');
            });

            it('throws exception for arguments with units', function () {
                expect(fn() => $this->mathModule->percentage([['value' => 0.5, 'unit' => 'px']]))
                    ->toThrow(CompilationException::class, 'argument must be unitless');
            });
        });

        describe('random()', function () {
            it('generates random number with no arguments', function () {
                $result = $this->mathModule->random([]);

                expect($result)->toBeArray()
                    ->and($result['unit'])->toBe('')
                    ->and($result['value'])->toBeGreaterThanOrEqual(0)
                    ->and($result['value'])->toBeLessThan(1);
            });

            it('generates random number with limit', function () {
                $result = $this->mathModule->random([10]);

                expect($result)->toBeArray()
                    ->and($result['unit'])->toBe('')
                    ->and($result['value'])->toBeInt()
                    ->and($result['value'])->toBeGreaterThanOrEqual(0)
                    ->and($result['value'])->toBeLessThan(10);
            });

            it('throws exception for too many arguments', function () {
                expect(fn() => $this->mathModule->random([1, 2]))
                    ->toThrow(CompilationException::class, 'requires zero or one argument');
            });

            it('throws exception for non-numeric argument', function () {
                expect(fn() => $this->mathModule->random(['invalid']))
                    ->toThrow(CompilationException::class, 'argument must be a number');
            });

            it('throws exception for arguments with units', function () {
                expect(fn() => $this->mathModule->random([['value' => 10, 'unit' => 'px']]))
                    ->toThrow(CompilationException::class, 'argument must be unitless');
            });

            it('throws exception for non-positive limit', function () {
                expect(fn() => $this->mathModule->random([0]))
                    ->toThrow(CompilationException::class, 'argument must be greater than zero')
                    ->and(fn() => $this->mathModule->random([-1]))
                    ->toThrow(CompilationException::class, 'argument must be greater than zero');
            });
        });
    });

    describe('normalize()', function () {
        it('normalizes numeric string', function () {
            $result = $this->accessor->callMethod('normalize', ['42']);

            expect($result)->toEqual(['value' => 42.0, 'unit' => '']);
        });

        it('normalizes numeric array', function () {
            $result = $this->accessor->callMethod('normalize', [
                ['value' => 42, 'unit' => 'px'],
            ]);

            expect($result)->toEqual(['value' => 42.0, 'unit' => 'px']);
        });

        it('normalizes array without unit', function () {
            $result = $this->accessor->callMethod('normalize', [
                ['value' => 42],
            ]);

            expect($result)->toEqual(['value' => 42.0, 'unit' => '']);
        });

        it('returns null for invalid input', function () {
            expect($this->accessor->callMethod('normalize', ['invalid']))
                ->toBeNull()
                ->and($this->accessor->callMethod('normalize', [[]]))
                ->toBeNull();
        });
    });

    describe('areUnitsCompatible()', function () {
        it('considers same units as compatible', function () {
            $result = $this->accessor->callMethod('areUnitsCompatible', ['px', 'px']);

            expect($result)->toBeTrue();
        });

        it('considers empty units as compatible with any unit', function () {
            expect($this->accessor->callMethod('areUnitsCompatible', ['', 'px']))
                ->toBeTrue()
                ->and($this->accessor->callMethod('areUnitsCompatible', ['px', '']))
                ->toBeTrue();
        });

        it('considers different units as incompatible', function () {
            $result = $this->accessor->callMethod('areUnitsCompatible', ['px', 'em']);

            expect($result)->toBeFalse();
        });
    });
})->covers(MathModule::class);
