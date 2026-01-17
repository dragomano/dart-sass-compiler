<?php

declare(strict_types=1);

use DartSass\Utils\CalcValue;

describe('CalcValue', function () {
    describe('constructor', function () {
        it('creates instance with left, operator, right', function () {
            $calc = new CalcValue(10, '+', 20);

            expect($calc->left)->toBe(10)
                ->and($calc->operator)->toBe('+')
                ->and($calc->right)->toBe(20);
        });
    });

    describe('evaluate', function () {
        it('computes compatible numbers', function () {
            $calc = new CalcValue(['value' => 10, 'unit' => 'px'], '+', ['value' => 20, 'unit' => 'px']);
            $result = $calc->evaluate();
            expect($result)->toBe(['value' => 30, 'unit' => 'px']);
        });

        it('returns calc string for incompatible numbers', function () {
            $calc = new CalcValue(['value' => 10, 'unit' => 'px'], '+', ['value' => 20, 'unit' => 'em']);
            $result = $calc->evaluate();
            expect($result)->toBe('calc(10px + 20em)');
        });

        it('returns calc string when left value cannot be normalized', function () {
            $calc = new CalcValue('invalid', '+', ['value' => 20, 'unit' => 'px']);
            $result = $calc->evaluate();
            expect($result)->toBe('calc(invalid + 20px)');
        });

        it('returns calc string when right value cannot be normalized', function () {
            $calc = new CalcValue(['value' => 10, 'unit' => 'px'], '+', null);
            $result = $calc->evaluate();
            expect($result)->toBe('calc(10px + )');
        });

        it('returns calc string when both values cannot be normalized', function () {
            $calc = new CalcValue('left', '+', 'right');
            $result = $calc->evaluate();
            expect($result)->toBe('calc(left + right)');
        });

        it('returns calc string when left value is array without value key', function () {
            $calc = new CalcValue(['unit' => 'px'], '+', ['value' => 20, 'unit' => 'px']);
            $result = $calc->evaluate();
            expect($result)->toBe('calc(px + 20px)');
        });

        it('computes numeric values', function () {
            $calc = new CalcValue(10, '+', 20);
            $result = $calc->evaluate();
            expect($result)->toBe(['value' => 30.0, 'unit' => '']);
        });

        it('computes subtraction', function () {
            $calc = new CalcValue(['value' => 20, 'unit' => 'px'], '-', ['value' => 5, 'unit' => 'px']);
            $result = $calc->evaluate();
            expect($result)->toBe(['value' => 15, 'unit' => 'px']);
        });

        it('computes multiplication', function () {
            $calc = new CalcValue(['value' => 10, 'unit' => 'px'], '*', ['value' => 2, 'unit' => 'px']);
            $result = $calc->evaluate();
            expect($result)->toBe(['value' => 20, 'unit' => 'px']);
        });

        it('computes division', function () {
            $calc = new CalcValue(['value' => 20, 'unit' => 'px'], '/', ['value' => 4, 'unit' => 'px']);
            $result = $calc->evaluate();
            expect($result)->toBe(['value' => 5, 'unit' => 'px']);
        });

        it('returns calc string for division by zero', function () {
            $calc = new CalcValue(['value' => 20, 'unit' => 'px'], '/', ['value' => 0, 'unit' => 'px']);
            $result = $calc->evaluate();
            expect($result)->toBe('calc(20px / 0px)');
        });

        it('returns calc string for unknown operator', function () {
            $calc = new CalcValue(['value' => 10, 'unit' => 'px'], '%', ['value' => 3, 'unit' => 'px']);
            $result = $calc->evaluate();
            expect($result)->toBe('calc(10px % 3px)');
        });
    });

    describe('__toString', function () {
        it('formats calc expression', function () {
            $calc = new CalcValue(10, '+', 20);
            expect((string) $calc)->toBe('calc(10 + 20)');
        });

        it('handles complex values', function () {
            $calc = new CalcValue(['value' => 10, 'unit' => 'px'], '*', ['value' => 2, 'unit' => '']);
            expect((string) $calc)->toBe('calc(10px * 2)');
        });
    });
});
