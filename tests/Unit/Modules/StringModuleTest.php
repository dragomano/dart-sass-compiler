<?php

declare(strict_types=1);

use DartSass\Exceptions\CompilationException;
use DartSass\Modules\StringModule;
use Random\RandomException;

beforeEach(function () {
    $this->stringModule = new StringModule();
});

describe('StringModule', function () {
    describe('quote()', function () {
        it('quotes unquoted string', function () {
            $result = $this->stringModule->quote(['hello world']);

            expect($result)->toBe('"hello world"');
        });

        it('leaves double quoted string unchanged', function () {
            $result = $this->stringModule->quote(['"already quoted"']);

            expect($result)->toBe('"already quoted"');
        });

        it('leaves single quoted string unchanged', function () {
            $result = $this->stringModule->quote(["'single quoted'"]);

            expect($result)->toBe("'single quoted'");
        });

        it('throws exception when missing argument for quote', function () {
            expect(fn() => $this->stringModule->quote([]))
                ->toThrow(CompilationException::class, 'quote() requires exactly one argument');
        });

        it('throws exception when too many arguments for quote', function () {
            expect(fn() => $this->stringModule->quote(['string', 'extra']))
                ->toThrow(CompilationException::class, 'quote() requires exactly one argument');
        });

        it('throws exception when argument is not string for quote', function () {
            expect(fn() => $this->stringModule->quote([123]))
                ->toThrow(CompilationException::class, 'quote() argument must be a string');
        });
    });

    describe('index()', function () {
        it('finds index of substring', function () {
            $result = $this->stringModule->index(['hello world', 'world']);

            expect($result)->toBe(7);
        });

        it('returns null when substring not found', function () {
            $result = $this->stringModule->index(['hello world', 'missing']);

            expect($result)->toBeNull();
        });

        it('returns 1 for substring at start', function () {
            $result = $this->stringModule->index(['hello world', 'hello']);

            expect($result)->toBe(1);
        });

        it('throws exception when missing first argument for index', function () {
            expect(fn() => $this->stringModule->index([]))
                ->toThrow(CompilationException::class, 'index() requires exactly two arguments');
        });

        it('throws exception when missing second argument for index', function () {
            expect(fn() => $this->stringModule->index(['string']))
                ->toThrow(CompilationException::class, 'index() requires exactly two arguments');
        });

        it('throws exception when too many arguments for index', function () {
            expect(fn() => $this->stringModule->index(['string', 'sub', 'extra']))
                ->toThrow(CompilationException::class, 'index() requires exactly two arguments');
        });

        it('throws exception when first argument is not string for index', function () {
            expect(fn() => $this->stringModule->index([123, 'sub']))
                ->toThrow(CompilationException::class, 'index() first argument must be a string');
        });

        it('throws exception when second argument is not string for index', function () {
            expect(fn() => $this->stringModule->index(['string', 123]))
                ->toThrow(CompilationException::class, 'index() second argument must be a string');
        });
    });

    describe('insert()', function () {
        it('inserts substring at the beginning', function () {
            $result = $this->stringModule->insert(['world', 'hello ', 1]);

            expect($result)->toBe('"hello world"');
        });

        it('inserts substring in the middle', function () {
            $result = $this->stringModule->insert(['heo', 'll', 3]);

            expect($result)->toBe('"hello"');
        });

        it('inserts substring at the end', function () {
            $result = $this->stringModule->insert(['hello', ' world', 6]);

            expect($result)->toBe('"hello world"');
        });

        it('inserts into empty string', function () {
            $result = $this->stringModule->insert(['', 'hello', 1]);

            expect($result)->toBe('"hello"');
        });

        it('handles index less than 1', function () {
            $result = $this->stringModule->insert(['world', 'hello ', 0]);

            expect($result)->toBe('"hello world"');
        });

        it('handles index greater than string length', function () {
            $result = $this->stringModule->insert(['hello', ' world', 10]);

            expect($result)->toBe('"hello world"');
        });

        it('throws exception when missing arguments for insert', function () {
            expect(fn() => $this->stringModule->insert([]))
                ->toThrow(CompilationException::class, 'insert() requires exactly three arguments');
        });

        it('throws exception when too few arguments for insert', function () {
            expect(fn() => $this->stringModule->insert(['string', 'insert']))
                ->toThrow(CompilationException::class, 'insert() requires exactly three arguments');
        });

        it('throws exception when too many arguments for insert', function () {
            expect(fn() => $this->stringModule->insert(['string', 'insert', 1, 'extra']))
                ->toThrow(CompilationException::class, 'insert() requires exactly three arguments');
        });

        it('throws exception when first argument is not string for insert', function () {
            expect(fn() => $this->stringModule->insert([123, 'insert', 1]))
                ->toThrow(CompilationException::class, 'insert() first argument must be a string');
        });

        it('throws exception when second argument is not string for insert', function () {
            expect(fn() => $this->stringModule->insert(['string', 123, 1]))
                ->toThrow(CompilationException::class, 'insert() second argument must be a string');
        });

        it('throws exception when third argument is not number for insert', function () {
            expect(fn() => $this->stringModule->insert(['string', 'insert', 'notnumber']))
                ->toThrow(CompilationException::class, 'insert() third argument must be a number');
        });
    });

    describe('length()', function () {
        it('returns length of string', function () {
            $result = $this->stringModule->length(['hello']);

            expect($result)->toBe(5);
        });

        it('returns length of empty string', function () {
            $result = $this->stringModule->length(['']);

            expect($result)->toBe(0);
        });

        it('returns length of quoted string', function () {
            $result = $this->stringModule->length(['"hello world"']);

            expect($result)->toBe(11);
        });

        it('throws exception when missing argument for length', function () {
            expect(fn() => $this->stringModule->length([]))
                ->toThrow(CompilationException::class, 'length() requires exactly one argument');
        });

        it('throws exception when too many arguments for length', function () {
            expect(fn() => $this->stringModule->length(['string', 'extra']))
                ->toThrow(CompilationException::class, 'length() requires exactly one argument');
        });

        it('throws exception when argument is not string for length', function () {
            expect(fn() => $this->stringModule->length([123]))
                ->toThrow(CompilationException::class, 'length() argument must be a string');
        });
    });

    describe('slice()', function () {
        it('slices string from start', function () {
            $result = $this->stringModule->slice(['hello world', 1]);

            expect($result)->toBe('"hello world"');
        });

        it('slices string with start and end', function () {
            $result = $this->stringModule->slice(['hello world', 1, 6]);

            expect($result)->toBe('"hello "');
        });

        it('slices string with negative start', function () {
            $result = $this->stringModule->slice(['hello world', -5]);

            expect($result)->toBe('"world"');
        });

        it('slices string with negative end', function () {
            $result = $this->stringModule->slice(['hello world', 1, -6]);

            expect($result)->toBe('"hello"');
        });

        it('returns empty string for invalid range', function () {
            $result = $this->stringModule->slice(['hello', 3, 2]);

            expect($result)->toBe('""');
        });

        it('handles quoted string', function () {
            $result = $this->stringModule->slice(['"hello world"', 7, 11]);

            expect($result)->toBe('"world"');
        });

        it('throws exception when missing arguments for slice', function () {
            expect(fn() => $this->stringModule->slice([]))
                ->toThrow(CompilationException::class, 'slice() requires two or three arguments');
        });

        it('throws exception when too many arguments for slice', function () {
            expect(fn() => $this->stringModule->slice(['string', 1, 2, 'extra']))
                ->toThrow(CompilationException::class, 'slice() requires two or three arguments');
        });

        it('throws exception when first argument is not string for slice', function () {
            expect(fn() => $this->stringModule->slice([123, 1]))
                ->toThrow(CompilationException::class, 'slice() first argument must be a string');
        });

        it('throws exception when second argument is not number for slice', function () {
            expect(fn() => $this->stringModule->slice(['string', 'notnumber']))
                ->toThrow(CompilationException::class, 'slice() second argument must be a number');
        });

        it('throws exception when third argument is not number for slice', function () {
            expect(fn() => $this->stringModule->slice(['string', 1, 'notnumber']))
                ->toThrow(CompilationException::class, 'slice() third argument must be a number');
        });

        it('handles startAt equal to 0', function () {
            $result = $this->stringModule->slice(['hello', 0]);

            expect($result)->toBe('"hello"');
        });

        it('handles endAt equal to 0', function () {
            $result = $this->stringModule->slice(['hello', 1, 0]);

            expect($result)->toBe('""');
        });
    });

    describe('split()', function () {
        it('splits string by separator', function () {
            $result = $this->stringModule->split(['hello,world', ',']);

            expect($result)->toBe(['"hello"', '"world"']);
        });

        it('splits string with limit', function () {
            $result = $this->stringModule->split(['a,b,c', ',', 2]);

            expect($result)->toBe(['"a"', '"b,c"']);
        });

        it('splits string into characters when separator is empty', function () {
            $result = $this->stringModule->split(['abc', '']);

            expect($result)->toBe(['"a"', '"b"', '"c"']);
        });

        it('handles quoted string', function () {
            $result = $this->stringModule->split(['"hello world"', ' ']);

            expect($result)->toBe(['"hello"', '"world"']);
        });

        it('returns array with single element when separator not found', function () {
            $result = $this->stringModule->split(['hello', ',']);

            expect($result)->toBe(['"hello"']);
        });

        it('throws exception when missing arguments for split', function () {
            expect(fn() => $this->stringModule->split([]))
                ->toThrow(CompilationException::class, 'split() requires two or three arguments');
        });

        it('throws exception when too many arguments for split', function () {
            expect(fn() => $this->stringModule->split(['string', ',', 1, 'extra']))
                ->toThrow(CompilationException::class, 'split() requires two or three arguments');
        });

        it('throws exception when first argument is not string for split', function () {
            expect(fn() => $this->stringModule->split([123, ',']))
                ->toThrow(CompilationException::class, 'split() first argument must be a string');
        });

        it('throws exception when second argument is not string for split', function () {
            expect(fn() => $this->stringModule->split(['string', 123]))
                ->toThrow(CompilationException::class, 'split() second argument must be a string');
        });

        it('throws exception when third argument is not number for split', function () {
            expect(fn() => $this->stringModule->split(['string', ',', 'notnumber']))
                ->toThrow(CompilationException::class, 'split() third argument must be a number');
        });
    });

    describe('toUpperCase()', function () {
        it('converts string to uppercase', function () {
            $result = $this->stringModule->toUpperCase(['hello world']);

            expect($result)->toBe('"HELLO WORLD"');
        });

        it('handles empty string', function () {
            $result = $this->stringModule->toUpperCase(['']);

            expect($result)->toBe('""');
        });

        it('handles quoted string', function () {
            $result = $this->stringModule->toUpperCase(['"hello world"']);

            expect($result)->toBe('"HELLO WORLD"');
        });

        it('handles unicode characters', function () {
            $result = $this->stringModule->toUpperCase(['héllo wörld']);

            expect($result)->toBe('"HéLLO WöRLD"');
        });

        it('throws exception when missing argument for toUpperCase', function () {
            expect(fn() => $this->stringModule->toUpperCase([]))
                ->toThrow(CompilationException::class, 'to-upper-case() requires exactly one argument');
        });

        it('throws exception when too many arguments for toUpperCase', function () {
            expect(fn() => $this->stringModule->toUpperCase(['string', 'extra']))
                ->toThrow(CompilationException::class, 'to-upper-case() requires exactly one argument');
        });

        it('throws exception when argument is not string for toUpperCase', function () {
            expect(fn() => $this->stringModule->toUpperCase([123]))
                ->toThrow(CompilationException::class, 'to-upper-case() argument must be a string');
        });
    });

    describe('toLowerCase()', function () {
        it('converts string to lowercase', function () {
            $result = $this->stringModule->toLowerCase(['HELLO WORLD']);

            expect($result)->toBe('"hello world"');
        });

        it('handles empty string', function () {
            $result = $this->stringModule->toLowerCase(['']);

            expect($result)->toBe('""');
        });

        it('handles quoted string', function () {
            $result = $this->stringModule->toLowerCase(['"HELLO WORLD"']);

            expect($result)->toBe('"hello world"');
        });

        it('handles unicode characters', function () {
            $result = $this->stringModule->toLowerCase(['HÉLLO WÖRLD']);

            expect($result)->toBe('"hÉllo wÖrld"');
        });

        it('throws exception when missing argument for toLowerCase', function () {
            expect(fn() => $this->stringModule->toLowerCase([]))
                ->toThrow(CompilationException::class, 'to-lower-case() requires exactly one argument');
        });

        it('throws exception when too many arguments for toLowerCase', function () {
            expect(fn() => $this->stringModule->toLowerCase(['string', 'extra']))
                ->toThrow(CompilationException::class, 'to-lower-case() requires exactly one argument');
        });

        it('throws exception when argument is not string for toLowerCase', function () {
            expect(fn() => $this->stringModule->toLowerCase([123]))
                ->toThrow(CompilationException::class, 'to-lower-case() argument must be a string');
        });
    });

    describe('uniqueId()', function () {
        it('returns a quoted string starting with a letter', function () {
            $result = $this->stringModule->uniqueId([]);

            expect($result)->toBeString()
                ->and($result)->toMatch('/^"[a-zA-Z][a-zA-Z0-9]*"$/');
        });

        it('throws exception when arguments provided', function () {
            expect(fn() => $this->stringModule->uniqueId(['arg']))
                ->toThrow(CompilationException::class, 'unique-id() takes no arguments');
        });

        it('throws CompilationException when random_int fails', function () {
            $module = mock(StringModule::class)->makePartial();
            $module->shouldAllowMockingProtectedMethods();
            $module->shouldReceive('generateRandomInt')
                ->once()
                ->with(0, 51)
                ->andThrow(new RandomException('Entropy source failed'));

            $this->expectException(CompilationException::class);
            $this->expectExceptionMessage('Entropy source failed');

            $module->uniqueId([]);
        });

        it('generates unique identifiers on multiple calls', function () {
            $results = [];
            for ($i = 0; $i < 100; $i++) {
                $results[] = $this->stringModule->uniqueId([]);
            }
            $uniqueResults = array_unique($results);
            expect(count($results))->toBe(count($uniqueResults));
        });
    });

    describe('unquote()', function () {
        it('unquotes double quoted string', function () {
            $result = $this->stringModule->unquote(['"hello world"']);

            expect($result)->toBe('hello world');
        });

        it('unquotes single quoted string', function () {
            $result = $this->stringModule->unquote(["'hello world'"]);

            expect($result)->toBe('hello world');
        });

        it('leaves unquoted string unchanged', function () {
            $result = $this->stringModule->unquote(['hello world']);

            expect($result)->toBe('hello world');
        });

        it('handles escape sequences', function () {
            $result = $this->stringModule->unquote(['"hello\\nworld"']);

            expect($result)->toBe('hellonworld');
        });

        it('returns null for empty string', function () {
            $result = $this->stringModule->unquote(['""']);

            expect($result)->toBeNull();
        });

        it('throws exception when missing argument for unquote', function () {
            expect(fn() => $this->stringModule->unquote([]))
                ->toThrow(CompilationException::class, 'unquote() requires exactly one argument');
        });

        it('throws exception when too many arguments for unquote', function () {
            expect(fn() => $this->stringModule->unquote(['string', 'extra']))
                ->toThrow(CompilationException::class, 'unquote() requires exactly one argument');
        });

        it('throws exception when argument is not string for unquote', function () {
            expect(fn() => $this->stringModule->unquote([123]))
                ->toThrow(CompilationException::class, 'unquote() argument must be a string');
        });
    });
})->covers(StringModule::class);
