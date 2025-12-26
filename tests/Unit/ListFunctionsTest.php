<?php

declare(strict_types=1);

use DartSass\Parsers\Nodes\ListNode;
use DartSass\Utils\ListFunctions;
use DartSass\Utils\SassList;
use DartSass\Exceptions\CompilationException;
use Tests\ReflectionAccessor;

beforeEach(function () {
    $this->listFunctions = new ListFunctions();
    $this->accessor      = new ReflectionAccessor($this->listFunctions);
});

it('appends value to list', function () {
    $result = $this->listFunctions->append([['a', 'b'], 'c']);

    expect($result)->toBeInstanceOf(SassList::class)
        ->and($result->value)->toEqual(['a', 'b', 'c'])
        ->and($result->separator)->toBe('space');
});

it('appends value to list with comma separator', function () {
    $list = new SassList(['a', 'b'], 'comma');
    $result = $this->listFunctions->append([$list, 'c']);

    expect($result)->toBeInstanceOf(SassList::class)
        ->and($result->separator)->toBe('comma');
});

it('appends value to list preserving existing separator', function () {
    $list = new SassList(['a', 'b'], 'comma');
    $result = $this->listFunctions->append([$list, 'c']);

    expect($result)->toBeInstanceOf(SassList::class)
        ->and($result->separator)->toBe('comma');
});

it('throws exception when missing list for append', function () {
    expect(fn() => $this->listFunctions->append([]))
        ->toThrow(CompilationException::class, 'Missing list for append');
});

it('throws exception when missing value for append', function () {
    expect(fn() => $this->listFunctions->append([['a', 'b']]))
        ->toThrow(CompilationException::class, 'Missing value for append');
});

it('finds index of value in list', function () {
    $result = $this->listFunctions->index([['a', 'b', 'c'], 'b']);

    expect($result)->toBe(2);
});

it('returns null when value not found in list', function () {
    $result = $this->listFunctions->index([['a', 'b', 'c'], 'd']);

    expect($result)->toBeNull();
});

it('finds index in comma-separated list', function () {
    $list = new SassList(['a', 'b', 'c'], 'comma');
    $result = $this->listFunctions->index([$list, 'b']);

    expect($result)->toBe(2);
});

it('throws exception when missing list for index', function () {
    expect(fn() => $this->listFunctions->index([]))
        ->toThrow(CompilationException::class, 'Missing list for index');
});

it('throws exception when missing value for index', function () {
    expect(fn() => $this->listFunctions->index([['a', 'b']]))
        ->toThrow(CompilationException::class, 'Missing value for index');
});

it('checks if list is bracketed', function () {
    $result = $this->listFunctions->isBracketed(['[a, b, c]']);

    expect($result)->toBeTrue();
});

it('checks if list is not bracketed', function () {
    $result = $this->listFunctions->isBracketed(['a, b, c']);

    expect($result)->toBeFalse();
});

it('throws exception when missing list for is-bracketed', function () {
    expect(fn() => $this->listFunctions->isBracketed([]))
        ->toThrow(CompilationException::class, 'Missing list for is-bracketed');
});

it('joins two lists', function () {
    $result = $this->listFunctions->join([['a', 'b'], ['c', 'd']]);

    expect($result)->toBeInstanceOf(SassList::class)
        ->and($result->value)->toEqual(['a', 'b', 'c', 'd'])
        ->and($result->separator)->toBe('space');
});

it('joins lists with comma separator', function () {
    $list1 = new SassList(['a', 'b'], 'comma');
    $list2 = new SassList(['c', 'd'], 'comma');
    $result = $this->listFunctions->join([$list1, $list2]);

    expect($result)->toBeInstanceOf(SassList::class)
        ->and($result->separator)->toBe('comma');
});

it('joins lists preserving bracketed property', function () {
    $result = $this->listFunctions->join([['[a, b]'], ['c, d'], '$bracketed', true]);

    expect($result)->toBeInstanceOf(SassList::class)
        ->and($result->bracketed)->toBeTrue();
});

it('throws exception when missing list1 for join', function () {
    expect(fn() => $this->listFunctions->join([]))
        ->toThrow(CompilationException::class, 'Missing list1 for join');
});

it('throws exception when missing list2 for join', function () {
    expect(fn() => $this->listFunctions->join([['a', 'b']]))
        ->toThrow(CompilationException::class, 'Missing list2 for join');
});

it('gets length of list', function () {
    $result = $this->listFunctions->length([['a', 'b', 'c']]);

    expect($result)->toBe(3);
});

it('gets length of string as list', function () {
    $result = $this->listFunctions->length(['a b c']);

    expect($result)->toBe(3);
});

it('gets length of comma-separated string', function () {
    $result = $this->listFunctions->length(['a, b, c']);

    expect($result)->toBe(3);
});

it('gets length of single value', function () {
    $result = $this->listFunctions->length(['a']);

    expect($result)->toBe(1);
});

it('throws exception when missing list for length', function () {
    expect(fn() => $this->listFunctions->length([]))
        ->toThrow(CompilationException::class, 'Missing list for length');
});

it('gets nth element from list', function () {
    $result = $this->listFunctions->nth([['a', 'b', 'c'], 2]);

    expect($result)->toBe('b');
});

it('gets first element from list', function () {
    $result = $this->listFunctions->nth([['a', 'b', 'c'], 1]);

    expect($result)->toBe('a');
});

it('gets last element from list', function () {
    $result = $this->listFunctions->nth([['a', 'b', 'c'], 3]);

    expect($result)->toBe('c');
});

it('throws exception when index out of bounds for nth', function () {
    expect(fn() => $this->listFunctions->nth([['a', 'b'], 3]))
        ->toThrow(CompilationException::class, 'Index 3 out of bounds for list');
});

it('throws exception when missing list for nth', function () {
    expect(fn() => $this->listFunctions->nth([]))
        ->toThrow(CompilationException::class, 'Missing list for nth');
});

it('throws exception when missing index for nth', function () {
    expect(fn() => $this->listFunctions->nth([['a', 'b']]))
        ->toThrow(CompilationException::class, 'Missing index for nth');
});

it('gets separator from comma list', function () {
    $list = new SassList(['a', 'b', 'c'], 'comma');
    $result = $this->listFunctions->separator([$list]);

    expect($result)->toBe('comma');
});

it('gets space separator from list', function () {
    $result = $this->listFunctions->separator([['a b c']]);

    expect($result)->toBe('space');
});

it('throws exception when missing list for separator', function () {
    expect(fn() => $this->listFunctions->separator([]))
        ->toThrow(CompilationException::class, 'Missing list for separator');
});

it('sets nth element in list', function () {
    $result = $this->listFunctions->setNth([['a', 'b', 'c'], 2, 'x']);

    expect($result)->toBeInstanceOf(SassList::class)
        ->and($result->value)->toEqual(['a', 'x', 'c']);
});

it('sets first element in list', function () {
    $result = $this->listFunctions->setNth([['a', 'b', 'c'], 1, 'x']);

    expect($result)->toBeInstanceOf(SassList::class)
        ->and($result->value)->toEqual(['x', 'b', 'c']);
});

it('sets last element in list', function () {
    $result = $this->listFunctions->setNth([['a', 'b', 'c'], 3, 'x']);

    expect($result)->toBeInstanceOf(SassList::class)
        ->and($result->value)->toEqual(['a', 'b', 'x']);
});

it('throws exception when index out of bounds for set-nth', function () {
    expect(fn() => $this->listFunctions->setNth([['a', 'b'], 3, 'x']))
        ->toThrow(CompilationException::class, 'Index 3 out of bounds');
});

it('throws exception when missing list for set-nth', function () {
    expect(fn() => $this->listFunctions->setNth([]))
        ->toThrow(CompilationException::class, 'Missing list for set-nth');
});

it('throws exception when missing index for set-nth', function () {
    expect(fn() => $this->listFunctions->setNth([['a', 'b']]))
        ->toThrow(CompilationException::class, 'Missing index for set-nth');
});

it('throws exception when missing value for set-nth', function () {
    expect(fn() => $this->listFunctions->setNth([['a', 'b'], 1]))
        ->toThrow(CompilationException::class, 'Missing value for set-nth');
});

it('creates slash-separated list', function () {
    $result = $this->listFunctions->slash(['a', 'b', 'c']);

    expect($result)->toBeInstanceOf(SassList::class)
        ->and($result->value)->toEqual(['a', 'b', 'c'])
        ->and($result->separator)->toBe('slash')
        ->and($result->bracketed)->toBeFalse();
});

it('creates slash list with minimum two elements', function () {
    $result = $this->listFunctions->slash(['a', 'b']);

    expect($result)->toBeInstanceOf(SassList::class)
        ->and($result->value)->toEqual(['a', 'b'])
        ->and($result->separator)->toBe('slash');
});

it('throws exception for slash with less than two elements', function () {
    expect(fn() => $this->listFunctions->slash(['a']))
        ->toThrow(CompilationException::class, 'slash requires at least two elements');
});

it('zips multiple lists', function () {
    $result = $this->listFunctions->zip([['a', 'b'], ['1', '2'], ['x', 'y']]);

    expect($result)->toHaveLength(2)
        ->and($result[0])->toBeInstanceOf(SassList::class)
        ->and($result[0]->value)->toEqual(['a', '1', 'x'])
        ->and($result[1]->value)->toEqual(['b', '2', 'y']);
});

it('zips lists with different lengths', function () {
    $result = $this->listFunctions->zip([['a', 'b', 'c'], ['1', '2']]);

    expect($result)->toHaveLength(2)
        ->and($result[0]->value)->toEqual(['a', '1'])
        ->and($result[1]->value)->toEqual(['b', '2']);
});

it('zips single list', function () {
    $result = $this->listFunctions->zip([['a', 'b', 'c']]);

    expect($result)->toHaveLength(3)
        ->and($result[0]->value)->toEqual(['a'])
        ->and($result[1]->value)->toEqual(['b'])
        ->and($result[2]->value)->toEqual(['c']);
});

it('zips empty lists', function () {
    $result = $this->listFunctions->zip([]);

    expect($result)->toBe([]);
});

it('handles negative indices in nth', function () {
    $result = $this->listFunctions->nth([['a', 'b', 'c'], -1]);

    expect($result)->toBe('c');
});

it('handles negative indices in setNth', function () {
    $result = $this->listFunctions->setNth([['a', 'b', 'c'], -1, 'x']);

    expect($result->value)->toEqual(['a', 'b', 'x']);
});

it('handles wrapped single element lists', function () {
    $result = $this->listFunctions->nth([[['a', 'b']], 1]);

    expect($result)->toEqual(['a', 'b']);
});

// Private method tests using ReflectionAccessor
it('parses list argument with regular array', function () {
    $result = $this->accessor->callProtectedMethod('parseListArg', [['a', 'b', 'c']]);

    expect($result)->toEqual(['a', 'b', 'c']);
});

it('parses list argument with wrapped array', function () {
    $result = $this->accessor->callProtectedMethod('parseListArg', [[['a', 'b']]]);

    expect($result)->toEqual([['a', 'b']]);
});

it('parses list argument with SassList object', function () {
    $sassList = new SassList(['a', 'b', 'c'], 'space');
    $result = $this->accessor->callProtectedMethod('parseListArg', [$sassList]);

    expect($result)->toEqual(['a', 'b', 'c']);
});

it('parses list argument with ListNode object', function () {
    $listNode = new ListNode(['x', 'y', 'z'], 0, 'space', false);
    $result = $this->accessor->callProtectedMethod('parseListArg', [$listNode]);

    expect($result)->toEqual(['x', 'y', 'z']);
});

it('parses list argument with space-separated string', function () {
    $result = $this->accessor->callProtectedMethod('parseListArg', ['a b c']);

    expect($result)->toEqual(['a', 'b', 'c']);
});

it('parses list argument with comma-separated string', function () {
    $result = $this->accessor->callProtectedMethod('parseListArg', ['a, b, c']);

    expect($result)->toEqual(['a', 'b', 'c']);
});

it('parses list argument with value array', function () {
    $result = $this->accessor->callProtectedMethod('parseListArg', [['value' => 'test']]);

    expect($result)->toEqual([['value' => 'test']]);
});

it('parses list argument with value and unit array', function () {
    $result = $this->accessor->callProtectedMethod('parseListArg', [['value' => 10, 'unit' => 'px']]);

    expect($result)->toEqual([['value' => 10, 'unit' => 'px']]);
});

it('parses wrapped value with SassList object', function () {
    $sassList = new SassList(['a', 'b'], 'space');
    $result = $this->accessor->callProtectedMethod('parseWrappedValue', [$sassList]);

    expect($result)->toEqual(['a', 'b']);
});

it('parses wrapped value with ListNode object', function () {
    $listNode = new ListNode(['x', 'y'], 0, 'comma', false);
    $result = $this->accessor->callProtectedMethod('parseWrappedValue', [$listNode]);

    expect($result)->toEqual(['x', 'y']);
});

it('parses wrapped value with string', function () {
    $result = $this->accessor->callProtectedMethod('parseWrappedValue', ['hello world']);

    expect($result)->toEqual(['hello', 'world']);
});

it('parses wrapped value with simple value', function () {
    $result = $this->accessor->callProtectedMethod('parseWrappedValue', ['single_value']);

    expect($result)->toEqual(['single_value']);
});

it('parses positive index', function () {
    $result = $this->accessor->callProtectedMethod('parseIndex', [2, 5]);

    expect($result)->toBe(2);
});

it('parses negative index -1', function () {
    $result = $this->accessor->callProtectedMethod('parseIndex', [-1, 5]);

    expect($result)->toBe(5);
});

it('parses negative index -2', function () {
    $result = $this->accessor->callProtectedMethod('parseIndex', [-2, 5]);

    expect($result)->toBe(4);
});

it('parses negative index -3', function () {
    $result = $this->accessor->callProtectedMethod('parseIndex', [-3, 5]);

    expect($result)->toBe(3);
});

it('parses index from value array', function () {
    $result = $this->accessor->callProtectedMethod('parseIndex', [['value' => 3], 5]);

    expect($result)->toBe(3);
});

it('parses negative index from value array', function () {
    $result = $this->accessor->callProtectedMethod('parseIndex', [['value' => -2], 5]);

    expect($result)->toBe(4);
});

it('handles edge case with index 0', function () {
    $result = $this->accessor->callProtectedMethod('parseIndex', [0, 5]);

    expect($result)->toBe(0);
});

it('handles edge case with empty list length', function () {
    $result = $this->accessor->callProtectedMethod('parseIndex', [1, 0]);

    expect($result)->toBe(1);
});

it('handles edge case with negative index for empty list', function () {
    $result = $this->accessor->callProtectedMethod('parseIndex', [-1, 0]);

    expect($result)->toBe(0);
});
