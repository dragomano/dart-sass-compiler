<?php

declare(strict_types=1);

use DartSass\Utils\UnitValidator;

dataset('single arguments', [
    'array with px unit'  => [['value' => 10, 'unit' => 'px']],
    'array with em unit'  => [['value' => 10, 'unit' => 'em']],
    'array with no unit'  => [['value' => 10, 'unit' => '']],
    'string with unit'    => [['10px']],
    'string without unit' => [['10']],
]);

dataset('valid unit combinations', [
    'same unit in arrays' => [
        [['value' => 10, 'unit' => 'px'], ['value' => 20, 'unit' => 'px']],
        true,
    ],
    'no units' => [
        [['value' => 10, 'unit' => ''], ['value' => 20, 'unit' => '']],
        true,
    ],
    'mixed unitless and same unit' => [
        [['value' => 10, 'unit' => ''], ['value' => 20, 'unit' => 'px']],
        true,
    ],
    'same unit in strings' => [
        [['10px', '20px']],
        true,
    ],
    'mixed array and string with same unit' => [
        [[['value' => 10, 'unit' => 'px'], '20px']],
        true,
    ],
    'negative numbers with same unit' => [
        [[['value' => -10, 'unit' => 'px'], ['value' => -20, 'unit' => 'px']]],
        true,
    ],
    'decimal numbers with same unit' => [
        [[['value' => 10.5, 'unit' => 'px'], ['value' => 20.75, 'unit' => 'px']]],
        true,
    ],
    'px units' => [
        [['value' => 10, 'unit' => 'px'], ['value' => 20, 'unit' => 'px']],
        true,
    ],
    'em units' => [
        [['value' => 10, 'unit' => 'em'], ['value' => 20, 'unit' => 'em']],
        true,
    ],
    'percentage units' => [
        [['value' => 10, 'unit' => '%'], ['value' => 20, 'unit' => '%']],
        true,
    ],
]);

dataset('invalid unit combinations', [
    'different units' => [
        [['value' => 10, 'unit' => 'px'], ['value' => 20, 'unit' => 'em']],
        false,
    ],
    'multiple different units' => [
        [
            ['value' => 10, 'unit' => 'px'],
            ['value' => 20, 'unit' => 'em'],
            ['value' => 30, 'unit' => '%'],
        ],
        false,
    ],
    'different units in strings' => [
        [['10px', '20em']],
        false,
    ],
    'mixed array and string with different units' => [
        [[['value' => 10, 'unit' => 'px'], '20em']],
        false,
    ],
    'negative numbers with different units' => [
        [[['value' => -10, 'unit' => 'px'], ['value' => -20, 'unit' => 'em']]],
        false,
    ],
    'decimal numbers with different units' => [
        [[['value' => 10.5, 'unit' => 'px'], ['value' => 20.75, 'unit' => 'em']]],
        false,
    ],
    'different unit types' => [
        [[['value' => 10, 'unit' => 'px'], ['value' => 20, 'unit' => 's']]],
        false,
    ],
]);

dataset('arguments without units', [
    'array without unit key' => [
        [['value' => 10], ['value' => 20, 'unit' => 'px'], ['value' => 30]],
        true,
    ],
    'string arguments without units' => [
        [['10', '20px', '30']],
        true,
    ],
]);

dataset('extract unit test cases', [
    'array with unit key'          => [['unit' => 'px'], 'px'],
    'array with value and unit'    => [['value' => 10, 'unit' => 'em'], 'em'],
    'array without unit key'       => [['value' => 10], ''],
    'empty array'                  => [[], ''],
    'string with unit'             => ['10px', 'px'],
    'negative string with unit'    => ['-10px', 'px'],
    'decimal string with unit'     => ['10.5em', 'em'],
    'negative decimal with unit'   => ['-10.5em', 'em'],
    'number without unit'          => ['10', ''],
    'negative number without unit' => ['-10', ''],
    'decimal without unit'         => ['10.5', ''],
    'complex units'                => ['10vh', 'vh'],
    'multi-character units'        => ['10rem', 'rem'],
    'non-numeric string'           => ['hello', ''],
    'null value'                   => [null, ''],
    'boolean true'                 => [true, ''],
    'boolean false'                => [false, ''],
    'object without unit'          => [(object) ['value' => 10], ''],
    'object with unit'             => [(object) ['unit' => 'px'], ''],
]);

describe('UnitValidator', function () {
    beforeEach(function () {
        $this->validator = new UnitValidator();
    });

    it('returns true for empty array', function () {
        expect($this->validator->validate([]))->toBeTrue();
    });

    it('returns true for single argument regardless of unit', function (array $args) {
        expect($this->validator->validate($args))->toBeTrue();
    })->with('single arguments');

    it('validates unit combinations correctly', function (array $args, bool $expected) {
        expect($this->validator->validate($args))->toBe($expected);
    })->with('valid unit combinations', 'invalid unit combinations');

    it('ignores arguments without units', function (array $args, bool $expected) {
        expect($this->validator->validate($args))->toBe($expected);
    })->with('arguments without units');

    describe('edge cases', function () {
        it('handles very large arrays efficiently', function () {
            $args = array_fill(0, 1000, ['value' => 10, 'unit' => 'px']);

            $result = $this->validator->validate($args);

            expect($result)->toBeTrue();
        });

        it('handles arrays with mixed data types', function () {
            $args = [
                ['value' => 10, 'unit' => 'px'],
                '20px',
                null,
                true,
                false,
                ['value' => 30], // no unit
                '40px',
            ];

            expect($this->validator->validate($args))->toBeTrue();
        });

        it('handles special unit combinations', function () {
            $args = [
                ['value' => 10, 'unit' => 'px/em'], // complex unit
                ['value' => 20, 'unit' => 'px/em'],
            ];

            expect($this->validator->validate($args))->toBeTrue();
        });

        it('rejects different complex units', function () {
            $args = [
                ['value' => 10, 'unit' => 'px/em'],
                ['value' => 20, 'unit' => 'px/%'],
            ];

            expect($this->validator->validate($args))->toBeFalse();
        });
    });

    describe('integration scenarios', function () {
        it('validates math operations with compatible units', function () {
            // Simulating typical usage in MathModuleHandler
            $args = [
                ['value' => 10, 'unit' => 'px'],
                ['value' => 20, 'unit' => 'px'],
            ];

            expect($this->validator->validate($args))->toBeTrue();
        });

        it('rejects math operations with incompatible units', function () {
            // Simulating typical usage in MathModuleHandler
            $args = [
                ['value' => 10, 'unit' => 'px'],
                ['value' => 20, 'unit' => 'em'],
            ];

            expect($this->validator->validate($args))->toBeFalse();
        });

        it('allows unitless operations', function () {
            // Common case for functions like sqrt, log, etc.
            $args = [
                ['value' => 10, 'unit' => ''],
                ['value' => 20, 'unit' => ''],
            ];

            expect($this->validator->validate($args))->toBeTrue();
        });

        it('handles calc() function arguments', function () {
            // calc() can mix units
            $args = [
                ['value' => 100, 'unit' => '%'],
                ['value' => 10, 'unit' => 'px'],
            ];

            expect($this->validator->validate($args))->toBeFalse();
        });
    });
});
