<?php

declare(strict_types=1);

use DartSass\Modules\MapModule;
use DartSass\Values\SassList;
use DartSass\Values\SassMap;

beforeEach(function () {
    $this->mapModule = new MapModule();
});

describe('MapModule', function () {
    describe('deepMerge()', function () {
        it('can deep merge maps', function () {
            $map1   = ['outer' => ['inner1' => 'value1']];
            $map2   = ['outer' => ['inner2' => 'value2']];
            $result = $this->mapModule->deepMerge([$map1, $map2]);

            expect($result)->toBeInstanceOf(SassMap::class)
                ->and($result->value)->toEqual(['outer' => ['inner1' => 'value1', 'inner2' => 'value2']]);
        });

        it('returns first arg when first arg is not a map', function () {
            $map2   = ['b' => 2];
            $result = $this->mapModule->deepMerge(['string', $map2]);

            expect($result)->toBe('string');
        });

        it('returns first arg when second arg is not a map', function () {
            $map1   = ['a' => 1];
            $result = $this->mapModule->deepMerge([$map1, 'string']);

            expect($result)->toEqual(['a' => 1]);
        });

        it('returns null when first arg is null and second is map', function () {
            $map2   = ['b' => 2];
            $result = $this->mapModule->deepMerge([null, $map2]);

            expect($result)->toBeNull();
        });

        it('returns first arg when both args are not maps', function () {
            $result = $this->mapModule->deepMerge(['string1', 'string2']);

            expect($result)->toBe('string1');
        });

        it('returns null when both args are null', function () {
            $result = $this->mapModule->deepMerge([null, null]);

            expect($result)->toBeNull();
        });
    });

    describe('deepRemove()', function () {
        it('can deep remove keys from map', function () {
            $map    = ['outer' => ['inner' => 'value', 'other' => 'data']];
            $result = $this->mapModule->deepRemove([$map, 'outer', 'inner']);

            expect($result)->toBeInstanceOf(SassMap::class)
                ->and($result->value)->toEqual(['outer' => ['other' => 'data']]);
        });

        it('returns map when no keys provided for deep remove', function () {
            $map    = ['a' => 1, 'b' => 2];
            $result = $this->mapModule->deepRemove([$map]);

            expect($result)->toBeInstanceOf(SassMap::class)
                ->and($result->value)->toEqual($map);
        });

        it('can deep remove top level key', function () {
            $map    = ['a' => 1, 'b' => 2];
            $result = $this->mapModule->deepRemove([$map, 'a']);

            expect($result)->toBeInstanceOf(SassMap::class)
                ->and($result->value)->toEqual(['b' => 2]);
        });

        it('returns unchanged map when trying to deep remove from non-array value', function () {
            $map    = ['a' => 1, 'b' => 2];
            $result = $this->mapModule->deepRemove([$map, 'a', 'b']);

            expect($result)->toBeInstanceOf(SassMap::class)
                ->and($result->value)->toEqual($map);
        });

        it('returns first arg when first arg is not a map', function () {
            $result = $this->mapModule->deepRemove(['string', 'key']);

            expect($result)->toBe('string');
        });

        it('returns null when first arg is null', function () {
            $result = $this->mapModule->deepRemove([null, 'key']);

            expect($result)->toBeNull();
        });

        it('returns number when first arg is number', function () {
            $result = $this->mapModule->deepRemove([123, 'key']);

            expect($result)->toBe(123);
        });
    });

    describe('get()', function () {
        it('can get value from map', function () {
            $map    = ['a' => 1, 'b' => 2, 'c' => 3];
            $result = $this->mapModule->get([$map, 'b']);

            expect($result)->toBe(2);
        });

        it('can get nested value from map', function () {
            $map    = ['outer' => ['inner' => 'value']];
            $result = $this->mapModule->get([$map, 'outer', 'inner']);

            expect($result)->toBe('value');
        });

        it('can get value with array key', function () {
            $map    = ['outer' => ['nested' => 'value']];
            $result = $this->mapModule->get([$map, ['outer', 'nested']]);

            expect($result)->toBe('value');
        });

        it('can get value from SassList map', function () {
            $list   = new SassList(['a', ':', 1, 'b', ':', 2], 'comma');
            $result = $this->mapModule->get([$list, 'a']);

            expect($result)->toBe(1);
        });

        it('can get nested value from SassList map with nested SassList', function () {
            $nested = new SassList(['inner', ':', 'value'], 'comma');
            $list   = new SassList(['a', ':', 1, 'b', ':', $nested], 'comma');
            $result = $this->mapModule->get([$list, 'b', 'inner']);

            expect($result)->toBe('value');
        });

        it('returns null for non-existent key', function () {
            $map    = ['a' => 1, 'b' => 2];
            $result = $this->mapModule->get([$map, 'nonexistent']);

            expect($result)->toBeNull();
        });

        it('returns null when map is not a map', function () {
            $result = $this->mapModule->get(['string', 'key']);

            expect($result)->toBeNull();
        });

        it('returns null when map is null', function () {
            $result = $this->mapModule->get([null, 'key']);

            expect($result)->toBeNull();
        });

        it('returns null when map is a unit array', function () {
            $map    = ['value' => 10, 'unit' => 'px'];
            $result = $this->mapModule->get([$map, 'key']);

            expect($result)->toBeNull();
        });

        it('returns map when direct map passed', function () {
            $map    = ['a' => 1, 'b' => 2];
            $result = $this->mapModule->get($map);

            expect($result)->toBeInstanceOf(SassMap::class)
                ->and($result->value)->toEqual($map);
        });

        it('wraps json string result into SassMap', function () {
            $map    = ['data' => '{"a":1,"b":2}'];
            $result = $this->mapModule->get([$map, 'data']);

            expect($result)->toBeInstanceOf(SassMap::class)
                ->and($result->value)->toEqual(['a' => 1, 'b' => 2]);
        });
    });

    describe('hasKey()', function () {
        it('can check if key exists', function () {
            $map    = ['a' => 1, 'b' => 2];
            $result = $this->mapModule->hasKey([$map, 'b']);

            expect($result)->toBeTrue();
        });

        it('can check if nested key exists', function () {
            $map    = ['outer' => ['inner' => 'value']];
            $result = $this->mapModule->hasKey([$map, 'outer', 'inner']);

            expect($result)->toBeTrue();
        });

        it('returns false for non-existent key check', function () {
            $map    = ['a' => 1, 'b' => 2];
            $result = $this->mapModule->hasKey([$map, 'nonexistent']);

            expect($result)->toBeFalse();
        });

        it('returns false when map is not a map', function () {
            $result = $this->mapModule->hasKey(['string', 'key']);

            expect($result)->toBeFalse();
        });

        it('returns false when map is null', function () {
            $result = $this->mapModule->hasKey([null, 'key']);

            expect($result)->toBeFalse();
        });

        it('returns false when array key has non-existent nested key', function () {
            $map    = ['outer' => ['inner' => 'value']];
            $result = $this->mapModule->hasKey([$map, ['outer', 'nonexistent']]);

            expect($result)->toBeFalse();
        });
    });

    describe('keys()', function () {
        it('can get keys from map', function () {
            $map    = ['a' => 1, 'b' => 2, 'c' => 3];
            $result = $this->mapModule->keys([$map]);

            expect($result)->toBeInstanceOf(SassList::class)
                ->and($result->value)->toEqual(['a', 'b', 'c']);
        });

        it('returns null when map is not a map', function () {
            $result = $this->mapModule->keys(['string']);

            expect($result)->toBeNull();
        });

        it('returns null when map is null', function () {
            $result = $this->mapModule->keys([null]);

            expect($result)->toBeNull();
        });
    });

    describe('merge()', function () {
        it('can merge maps', function () {
            $map1   = ['a' => 1, 'b' => 2];
            $map2   = ['b' => 20, 'c' => 3];
            $result = $this->mapModule->merge([$map1, $map2]);

            expect($result)->toBeInstanceOf(SassMap::class)
                ->and($result->value)->toEqual(['a' => 1, 'b' => 20, 'c' => 3]);
        });

        it('returns first arg when first arg is not a map', function () {
            $map2   = ['b' => 2];
            $result = $this->mapModule->merge(['string', $map2]);

            expect($result)->toBe('string');
        });

        it('returns first arg when second arg is not a map', function () {
            $map1   = ['a' => 1];
            $result = $this->mapModule->merge([$map1, 'string']);

            expect($result)->toEqual(['a' => 1]);
        });

        it('returns null when first arg is null and second is map', function () {
            $map2   = ['b' => 2];
            $result = $this->mapModule->merge([null, $map2]);

            expect($result)->toBeNull();
        });

        it('returns first arg when both args are not maps', function () {
            $result = $this->mapModule->merge(['string1', 'string2']);

            expect($result)->toBe('string1');
        });

        it('returns null when both args are null', function () {
            $result = $this->mapModule->merge([null, null]);

            expect($result)->toBeNull();
        });

        it('merges direct map with empty map', function () {
            $map    = ['a' => 1, 'b' => 2];
            $result = $this->mapModule->merge($map);

            expect($result)->toBeInstanceOf(SassMap::class)
                ->and($result->value)->toEqual($map);
        });

        it('returns null when map is not a map in nested merge', function () {
            $result = $this->mapModule->merge(['string', 'key', ['nested' => 'value']]);

            expect($result)->toBeNull();
        });

        it('sets map value in nested merge when current is not a map', function () {
            $map    = ['a' => 1];
            $result = $this->mapModule->merge([$map, 'a', ['b' => 2]]);

            expect($result)->toBeInstanceOf(SassMap::class)
                ->and($result->value)->toEqual(['a' => ['b' => 2]]);
        });
    });

    describe('remove()', function () {
        it('can remove keys from map', function () {
            $map    = ['a' => 1, 'b' => 2, 'c' => 3];
            $result = $this->mapModule->remove([$map, 'b']);

            expect($result)->toBeInstanceOf(SassMap::class)
                ->and($result->value)->toEqual(['a' => 1, 'c' => 3]);
        });

        it('returns original map when map is not a map', function () {
            $result = $this->mapModule->remove(['string', 'key']);

            expect($result)->toBe('string');
        });

        it('returns null when original map is null', function () {
            $result = $this->mapModule->remove([null, 'key']);

            expect($result)->toBeNull();
        });
    });

    describe('set()', function () {
        it('can set value in map', function () {
            $map    = ['a' => 1, 'b' => 2];
            $result = $this->mapModule->set([$map, 'c', 3]);

            expect($result)->toBeInstanceOf(SassMap::class)
                ->and($result->value)->toEqual(['a' => 1, 'b' => 2, 'c' => 3]);
        });

        it('can set nested value in map', function () {
            $map    = ['outer' => ['inner' => 'old']];
            $result = $this->mapModule->set([$map, 'outer', 'inner', 'new']);

            expect($result)->toBeInstanceOf(SassMap::class)
                ->and($result->value)->toEqual(['outer' => ['inner' => 'new']]);
        });

        it('returns empty map when map is not a map and no keys provided', function () {
            $result = $this->mapModule->set(['string', 'value']);

            expect($result)->toBeInstanceOf(SassMap::class)
                ->and($result->value)->toEqual([]);
        });

        it('sets value in new map when map is not a map', function () {
            $result = $this->mapModule->set(['string', 'key', 'value']);

            expect($result)->toBeInstanceOf(SassMap::class)
                ->and($result->value)->toEqual(['key' => 'value']);
        });
    });

    describe('values()', function () {
        it('can get values from map', function () {
            $map    = ['a' => 1, 'b' => 2, 'c' => 3];
            $result = $this->mapModule->values([$map]);

            expect($result)->toBeInstanceOf(SassList::class)
                ->and($result->value)->toEqual([1, 2, 3]);
        });

        it('returns null when map is not a map', function () {
            $result = $this->mapModule->values(['string']);

            expect($result)->toBeNull();
        });

        it('returns null when map is null', function () {
            $result = $this->mapModule->values([null]);

            expect($result)->toBeNull();
        });
    });
})->covers(MapModule::class);
