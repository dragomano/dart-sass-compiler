<?php declare(strict_types=1);

use DartSass\Utils\MathFunctions;
use DartSass\Utils\ValueFormatter;
use DartSass\Exceptions\CompilationException;
use Tests\ReflectionAccessor;

beforeEach(function () {
    $this->valueFormatter = new ValueFormatter();
    $this->mathFunctions  = new MathFunctions($this->valueFormatter);
    $this->accessor       = new ReflectionAccessor($this->mathFunctions);
});

describe('MathFunctions', function () {
    describe('ceil', function () {
      it('rounds up positive number', function () {
        $result = $this->mathFunctions->ceil([5.1]);

        expect($result)->toEqual(['value' => 6.0, 'unit' => '']);
      });

      it('rounds up negative number', function () {
        $result = $this->mathFunctions->ceil([-5.9]);

        expect($result)->toEqual(['value' => -5.0, 'unit' => '']);
      });

      it('rounds up number with unit', function () {
        $result = $this->mathFunctions->ceil([['value' => 5.7, 'unit' => 'px']]);

        expect($result)->toEqual(['value' => 6.0, 'unit' => 'px']);
      });

      it('leaves integer unchanged', function () {
        $result = $this->mathFunctions->ceil([5]);

        expect($result)->toEqual(['value' => 5.0, 'unit' => '']);
      });

      it('throws exception for wrong argument count', function () {
        expect(fn() => $this->mathFunctions->ceil([]))
          ->toThrow(CompilationException::class, 'requires exactly one argument')
          ->and(fn() => $this->mathFunctions->ceil([1, 2]))
          ->toThrow(CompilationException::class, 'requires exactly one argument');
      });

      it('throws exception for non-numeric argument', function () {
        expect(fn() => $this->mathFunctions->ceil(['invalid']))
          ->toThrow(CompilationException::class, 'argument must be a number');
      });
    });

    describe('clamp', function () {
      it('clamps value within range', function () {
        $result = $this->mathFunctions->clamp([
          ['value' => 5, 'unit' => 'px'],
          ['value' => 10, 'unit' => 'px'],
          ['value' => 15, 'unit' => 'px']
        ]);

        expect($result)->toEqual(['value' => 10, 'unit' => 'px']);
      });

      it('clamps value below minimum', function () {
        $result = $this->mathFunctions->clamp([
          ['value' => 3, 'unit' => 'px'],
          ['value' => 10, 'unit' => 'px'],
          ['value' => 15, 'unit' => 'px']
        ]);

        expect($result)->toEqual(['value' => 10, 'unit' => 'px']);
      });

      it('clamps value above maximum', function () {
        $result = $this->mathFunctions->clamp([
          ['value' => 20, 'unit' => 'px'],
          ['value' => 5, 'unit' => 'px'],
          ['value' => 15, 'unit' => 'px']
        ]);

        expect($result)->toEqual(['value' => 15, 'unit' => 'px']);
      });

      it('returns CSS function for incompatible units', function () {
        $result = $this->mathFunctions->clamp([
          ['value' => 5, 'unit' => 'px'],
          ['value' => 10, 'unit' => 'em'],
          ['value' => 15, 'unit' => 'px']
        ]);

        expect($result)->toBe('clamp(5px, 10em, 15px)');
      });

      it('returns CSS function for non-numeric arguments', function () {
        $result = $this->mathFunctions->clamp(['var(--min)', '10px', 'var(--max)']);

        expect($result)->toBe('clamp(var(--min), 10px, var(--max))');
      });

      it('throws exception for wrong argument count', function () {
        expect(fn() => $this->mathFunctions->clamp([5, 10]))
          ->toThrow(CompilationException::class, 'requires exactly three arguments')
          ->and(fn() => $this->mathFunctions->clamp([5, 10, 15, 20]))
          ->toThrow(CompilationException::class, 'requires exactly three arguments');
      });
    });

    describe('floor', function () {
      it('rounds down positive number', function () {
        $result = $this->mathFunctions->floor([5.9]);

        expect($result)->toEqual(['value' => 5.0, 'unit' => '']);
      });

      it('rounds down negative number', function () {
        $result = $this->mathFunctions->floor([-5.9]);

        expect($result)->toEqual(['value' => -6.0, 'unit' => '']);
      });

      it('rounds down number with unit', function () {
        $result = $this->mathFunctions->floor([['value' => 5.7, 'unit' => 'px']]);

        expect($result)->toEqual(['value' => 5.0, 'unit' => 'px']);
      });

      it('leaves integer unchanged', function () {
        $result = $this->mathFunctions->floor([5]);

        expect($result)->toEqual(['value' => 5.0, 'unit' => '']);
      });

      it('throws exception for wrong argument count', function () {
        expect(fn() => $this->mathFunctions->floor([]))
          ->toThrow(CompilationException::class, 'requires exactly one argument')
          ->and(fn() => $this->mathFunctions->floor([1, 2]))
          ->toThrow(CompilationException::class, 'requires exactly one argument');
      });

      it('throws exception for non-numeric argument', function () {
        expect(fn() => $this->mathFunctions->floor(['invalid']))
          ->toThrow(CompilationException::class, 'argument must be a number');
      });
    });

    describe('max', function () {
      it('returns maximum of simple numbers', function () {
        $result = $this->mathFunctions->max([5, 3, 8, 1]);

        expect($result)->toEqual(['value' => 8, 'unit' => '']);
      });

      it('returns maximum of numbers with same units', function () {
        $result = $this->mathFunctions->max([
          ['value' => 10, 'unit' => 'px'],
          ['value' => 5, 'unit' => 'px'],
          ['value' => 8, 'unit' => 'px']
        ]);

        expect($result)->toEqual(['value' => 10, 'unit' => 'px']);
      });

      it('returns maximum of mixed units as CSS function', function () {
        $result = $this->mathFunctions->max([
          ['value' => 10, 'unit' => 'px'],
          ['value' => 5, 'unit' => 'em']
        ]);

        expect($result)->toBe('max(10px, 5em)');
      });

      it('returns CSS function for non-numeric arguments', function () {
        $result = $this->mathFunctions->max(['var(--test)', 10]);

        expect($result)->toBe('max(var(--test), 10)');
      });

      it('throws exception for no arguments', function () {
        expect(fn() => $this->mathFunctions->max([]))
          ->toThrow(CompilationException::class, 'requires at least one argument');
      });
    });

    describe('min', function () {
      it('returns minimum of simple numbers', function () {
        $result = $this->mathFunctions->min([5, 3, 8, 1]);

        expect($result)->toEqual(['value' => 1, 'unit' => '']);
      });

      it('returns minimum of numbers with same units', function () {
        $result = $this->mathFunctions->min([
          ['value' => 10, 'unit' => 'px'],
          ['value' => 5, 'unit' => 'px'],
          ['value' => 8, 'unit' => 'px']
        ]);

        expect($result)->toEqual(['value' => 5, 'unit' => 'px']);
      });

      it('returns minimum of mixed units as CSS function', function () {
        $result = $this->mathFunctions->min([
          ['value' => 10, 'unit' => 'px'],
          ['value' => 5, 'unit' => 'em']
        ]);

        expect($result)->toBe('min(10px, 5em)');
      });

      it('returns minimum when some values have no units', function () {
        $result = $this->mathFunctions->min([5, ['value' => 3, 'unit' => 'px']]);

        expect($result)->toEqual(['value' => 3, 'unit' => '']);
      });

      it('returns CSS function for non-numeric arguments', function () {
        $result = $this->mathFunctions->min(['var(--test)', 10]);

        expect($result)->toBe('min(var(--test), 10)');
      });

      it('throws exception for no arguments', function () {
        expect(fn() => $this->mathFunctions->min([]))
          ->toThrow(CompilationException::class, 'requires at least one argument');
      });
    });

    describe('round', function () {
      it('rounds number', function () {
        $result = $this->mathFunctions->round([5.7]);

        expect($result)->toEqual(['value' => 6.0, 'unit' => '']);
      });

      it('throws exception for wrong argument count', function () {
        expect(fn() => $this->mathFunctions->round([]))
          ->toThrow(CompilationException::class, 'requires exactly one argument')
          ->and(fn() => $this->mathFunctions->round([1, 2, 3]))
          ->toThrow(CompilationException::class, 'requires exactly one argument');
      });

      it('throws exception for non-numeric first argument', function () {
        expect(fn() => $this->mathFunctions->round(['invalid']))
          ->toThrow(CompilationException::class, 'argument must be a number');
      });
    });

    describe('abs', function () {
        it('returns absolute value for positive number', function () {
            $result = $this->mathFunctions->abs([5.5]);

            expect($result)->toEqual(['value' => 5.5, 'unit' => '']);
        });

        it('returns absolute value for negative number', function () {
            $result = $this->mathFunctions->abs([-5.5]);

            expect($result)->toEqual(['value' => 5.5, 'unit' => '']);
        });

        it('returns absolute value for number with unit', function () {
            $result = $this->mathFunctions->abs([['value' => -10, 'unit' => 'px']]);

            expect($result)->toEqual(['value' => 10, 'unit' => 'px']);
        });

        it('returns absolute value for zero', function () {
            $result = $this->mathFunctions->abs([0]);

            expect($result)->toEqual(['value' => 0, 'unit' => '']);
        });

        it('throws exception for wrong argument count', function () {
            expect(fn() => $this->mathFunctions->abs([]))
                ->toThrow(CompilationException::class, 'requires exactly one argument')
                ->and(fn() => $this->mathFunctions->abs([1, 2]))
                ->toThrow(CompilationException::class, 'requires exactly one argument');
        });

        it('throws exception for non-numeric argument', function () {
            expect(fn() => $this->mathFunctions->abs(['invalid']))
                ->toThrow(CompilationException::class, 'argument must be a number');
        });
    });

    describe('hypot', function () {
      it('returns hypotenuse for two positive numbers', function () {
        $result = $this->mathFunctions->hypot([3, 4]);

        expect($result)->toEqual(['value' => 5.0, 'unit' => '']);
      });

      it('returns input for single argument', function () {
        $result = $this->mathFunctions->hypot([5]);

        expect($result)->toEqual(['value' => 5.0, 'unit' => '']);
      });

      it('handles negative numbers correctly', function () {
        $result = $this->mathFunctions->hypot([-3, 4]);

        expect($result)->toEqual(['value' => 5.0, 'unit' => '']);
      });

      it('preserves units for numbers with units', function () {
        $result = $this->mathFunctions->hypot([['value' => 3, 'unit' => 'px'], ['value' => 4, 'unit' => 'px']]);

        expect($result)->toEqual(['value' => 5.0, 'unit' => 'px']);
      });

      it('handles zero values', function () {
        $result = $this->mathFunctions->hypot([0, 0]);

        expect($result)->toEqual(['value' => 0.0, 'unit' => '']);
      });
    });

    describe('log', function () {
        it('calculates natural logarithm', function () {
            $result = $this->mathFunctions->log([10]);

            expect($result)->toEqual(['value' => 2.302585092994046, 'unit' => '']);
        });

        it('calculates logarithm with base', function () {
            $result = $this->mathFunctions->log([10, 10]);

            expect($result)->toEqual(['value' => 1.0, 'unit' => '']);
        });

        it('calculates logarithm of 1', function () {
            $result = $this->mathFunctions->log([1]);

            expect($result)->toEqual(['value' => 0.0, 'unit' => '']);
        });

        it('throws exception for wrong argument count', function () {
            expect(fn() => $this->mathFunctions->log([]))
                ->toThrow(CompilationException::class, 'requires one or two arguments')
                ->and(fn() => $this->mathFunctions->log([1, 2, 3]))
                ->toThrow(CompilationException::class, 'requires one or two arguments');
        });

        it('throws exception for non-numeric argument', function () {
            expect(fn() => $this->mathFunctions->log(['invalid']))
                ->toThrow(CompilationException::class, 'first argument must be a number');
        });

        it('throws exception for negative number', function () {
            expect(fn() => $this->mathFunctions->log([-1]))
                ->toThrow(CompilationException::class, 'first argument must be greater than zero');
        });

        it('throws exception for invalid base', function () {
            expect(fn() => $this->mathFunctions->log([10, -1]))
                ->toThrow(CompilationException::class, 'base must be greater than zero and not equal to one')
                ->and(fn() => $this->mathFunctions->log([10, 1]))
                ->toThrow(CompilationException::class, 'base must be greater than zero and not equal to one');
        });

        it('throws exception for arguments with units', function () {
            expect(fn() => $this->mathFunctions->log([['value' => 10, 'unit' => 'px']]))
                ->toThrow(CompilationException::class, 'arguments must be unitless');
        });
    });

    describe('pow', function () {
        it('calculates power of numbers', function () {
            $result = $this->mathFunctions->pow([2, 3]);

            expect($result)->toEqual(['value' => 8.0, 'unit' => '']);
        });

        it('calculates power with decimal numbers', function () {
            $result = $this->mathFunctions->pow([2.5, 2]);

            expect($result)->toEqual(['value' => 6.25, 'unit' => '']);
        });

        it('throws exception for wrong argument count', function () {
            expect(fn() => $this->mathFunctions->pow([]))
                ->toThrow(CompilationException::class, 'requires exactly two arguments')
                ->and(fn() => $this->mathFunctions->pow([1]))
                ->toThrow(CompilationException::class, 'requires exactly two arguments')
                ->and(fn() => $this->mathFunctions->pow([1, 2, 3]))
                ->toThrow(CompilationException::class, 'requires exactly two arguments');
        });

        it('throws exception for non-numeric arguments', function () {
            expect(fn() => $this->mathFunctions->pow(['invalid', 2]))
                ->toThrow(CompilationException::class, 'first argument must be a number')
                ->and(fn() => $this->mathFunctions->pow([2, 'invalid']))
                ->toThrow(CompilationException::class, 'second argument must be a number');
        });

        it('throws exception for arguments with units', function () {
            expect(fn() => $this->mathFunctions->pow([['value' => 2, 'unit' => 'px'], 3]))
                ->toThrow(CompilationException::class, 'arguments must be unitless')
                ->and(fn() => $this->mathFunctions->pow([2, ['value' => 3, 'unit' => 'px']]))
                ->toThrow(CompilationException::class, 'arguments must be unitless');
        });
    });

    describe('sqrt', function () {
        it('calculates square root of perfect squares', function () {
            $result = $this->mathFunctions->sqrt([16]);

            expect($result)->toEqual(['value' => 4.0, 'unit' => '']);
        });

        it('calculates square root of decimal numbers', function () {
            $result = $this->mathFunctions->sqrt([2]);

            expect($result)->toEqual(['value' => 1.4142135623730951, 'unit' => '']);
        });

        it('calculates square root of zero', function () {
            $result = $this->mathFunctions->sqrt([0]);

            expect($result)->toEqual(['value' => 0.0, 'unit' => '']);
        });

        it('throws exception for wrong argument count', function () {
            expect(fn() => $this->mathFunctions->sqrt([]))
                ->toThrow(CompilationException::class, 'requires exactly one argument')
                ->and(fn() => $this->mathFunctions->sqrt([1, 2]))
                ->toThrow(CompilationException::class, 'requires exactly one argument');
        });

        it('throws exception for non-numeric argument', function () {
            expect(fn() => $this->mathFunctions->sqrt(['invalid']))
                ->toThrow(CompilationException::class, 'argument must be a number');
        });

        it('throws exception for negative numbers', function () {
            expect(fn() => $this->mathFunctions->sqrt([-1]))
                ->toThrow(CompilationException::class, 'argument must be non-negative');
        });

        it('throws exception for arguments with units', function () {
            expect(fn() => $this->mathFunctions->sqrt([['value' => 16, 'unit' => 'px']]))
                ->toThrow(CompilationException::class, 'argument must be unitless');
        });
    });

    describe('trigonometric functions', function () {
        describe('cos', function () {
            it('calculates cosine of zero', function () {
                $result = $this->mathFunctions->cos([0]);

                expect($result)->toEqual(['value' => 1.0, 'unit' => '']);
            });

            it('calculates cosine with radians', function () {
                $result = $this->mathFunctions->cos([['value' => pi(), 'unit' => 'rad']]);

                expect($result['value'])->toBeCloseTo(-1.0, 10)
                    ->and($result['unit'])->toBe('');
            });

            it('calculates cosine with degrees', function () {
                $result = $this->mathFunctions->cos([['value' => 180, 'unit' => 'deg']]);

                expect($result['value'])->toBeCloseTo(-1.0, 10)
                    ->and($result['unit'])->toBe('');
            });

            it('throws exception for wrong argument count', function () {
                expect(fn() => $this->mathFunctions->cos([]))
                    ->toThrow(CompilationException::class, 'requires exactly one argument')
                    ->and(fn() => $this->mathFunctions->cos([1, 2]))
                    ->toThrow(CompilationException::class, 'requires exactly one argument');
            });

            it('throws exception for non-numeric argument', function () {
                expect(fn() => $this->mathFunctions->cos(['invalid']))
                    ->toThrow(CompilationException::class, 'argument must be a number');
            });

            it('throws exception for invalid units', function () {
                expect(fn() => $this->mathFunctions->cos([['value' => 1, 'unit' => 'px']]))
                    ->toThrow(CompilationException::class, 'argument must be unitless, or have rad or deg units');
            });
        });

        describe('sin', function () {
            it('calculates sine of zero', function () {
                $result = $this->mathFunctions->sin([0]);

                expect($result)->toEqual(['value' => 0.0, 'unit' => '']);
            });

            it('calculates sine with radians', function () {
                $result = $this->mathFunctions->sin([['value' => pi()/2, 'unit' => 'rad']]);

                expect($result['value'])->toBeCloseTo(1.0, 10)
                    ->and($result['unit'])->toBe('');
            });

            it('calculates sine with degrees', function () {
                $result = $this->mathFunctions->sin([['value' => 90, 'unit' => 'deg']]);

                expect($result['value'])->toBeCloseTo(1.0, 10)
                    ->and($result['unit'])->toBe('');
            });

            it('throws exception for wrong argument count', function () {
                expect(fn() => $this->mathFunctions->sin([]))
                    ->toThrow(CompilationException::class, 'requires exactly one argument')
                    ->and(fn() => $this->mathFunctions->sin([1, 2]))
                    ->toThrow(CompilationException::class, 'requires exactly one argument');
            });

            it('throws exception for non-numeric argument', function () {
                expect(fn() => $this->mathFunctions->sin(['invalid']))
                    ->toThrow(CompilationException::class, 'argument must be a number');
            });

            it('throws exception for invalid units', function () {
                expect(fn() => $this->mathFunctions->sin([['value' => 1, 'unit' => 'px']]))
                    ->toThrow(CompilationException::class, 'argument must be unitless, or have rad or deg units');
            });
        });

        describe('tan', function () {
            it('calculates tangent of zero', function () {
                $result = $this->mathFunctions->tan([0]);

                expect($result)->toEqual(['value' => 0.0, 'unit' => '']);
            });

            it('calculates tangent with radians', function () {
                $result = $this->mathFunctions->tan([['value' => pi()/4, 'unit' => 'rad']]);

                expect($result['value'])->toBeCloseTo(1.0, 10)
                    ->and($result['unit'])->toBe('');
            });

            it('calculates tangent with degrees', function () {
                $result = $this->mathFunctions->tan([['value' => 45, 'unit' => 'deg']]);

                expect($result['value'])->toBeCloseTo(1.0, 10)
                    ->and($result['unit'])->toBe('');
            });

            it('throws exception for wrong argument count', function () {
                expect(fn() => $this->mathFunctions->tan([]))
                    ->toThrow(CompilationException::class, 'requires exactly one argument')
                    ->and(fn() => $this->mathFunctions->tan([1, 2]))
                    ->toThrow(CompilationException::class, 'requires exactly one argument');
            });

            it('throws exception for non-numeric argument', function () {
                expect(fn() => $this->mathFunctions->tan(['invalid']))
                    ->toThrow(CompilationException::class, 'argument must be a number');
            });

            it('throws exception for invalid units', function () {
                expect(fn() => $this->mathFunctions->tan([['value' => 1, 'unit' => 'px']]))
                    ->toThrow(CompilationException::class, 'argument must be unitless, or have rad or deg units');
            });
        });
    });

    describe('inverse trigonometric functions', function () {
        describe('acos', function () {
            it('calculates arc cosine', function () {
                $result = $this->mathFunctions->acos([0]);

                expect($result['value'])->toBeCloseTo(90, 10)
                    ->and($result['unit'])->toBe('deg');
            });

            it('calculates arc cosine of one', function () {
                $result = $this->mathFunctions->acos([1]);

                expect($result)->toEqual(['value' => 0.0, 'unit' => 'deg']);
            });

            it('throws exception for wrong argument count', function () {
                expect(fn() => $this->mathFunctions->acos([]))
                    ->toThrow(CompilationException::class, 'requires exactly one argument')
                    ->and(fn() => $this->mathFunctions->acos([1, 2]))
                    ->toThrow(CompilationException::class, 'requires exactly one argument');
            });

            it('throws exception for non-numeric argument', function () {
                expect(fn() => $this->mathFunctions->acos(['invalid']))
                    ->toThrow(CompilationException::class, 'argument must be a number');
            });

            it('throws exception for values outside domain', function () {
                expect(fn() => $this->mathFunctions->acos([2]))
                    ->toThrow(CompilationException::class, 'argument must be between -1 and 1')
                    ->and(fn() => $this->mathFunctions->acos([-2]))
                    ->toThrow(CompilationException::class, 'argument must be between -1 and 1');
            });

            it('throws exception for arguments with units', function () {
                expect(fn() => $this->mathFunctions->acos([['value' => 0, 'unit' => 'px']]))
                    ->toThrow(CompilationException::class, 'argument must be unitless');
            });
        });

        describe('asin', function () {
            it('calculates arc sine', function () {
                $result = $this->mathFunctions->asin([0]);

                expect($result)->toEqual(['value' => 0.0, 'unit' => 'deg']);
            });

            it('calculates arc sine of one', function () {
                $result = $this->mathFunctions->asin([1]);

                expect($result['value'])->toBeCloseTo(90, 10)
                    ->and($result['unit'])->toBe('deg');
            });

            it('throws exception for wrong argument count', function () {
                expect(fn() => $this->mathFunctions->asin([]))
                    ->toThrow(CompilationException::class, 'requires exactly one argument')
                    ->and(fn() => $this->mathFunctions->asin([1, 2]))
                    ->toThrow(CompilationException::class, 'requires exactly one argument');
            });

            it('throws exception for non-numeric argument', function () {
                expect(fn() => $this->mathFunctions->asin(['invalid']))
                    ->toThrow(CompilationException::class, 'argument must be a number');
            });

            it('throws exception for values outside domain', function () {
                expect(fn() => $this->mathFunctions->asin([2]))
                    ->toThrow(CompilationException::class, 'argument must be between -1 and 1')
                    ->and(fn() => $this->mathFunctions->asin([-2]))
                    ->toThrow(CompilationException::class, 'argument must be between -1 and 1');
            });

            it('throws exception for arguments with units', function () {
                expect(fn() => $this->mathFunctions->asin([['value' => 0, 'unit' => 'px']]))
                    ->toThrow(CompilationException::class, 'argument must be unitless');
            });
        });

        describe('atan', function () {
            it('calculates arc tangent', function () {
                $result = $this->mathFunctions->atan([0]);

                expect($result)->toEqual(['value' => 0.0, 'unit' => 'deg']);
            });

            it('calculates arc tangent of one', function () {
                $result = $this->mathFunctions->atan([1]);

                expect($result['value'])->toBeCloseTo(45, 10)
                    ->and($result['unit'])->toBe('deg');
            });

            it('throws exception for wrong argument count', function () {
                expect(fn() => $this->mathFunctions->atan([]))
                    ->toThrow(CompilationException::class, 'requires exactly one argument')
                    ->and(fn() => $this->mathFunctions->atan([1, 2]))
                    ->toThrow(CompilationException::class, 'requires exactly one argument');
            });

            it('throws exception for non-numeric argument', function () {
                expect(fn() => $this->mathFunctions->atan(['invalid']))
                    ->toThrow(CompilationException::class, 'argument must be a number');
            });

            it('throws exception for arguments with units', function () {
                expect(fn() => $this->mathFunctions->atan([['value' => 0, 'unit' => 'px']]))
                    ->toThrow(CompilationException::class, 'argument must be unitless');
            });
        });

        describe('atan2', function () {
            it('calculates arc tangent of two variables', function () {
                $result = $this->mathFunctions->atan2([1, 1]);

                expect($result['value'])->toBeCloseTo(45, 10)
                    ->and($result['unit'])->toBe('deg');
            });

            it('calculates arc tangent with zero y', function () {
                $result = $this->mathFunctions->atan2([0, 1]);

                expect($result)->toEqual(['value' => 0.0, 'unit' => 'deg']);
            });

            it('throws exception for wrong argument count', function () {
                expect(fn() => $this->mathFunctions->atan2([]))
                    ->toThrow(CompilationException::class, 'requires exactly two arguments')
                    ->and(fn() => $this->mathFunctions->atan2([1]))
                    ->toThrow(CompilationException::class, 'requires exactly two arguments')
                    ->and(fn() => $this->mathFunctions->atan2([1, 2, 3]))
                    ->toThrow(CompilationException::class, 'requires exactly two arguments');
            });

            it('throws exception for non-numeric arguments', function () {
                expect(fn() => $this->mathFunctions->atan2(['invalid', 1]))
                    ->toThrow(CompilationException::class, 'first argument must be a number')
                    ->and(fn() => $this->mathFunctions->atan2([1, 'invalid']))
                    ->toThrow(CompilationException::class, 'second argument must be a number');
            });

            it('throws exception for arguments with units', function () {
                expect(fn() => $this->mathFunctions->atan2([['value' => 1, 'unit' => 'px'], 1]))
                    ->toThrow(CompilationException::class, 'arguments must be unitless')
                    ->and(fn() => $this->mathFunctions->atan2([1, ['value' => 1, 'unit' => 'px']]))
                    ->toThrow(CompilationException::class, 'arguments must be unitless');
            });
        });
    });

    describe('utility functions', function () {
        describe('compatible', function () {
            it('returns true for compatible units', function () {
                $result = $this->mathFunctions->compatible([
                    ['value' => 10, 'unit' => 'px'],
                    ['value' => 5, 'unit' => 'px']
                ]);

                expect($result)->toEqual(['value' => 'true', 'unit' => '']);
            });

            it('returns true for unitless values', function () {
                $result = $this->mathFunctions->compatible([[ 'value' => 10, 'unit' => '' ], [ 'value' => 5, 'unit' => '' ]]);

                expect($result)->toEqual(['value' => 'true', 'unit' => '']);
            });

            it('returns true for unitless and unit values', function () {
                $result = $this->mathFunctions->compatible([
                    ['value' => 10, 'unit' => ''],
                    ['value' => 5, 'unit' => 'px']
                ]);

                expect($result)->toEqual(['value' => 'true', 'unit' => '']);
            });

            it('returns false for incompatible units', function () {
                $result = $this->mathFunctions->compatible([
                    ['value' => 10, 'unit' => 'px'],
                    ['value' => 5, 'unit' => 'em']
                ]);

                expect($result)->toEqual(['value' => 'false', 'unit' => '']);
            });

            it('throws exception for wrong argument count', function () {
                expect(fn() => $this->mathFunctions->compatible([]))
                    ->toThrow(CompilationException::class, 'requires exactly two arguments')
                    ->and(fn() => $this->mathFunctions->compatible([1]))
                    ->toThrow(CompilationException::class, 'requires exactly two arguments')
                    ->and(fn() => $this->mathFunctions->compatible([1, 2, 3]))
                    ->toThrow(CompilationException::class, 'requires exactly two arguments');
            });

            it('throws exception for non-numeric arguments', function () {
                expect(fn() => $this->mathFunctions->compatible(['invalid', 2]))
                    ->toThrow(CompilationException::class, 'arguments must be numbers')
                    ->and(fn() => $this->mathFunctions->compatible([1, 'invalid']))
                    ->toThrow(CompilationException::class, 'arguments must be numbers');
            });
        });

        describe('isUnitless', function () {
            it('returns true for unitless value', function () {
                $result = $this->mathFunctions->isUnitless([10]);

                expect($result)->toEqual(['value' => 'true', 'unit' => '']);
            });

            it('returns false for value with unit', function () {
                $result = $this->mathFunctions->isUnitless([['value' => 10, 'unit' => 'px']]);

                expect($result)->toEqual(['value' => 'false', 'unit' => '']);
            });

            it('throws exception for wrong argument count', function () {
                expect(fn() => $this->mathFunctions->isUnitless([]))
                    ->toThrow(CompilationException::class, 'requires exactly one argument')
                    ->and(fn() => $this->mathFunctions->isUnitless([1, 2]))
                    ->toThrow(CompilationException::class, 'requires exactly one argument');
            });

            it('throws exception for non-numeric argument', function () {
                expect(fn() => $this->mathFunctions->isUnitless(['invalid']))
                    ->toThrow(CompilationException::class, 'argument must be a number');
            });
        });

        describe('unit', function () {
            it('returns unit for value with unit', function () {
                $result = $this->mathFunctions->unit([['value' => 10, 'unit' => 'px']]);

                expect($result)->toEqual(['value' => '"px"', 'unit' => '']);
            });

            it('returns empty string for unitless value', function () {
                $result = $this->mathFunctions->unit([10]);

                expect($result)->toEqual(['value' => '""', 'unit' => '']);
            });

            it('throws exception for wrong argument count', function () {
                expect(fn() => $this->mathFunctions->unit([]))
                    ->toThrow(CompilationException::class, 'requires exactly one argument')
                    ->and(fn() => $this->mathFunctions->unit([1, 2]))
                    ->toThrow(CompilationException::class, 'requires exactly one argument');
            });

            it('throws exception for non-numeric argument', function () {
                expect(fn() => $this->mathFunctions->unit(['invalid']))
                    ->toThrow(CompilationException::class, 'argument must be a number');
            });
        });

        describe('div', function () {
            it('divides two numbers', function () {
                $result = $this->mathFunctions->div([10, 2]);

                expect($result)->toEqual(['value' => 5.0, 'unit' => '']);
            });

            it('divides numbers with same units', function () {
                $result = $this->mathFunctions->div([
                    ['value' => 10, 'unit' => 'px'],
                    ['value' => 2, 'unit' => 'px']
                ]);

                expect($result)->toEqual(['value' => 5.0, 'unit' => '']);
            });

            it('divides numbers with different units', function () {
                $result = $this->mathFunctions->div([
                    ['value' => 10, 'unit' => 'px'],
                    ['value' => 2, 'unit' => 'em']
                ]);

                expect($result)->toEqual(['value' => 5.0, 'unit' => 'px/em']);
            });

            it('divides unitless number by number with unit', function () {
                $result = $this->mathFunctions->div([
                    10,
                    ['value' => 2, 'unit' => 'px']
                ]);

                expect($result)->toEqual(['value' => 5.0, 'unit' => '/px']);
            });

            it('divides number with unit by unitless number', function () {
                $result = $this->mathFunctions->div([
                    ['value' => 10, 'unit' => 'px'],
                    2
                ]);

                expect($result)->toEqual(['value' => 5.0, 'unit' => 'px']);
            });

            it('throws exception for wrong argument count', function () {
                expect(fn() => $this->mathFunctions->div([]))
                    ->toThrow(CompilationException::class, 'requires exactly two arguments')
                    ->and(fn() => $this->mathFunctions->div([1]))
                    ->toThrow(CompilationException::class, 'requires exactly two arguments')
                    ->and(fn() => $this->mathFunctions->div([1, 2, 3]))
                    ->toThrow(CompilationException::class, 'requires exactly two arguments');
            });

            it('throws exception for non-numeric arguments', function () {
                expect(fn() => $this->mathFunctions->div(['invalid', 2]))
                    ->toThrow(CompilationException::class, 'first argument must be a number')
                    ->and(fn() => $this->mathFunctions->div([1, 'invalid']))
                    ->toThrow(CompilationException::class, 'second argument must be a number');
            });

            it('throws exception for division by zero', function () {
                expect(fn() => $this->mathFunctions->div([10, 0]))
                    ->toThrow(CompilationException::class, 'second argument must not be zero');
            });
        });

        describe('percentage', function () {
            it('converts decimal to percentage', function () {
                $result = $this->mathFunctions->percentage([0.5]);

                expect($result)->toEqual(['value' => 50.0, 'unit' => '%']);
            });

            it('converts zero to percentage', function () {
                $result = $this->mathFunctions->percentage([0]);

                expect($result)->toEqual(['value' => 0.0, 'unit' => '%']);
            });

            it('converts whole number to percentage', function () {
                $result = $this->mathFunctions->percentage([2]);

                expect($result)->toEqual(['value' => 200.0, 'unit' => '%']);
            });

            it('throws exception for wrong argument count', function () {
                expect(fn() => $this->mathFunctions->percentage([]))
                    ->toThrow(CompilationException::class, 'requires exactly one argument')
                    ->and(fn() => $this->mathFunctions->percentage([1, 2]))
                    ->toThrow(CompilationException::class, 'requires exactly one argument');
            });

            it('throws exception for non-numeric argument', function () {
                expect(fn() => $this->mathFunctions->percentage(['invalid']))
                    ->toThrow(CompilationException::class, 'argument must be a number');
            });

            it('throws exception for arguments with units', function () {
                expect(fn() => $this->mathFunctions->percentage([['value' => 0.5, 'unit' => 'px']]))
                    ->toThrow(CompilationException::class, 'argument must be unitless');
            });
        });

        describe('random', function () {
            it('generates random number with no arguments', function () {
                $result = $this->mathFunctions->random([]);

                expect($result)->toBeArray()
                    ->and($result['unit'])->toBe('')
                    ->and($result['value'])->toBeGreaterThanOrEqual(0)
                    ->and($result['value'])->toBeLessThan(1);
            });

            it('generates random number with limit', function () {
                $result = $this->mathFunctions->random([10]);

                expect($result)->toBeArray()
                    ->and($result['unit'])->toBe('')
                    ->and($result['value'])->toBeInt()
                    ->and($result['value'])->toBeGreaterThanOrEqual(0)
                    ->and($result['value'])->toBeLessThan(10);
            });

            it('throws exception for too many arguments', function () {
                expect(fn() => $this->mathFunctions->random([1, 2]))
                    ->toThrow(CompilationException::class, 'requires zero or one argument');
            });

            it('throws exception for non-numeric argument', function () {
                expect(fn() => $this->mathFunctions->random(['invalid']))
                    ->toThrow(CompilationException::class, 'argument must be a number');
            });

            it('throws exception for arguments with units', function () {
                expect(fn() => $this->mathFunctions->random([['value' => 10, 'unit' => 'px']]))
                    ->toThrow(CompilationException::class, 'argument must be unitless');
            });

            it('throws exception for non-positive limit', function () {
                expect(fn() => $this->mathFunctions->random([0]))
                    ->toThrow(CompilationException::class, 'argument must be greater than zero')
                    ->and(fn() => $this->mathFunctions->random([-1]))
                    ->toThrow(CompilationException::class, 'argument must be greater than zero');
            });
        });
    });

    describe('calc', function () {
        it('returns simple number as string', function () {
            $result = $this->mathFunctions->calc([42]);

            expect($result)->toBe('42');
        });

        it('returns number with unit', function () {
            $result = $this->mathFunctions->calc([['value' => 10, 'unit' => 'px']]);

            expect($result)->toBe('10px');
        });

        it('returns calc() with single argument', function () {
            $result = $this->mathFunctions->calc(['5em + 2em']);

            expect($result)->toBe('calc(5em + 2em)');
        });

        it('returns calc() with multiple arguments', function () {
            $result = $this->mathFunctions->calc(['100%', '10px', '2em']);

            expect($result)->toBe('calc(100%, 10px, 2em)');
        });
    });

    describe('normalize', function () {
        it('normalizes numeric string', function () {
            $result = $this->accessor->callProtectedMethod('normalize', ['42']);

            expect($result)->toEqual(['value' => 42.0, 'unit' => '']);
        });

        it('normalizes numeric array', function () {
            $result = $this->accessor->callProtectedMethod('normalize', [
                ['value' => 42, 'unit' => 'px']
            ]);

            expect($result)->toEqual(['value' => 42.0, 'unit' => 'px']);
        });

        it('normalizes array without unit', function () {
            $result = $this->accessor->callProtectedMethod('normalize', [
                ['value' => 42]
            ]);

            expect($result)->toEqual(['value' => 42.0, 'unit' => '']);
        });

        it('returns null for invalid input', function () {
            expect($this->accessor->callProtectedMethod('normalize', ['invalid']))
                ->toBeNull()
                ->and($this->accessor->callProtectedMethod('normalize', [[]]))
                ->toBeNull();
        });
    });

    describe('areUnitsCompatible', function () {
        it('considers same units as compatible', function () {
            $result = $this->accessor->callProtectedMethod('areUnitsCompatible', ['px', 'px']);

            expect($result)->toBeTrue();
        });

        it('considers empty units as compatible with any unit', function () {
            expect($this->accessor->callProtectedMethod('areUnitsCompatible', ['', 'px']))
                ->toBeTrue()
                ->and($this->accessor->callProtectedMethod('areUnitsCompatible', ['px', '']))
                ->toBeTrue();
        });

        it('considers different units as incompatible', function () {
            $result = $this->accessor->callProtectedMethod('areUnitsCompatible', ['px', 'em']);

            expect($result)->toBeFalse();
        });
    });
});
