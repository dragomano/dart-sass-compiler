<?php

declare(strict_types=1);

use DartSass\Utils\StringFormatter;
use DartSass\Values\SassList;

describe('StringFormatter', function () {
    describe('concat()', function () {
        it('concatenates two strings without space', function () {
            expect(StringFormatter::concat('hello', 'world'))->toBe('helloworld');
        });

        it('concatenates string and number', function () {
            expect(StringFormatter::concat('width: ', 100))->toBe('width: 100');
        });

        it('concatenates two numbers', function () {
            expect(StringFormatter::concat(42, 24))->toBe('4224');
        });

        it('handles quoted strings', function () {
            expect(StringFormatter::concat('"hello"', '"world"'))->toBe('"helloworld"');
        });

        it('handles null values', function () {
            expect(StringFormatter::concat('test', null))->toBe('testnull');
        });

        it('handles empty strings', function () {
            expect(StringFormatter::concat('', 'world'))->toBe('world');
        });
    });

    describe('concatWithSpace()', function () {
        it('concatenates with space', function () {
            expect(StringFormatter::concatWithSpace('hello', 'world'))->toBe('hello world');
        });

        it('concatenates different types with space', function () {
            expect(StringFormatter::concatWithSpace('font-size:', 14))->toBe('font-size: 14');
        });

        it('handles empty values', function () {
            expect(StringFormatter::concatWithSpace('', ''))->toBe(' ');
        });
    });

    describe('concatMultiple()', function () {
        it('concatenates array of strings', function () {
            expect(StringFormatter::concatMultiple(['a', 'b', 'c']))->toBe('abc');
        });

        it('concatenates with separator', function () {
            expect(StringFormatter::concatMultiple(['a', 'b', 'c'], '-'))->toBe('a-b-c');
        });

        it('handles empty array', function () {
            expect(StringFormatter::concatMultiple([]))->toBe('');
        });

        it('handles different types', function () {
            expect(StringFormatter::concatMultiple([1, 'px', true]))->toBe('1pxtrue');
        });

        it('handles SassList elements', function () {
            $list1 = new SassList(['a', 'b']);
            $list2 = new SassList(['c']);
            expect(StringFormatter::concatMultiple([$list1, $list2], ' '))->toBe('a b c');
        });
    });

    describe('toString()', function () {
        it('converts string', function () {
            expect(StringFormatter::toString('test'))->toBe('test');
        });

        it('converts number', function () {
            expect(StringFormatter::toString(42))->toBe('42');
        });

        it('converts float', function () {
            expect(StringFormatter::toString(3.14))->toBe('3.14');
        });

        it('converts boolean true', function () {
            expect(StringFormatter::toString(true))->toBe('true');
        });

        it('converts boolean false', function () {
            expect(StringFormatter::toString(false))->toBe('false');
        });

        it('converts null', function () {
            expect(StringFormatter::toString(null))->toBe('null');
        });

        it('converts array with value and unit', function () {
            expect(StringFormatter::toString(['value' => 100, 'unit' => 'px']))->toBe('100px');
        });

        it('converts regular array', function () {
            expect(StringFormatter::toString([1, 2, 3]))->toBe('[1,2,3]');
        });

        it('converts SassList', function () {
            $list = new SassList(['a', 'b', 'c']);
            expect(StringFormatter::toString($list))->toBe('a b c');
        });

        it('converts object with __toString', function () {
            $obj = new class () {
                public function __toString(): string
                {
                    return 'object_string';
                }
            };
            expect(StringFormatter::toString($obj))->toBe('object_string');
        });

        it('converts object without __toString', function () {
            $obj = new stdClass();
            expect(StringFormatter::toString($obj))->toBe('stdClass');
        });

        it('converts resource', function () {
            $resource = tmpfile();
            $result = StringFormatter::toString($resource);
            expect($result)->toStartWith('Resource id #');
            fclose($resource);
        });
    });

    describe('isStringCompatible()', function () {
        it('returns true for string', function () {
            expect(StringFormatter::isStringCompatible('test'))->toBeTrue();
        });

        it('returns true for number', function () {
            expect(StringFormatter::isStringCompatible(42))->toBeTrue();
        });

        it('returns true for boolean', function () {
            expect(StringFormatter::isStringCompatible(true))->toBeTrue();
        });

        it('returns true for null', function () {
            expect(StringFormatter::isStringCompatible(null))->toBeTrue();
        });

        it('returns true for array', function () {
            expect(StringFormatter::isStringCompatible([1, 2]))->toBeTrue();
        });

        it('returns true for SassList', function () {
            $list = new SassList(['a']);
            expect(StringFormatter::isStringCompatible($list))->toBeTrue();
        });

        it('returns true for object with __toString', function () {
            $obj = new class () {
                public function __toString(): string
                {
                    return 'test';
                }
            };
            expect(StringFormatter::isStringCompatible($obj))->toBeTrue();
        });

        it('returns false for object without __toString', function () {
            $obj = new stdClass();
            expect(StringFormatter::isStringCompatible($obj))->toBeFalse();
        });
    });

    describe('quoteString()', function () {
        it('adds quotes to string with spaces', function () {
            expect(StringFormatter::quoteString('hello world'))->toBe('"hello world"');
        });

        it('adds quotes to string with special chars', function () {
            expect(StringFormatter::quoteString('hello-world!'))->toBe('"hello-world!"');
        });

        it('does not add quotes to simple string', function () {
            expect(StringFormatter::quoteString('helloworld'))->toBe('helloworld');
        });

        it('does not add quotes to already quoted string', function () {
            expect(StringFormatter::quoteString('"already quoted"'))->toBe('"already quoted"');
        });

        it('handles empty array', function () {
            expect(StringFormatter::toString([]))->toBe('[]');
        });

        it('handles array with only unit', function () {
            expect(StringFormatter::toString(['unit' => 'px']))->toBe('px');
        });

        it('handles empty string', function () {
            expect(StringFormatter::quoteString(''))->toBe('');
        });
    });

    describe('unquoteString()', function () {
        it('removes quotes', function () {
            expect(StringFormatter::unquoteString('"hello world"'))->toBe('hello world');
        });

        it('removes single quotes', function () {
            expect(StringFormatter::unquoteString("'hello'"))->toBe('hello');
        });

        it('does not change unquoted string', function () {
            expect(StringFormatter::unquoteString('hello'))->toBe('hello');
        });

        it('does not change string with mismatched quotes', function () {
            expect(StringFormatter::unquoteString('"hello\''))->toBe('"hello\'');
        });

        it('handles empty string', function () {
            expect(StringFormatter::unquoteString(''))->toBe('');
        });
    });

    describe('isQuoted()', function () {
        it('returns true for quoted string', function () {
            expect(StringFormatter::isQuoted('"hello"'))->toBeTrue();
        });

        it('returns true for single quotes', function () {
            expect(StringFormatter::isQuoted("'hello'"))->toBeTrue();
        });

        it('returns false for unquoted string', function () {
            expect(StringFormatter::isQuoted('hello'))->toBeFalse();
        });

        it('returns false for short string', function () {
            expect(StringFormatter::isQuoted('"a'))->toBeFalse();
        });

        it('return false for empty string', function () {
            expect(StringFormatter::isQuoted(''))->toBeFalse();
        });
    });
})->covers(StringFormatter::class);
