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
                ->toThrow(CompilationException::class, 'expects exactly one argument')
                ->and(fn() => $this->mathFunctions->abs([1, 2]))
                ->toThrow(CompilationException::class, 'expects exactly one argument');
        });

        it('throws exception for non-numeric argument', function () {
            expect(fn() => $this->mathFunctions->abs(['invalid']))
                ->toThrow(CompilationException::class, 'argument must be a number');
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
                ->toThrow(CompilationException::class, 'expects exactly three arguments')
                ->and(fn() => $this->mathFunctions->clamp([5, 10, 15, 20]))
                ->toThrow(CompilationException::class, 'expects exactly three arguments');
        });
    });

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
                ->toThrow(CompilationException::class, 'expects exactly one argument')
                ->and(fn() => $this->mathFunctions->ceil([1, 2]))
                ->toThrow(CompilationException::class, 'expects exactly one argument');
        });

        it('throws exception for non-numeric argument', function () {
            expect(fn() => $this->mathFunctions->ceil(['invalid']))
                ->toThrow(CompilationException::class, 'argument must be a number');
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
                ->toThrow(CompilationException::class, 'expects exactly one argument')
                ->and(fn() => $this->mathFunctions->floor([1, 2]))
                ->toThrow(CompilationException::class, 'expects exactly one argument');
        });

        it('throws exception for non-numeric argument', function () {
            expect(fn() => $this->mathFunctions->floor(['invalid']))
                ->toThrow(CompilationException::class, 'argument must be a number');
        });
    });

    describe('round', function () {
        it('rounds number without precision', function () {
            $result = $this->mathFunctions->round([5.7]);

            expect($result)->toEqual(['value' => 6.0, 'unit' => '']);
        });

        it('rounds number with precision', function () {
            $result = $this->mathFunctions->round([5.678, 2]);

            expect($result)->toEqual(['value' => 5.68, 'unit' => '']);
        });

        it('rounds number with unit and precision', function () {
            $result = $this->mathFunctions->round([
                ['value' => 5.678, 'unit' => 'px'],
                2
            ]);

            expect($result)->toEqual(['value' => 5.68, 'unit' => 'px']);
        });

        it('rounds negative number with precision', function () {
            $result = $this->mathFunctions->round([-5.678, 2]);

            expect($result)->toEqual(['value' => -5.68, 'unit' => '']);
        });

        it('handles zero precision', function () {
            $result = $this->mathFunctions->round([5.7, 0]);

            expect($result)->toEqual(['value' => 6.0, 'unit' => '']);
        });

        it('handles negative precision', function () {
            $result = $this->mathFunctions->round([1234.5, -2]);

            expect($result)->toEqual(['value' => 1200.0, 'unit' => '']);
        });

        it('throws exception for wrong argument count', function () {
            expect(fn() => $this->mathFunctions->round([]))
                ->toThrow(CompilationException::class, 'expects one or two arguments')
                ->and(fn() => $this->mathFunctions->round([1, 2, 3]))
                ->toThrow(CompilationException::class, 'expects one or two arguments');
        });

        it('throws exception for non-numeric first argument', function () {
            expect(fn() => $this->mathFunctions->round(['invalid']))
                ->toThrow(CompilationException::class, 'argument must be a number');
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
