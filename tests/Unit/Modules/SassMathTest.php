<?php

declare(strict_types=1);

use DartSass\Modules\SassMath;

beforeEach(function () {
    $this->sassMath = new SassMath(42.5, 'px');
});

describe('SassMath', function () {
    describe('constructor', function () {
        it('creates instance with value and unit', function () {
            $sassMath = new SassMath(10.0, 'em');

            expect($sassMath->value)->toEqual(10.0)
                ->and($sassMath->unit)->toEqual('em');
        });

        it('creates instance with zero value', function () {
            $sassMath = new SassMath(0.0, '');

            expect($sassMath->value)->toEqual(0.0)
                ->and($sassMath->unit)->toEqual('');
        });

        it('creates instance with negative value', function () {
            $sassMath = new SassMath(-15.7, 'rem');

            expect($sassMath->value)->toEqual(-15.7)
                ->and($sassMath->unit)->toEqual('rem');
        });
    });

    describe('ArrayAccess offsetExists', function () {
        it('returns true for valid key "value"', function () {
            expect($this->sassMath)->offsetExists('value')->toBeTrue();
        });

        it('returns true for valid key "unit"', function () {
            expect($this->sassMath)->offsetExists('unit')->toBeTrue();
        });

        it('returns false for invalid key', function () {
            expect($this->sassMath)->offsetExists('invalid')->toBeFalse()
                ->and($this->sassMath)->offsetExists('other')->toBeFalse()
                ->and($this->sassMath)->offsetExists('')->toBeFalse();
        });

        it('returns false for numeric key', function () {
            expect($this->sassMath)->offsetExists(0)->toBeFalse()
                ->and($this->sassMath)->offsetExists(1)->toBeFalse();
        });

        it('returns false for null key', function () {
            expect($this->sassMath)->offsetExists(null)->toBeFalse();
        });
    });

    describe('ArrayAccess offsetGet', function () {
        it('returns value for key "value"', function () {
            expect($this->sassMath)->offsetGet('value')->toEqual(42.5);
        });

        it('returns unit for key "unit"', function () {
            expect($this->sassMath)->offsetGet('unit')->toEqual('px');
        });

        it('returns null for invalid key', function () {
            expect($this->sassMath)->offsetGet('invalid')->toBeNull()
                ->and($this->sassMath)->offsetGet('other')->toBeNull()
                ->and($this->sassMath)->offsetGet('')->toBeNull();
        });

        it('returns null for numeric key', function () {
            expect($this->sassMath)->offsetGet(0)->toBeNull()
                ->and($this->sassMath)->offsetGet(1)->toBeNull();
        });

        it('returns null for null key', function () {
            expect($this->sassMath)->offsetGet(null)->toBeNull();
        });
    });

    describe('ArrayAccess offsetSet (readonly behavior)', function () {
        it('does not change value when setting "value"', function () {
            $originalValue = $this->sassMath->value;
            $this->sassMath->offsetSet('value', 99.9);

            expect($this->sassMath->value)->toEqual($originalValue);
        });

        it('does not change unit when setting "unit"', function () {
            $originalUnit = $this->sassMath->unit;
            $this->sassMath->offsetSet('unit', 'em');

            expect($this->sassMath->unit)->toEqual($originalUnit);
        });

        it('does not throw exception when setting invalid key', function () {
            expect(fn() => $this->sassMath->offsetSet('invalid', 'test'))->not->toThrow(Exception::class);
        });

        it('does not throw exception when setting null key', function () {
            expect(fn() => $this->sassMath->offsetSet(null, 'test'))->not->toThrow(Exception::class);
        });
    });

    describe('ArrayAccess offsetUnset (readonly behavior)', function () {
        it('does not change value when unsetting "value"', function () {
            $originalValue = $this->sassMath->value;
            $this->sassMath->offsetUnset('value');

            expect($this->sassMath->value)->toEqual($originalValue);
        });

        it('does not change unit when unsetting "unit"', function () {
            $originalUnit = $this->sassMath->unit;
            $this->sassMath->offsetUnset('unit');

            expect($this->sassMath->unit)->toEqual($originalUnit);
        });

        it('does not throw exception when unsetting invalid key', function () {
            expect(fn() => $this->sassMath->offsetUnset('invalid'))->not->toThrow(Exception::class);
        });

        it('does not throw exception when unsetting null key', function () {
            expect(fn() => $this->sassMath->offsetUnset(null))->not->toThrow(Exception::class);
        });
    });

    describe('readonly behavior', function () {
        it('maintains original value through ArrayAccess operations', function () {
            $originalValue = $this->sassMath->value;
            $originalUnit  = $this->sassMath->unit;

            $this->sassMath->offsetSet('value', 999);
            $this->sassMath->offsetSet('unit', 'invalid');
            $this->sassMath->offsetUnset('value');
            $this->sassMath->offsetUnset('unit');

            expect($this->sassMath->value)->toEqual($originalValue)
                ->and($this->sassMath->unit)->toEqual($originalUnit);
        });
    });
});
