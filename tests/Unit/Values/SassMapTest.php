<?php

declare(strict_types=1);

use DartSass\Handlers\MixinHandler;
use DartSass\Values\SassMap;
use DartSass\Values\SassMixin;

describe('SassMap', function () {
    describe('__toString()', function () {
        it('formats map with string keys and values', function () {
            $map = new SassMap(['key1' => 'value1', 'key2' => 'value2']);

            expect((string) $map)->toBe('(key1: "value1", key2: "value2")');
        });

        it('formats map with numeric keys', function () {
            $map = new SassMap([1 => 'value1', 2 => 'value2']);

            expect((string) $map)->toBe('(1: "value1", 2: "value2")');
        });

        it('formats empty map', function () {
            $map = new SassMap([]);

            expect((string) $map)->toBe('()');
        });

        it('formats map with mixed values', function () {
            $map = new SassMap(['a' => 1, 'b' => true, 'c' => null]);

            expect((string) $map)->toBe('(a: 1, b: true, c: null)');
        });

        it('formats map with SassMixin value', function () {
            $handler = mock(MixinHandler::class);
            $mixin   = new SassMixin($handler, 'testMixin');
            $map     = new SassMap(['mixin' => $mixin]);

            expect((string) $map)->toBe('(mixin: testMixin)');
        });

        it('formats map with object having __toString', function () {
            $obj = new class () {
                public function __toString(): string
                {
                    return 'custom object';
                }
            };
            $map = new SassMap(['obj' => $obj]);

            expect((string) $map)->toBe('(obj: custom object)');
        });

        it('formats map with array value', function () {
            $map = new SassMap(['arr' => [1, 2, 3]]);

            expect((string) $map)->toBe('(arr: [1,2,3])');
        });

        it('formats map with object value', function () {
            $obj = new stdClass();
            $obj->prop = 'value';
            $map = new SassMap(['std' => $obj]);

            expect((string) $map)->toBe('(std: {"prop":"value"})');
        });
    });
})->covers(SassMap::class);
