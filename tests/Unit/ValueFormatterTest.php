<?php

declare(strict_types=1);

use DartSass\Utils\LazyValue;
use DartSass\Utils\ValueFormatter;

beforeEach(function () {
    $this->valueFormatter = new ValueFormatter();
});

describe('ValueFormatter', function () {
    describe('format', function () {
        it('formats LazyValue by calling getValue', function () {
            $lazyValue = new LazyValue(fn() => 42);
            $result = $this->valueFormatter->format($lazyValue);

            expect($result)->toBe('42');
        });

        it('formats array with value and unit', function () {
            $result = $this->valueFormatter->format(['value' => 10.5, 'unit' => 'px']);

            expect($result)->toBe('10.5px');
        });

        it('formats array with string boolean true', function () {
            $result = $this->valueFormatter->format(['value' => 'true']);

            expect($result)->toBe('true');
        });

        it('formats array with string boolean false', function () {
            $result = $this->valueFormatter->format(['value' => 'false']);

            expect($result)->toBe('false');
        });

        it('formats array with PHP boolean true', function () {
            $result = $this->valueFormatter->format(['value' => true]);

            expect($result)->toBe('true');
        });

        it('formats array with PHP boolean false', function () {
            $result = $this->valueFormatter->format(['value' => false]);

            expect($result)->toBe('false');
        });

        it('formats array with quoted string', function () {
            $result = $this->valueFormatter->format(['value' => '"hello"']);

            expect($result)->toBe('"hello"');
        });

        it('formats array with non-quoted string as zero', function () {
            $result = $this->valueFormatter->format(['value' => 'hello']);

            expect($result)->toBe('0');
        });

        it('formats numeric value', function () {
            $result = $this->valueFormatter->format(123);

            expect($result)->toBe('123');
        });

        it('formats float value', function () {
            $result = $this->valueFormatter->format(12.34);

            expect($result)->toBe('12.34');
        });

        it('formats PHP boolean true directly', function () {
            $result = $this->valueFormatter->format(true);

            expect($result)->toBe('true');
        });

        it('formats PHP boolean false directly', function () {
            $result = $this->valueFormatter->format(false);

            expect($result)->toBe('false');
        });

        it('formats string value', function () {
            $result = $this->valueFormatter->format('hello world');

            expect($result)->toBe('hello world');
        });

        it('formats array of values', function () {
            $result = $this->valueFormatter->format([1, 2, 3]);

            expect($result)->toBe('1 2 3');
        });

        it('formats array of values with comma separation', function () {
            $result = $this->valueFormatter->format(['1, 2', '3, 4']);

            expect($result)->toBe('1, 2 3, 4');
        });

        it('formats nested array with values', function () {
            $result = $this->valueFormatter->format([
                ['value' => 10, 'unit' => 'px'],
                ['value' => 20, 'unit' => 'em']
            ]);

            expect($result)->toBe('10px 20em');
        });
    });

    describe('formatNumber', function () {
        it('formats zero as 0', function () {
            $result = $this->valueFormatter->format(0);

            expect($result)->toBe('0');
        });

        it('formats negative zero as 0', function () {
            $result = $this->valueFormatter->format(-0);

            expect($result)->toBe('0');
        });

        it('formats positive number less than 1', function () {
            $result = $this->valueFormatter->format(0.5);

            expect($result)->toBe('.5');
        });

        it('formats positive number less than 1 with multiple decimals', function () {
            $result = $this->valueFormatter->format(0.123);

            expect($result)->toBe('.123');
        });

        it('formats negative number less than 1', function () {
            $result = $this->valueFormatter->format(-0.5);

            expect($result)->toBe('-0.5');
        });

        it('formats whole number', function () {
            $result = $this->valueFormatter->format(42);

            expect($result)->toBe('42');
        });

        it('formats decimal number', function () {
            $result = $this->valueFormatter->format(3.14159);

            expect($result)->toBe('3.14159');
        });

        it('formats large number', function () {
            $result = $this->valueFormatter->format(1234567.89);

            expect($result)->toBe('1234567.89');
        });

        it('formats very small positive number', function () {
            $result = $this->valueFormatter->format(0.0000001);

            expect($result)->toBe('1.0E-7');
        });
    });
});
