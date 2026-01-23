<?php

declare(strict_types=1);

use DartSass\Handlers\Builtins\FormatFunctionHandler;
use DartSass\Handlers\SassModule;
use DartSass\Utils\ResultFormatterInterface;
use Tests\ReflectionAccessor;

beforeEach(function () {
    $this->resultFormatter = mock(ResultFormatterInterface::class);
    $this->handler  = new FormatFunctionHandler($this->resultFormatter);
    $this->accessor = new ReflectionAccessor($this->handler);
});

describe('FormatFunctionHandler', function () {
    describe('canHandle method', function () {
        it('returns true for format function', function () {
            expect($this->handler->canHandle('format'))->toBeTrue();
        });

        it('returns false for other functions', function () {
            expect($this->handler->canHandle('other'))->toBeFalse()
                ->and($this->handler->canHandle('format-string'))->toBeFalse()
                ->and($this->handler->canHandle('printf'))->toBeFalse();
        });
    });

    describe('handle method', function () {
        it('formats single argument correctly', function () {
            $this->resultFormatter->shouldReceive('format')
                ->with('test')
                ->andReturn('test');

            $result = $this->handler->handle('format', ['test']);
            expect($result)->toBe('format("test")');
        });

        it('formats multiple arguments correctly', function () {
            $this->resultFormatter->shouldReceive('format')
                ->with('first')
                ->andReturn('first');
            $this->resultFormatter->shouldReceive('format')
                ->with('second')
                ->andReturn('second');
            $this->resultFormatter->shouldReceive('format')
                ->with('third')
                ->andReturn('third');

            $result = $this->handler->handle('format', ['first', 'second', 'third']);
            expect($result)->toBe('format("first", "second", "third")');
        });

        it('wraps non-quoted values with double quotes', function () {
            $this->resultFormatter->shouldReceive('format')
                ->with('unquoted')
                ->andReturn('unquoted');

            $result = $this->handler->handle('format', ['unquoted']);
            expect($result)->toBe('format("unquoted")');
        });

        it('converts single quotes to double quotes', function () {
            $this->resultFormatter->shouldReceive('format')
                ->with('single-quoted')
                ->andReturn("'single-quoted'");

            $result = $this->handler->handle('format', ['single-quoted']);
            expect($result)->toBe('format("single-quoted")');
        });

        it('preserves already double-quoted values', function () {
            $this->resultFormatter->shouldReceive('format')
                ->with('already-quoted')
                ->andReturn('"already-quoted"');

            $result = $this->handler->handle('format', ['already-quoted']);
            expect($result)->toBe('format("already-quoted")');
        });

        it('handles empty arguments array', function () {
            $result = $this->handler->handle('format', []);
            expect($result)->toBe('format()');
        });

        it('handles numeric values', function () {
            $this->resultFormatter->shouldReceive('format')
                ->with(42)
                ->andReturn('42');
            $this->resultFormatter->shouldReceive('format')
                ->with(3.14)
                ->andReturn('3.14');

            $result = $this->handler->handle('format', [42, 3.14]);
            expect($result)->toBe('format("42", "3.14")');
        });

        it('handles boolean values', function () {
            $this->resultFormatter->shouldReceive('format')
                ->with(true)
                ->andReturn('true');
            $this->resultFormatter->shouldReceive('format')
                ->with(false)
                ->andReturn('false');

            $result = $this->handler->handle('format', [true, false]);
            expect($result)->toBe('format("true", "false")');
        });

        it('handles null value', function () {
            $this->resultFormatter->shouldReceive('format')
                ->with(null)
                ->andReturn('null');

            $result = $this->handler->handle('format', [null]);
            expect($result)->toBe('format("null")');
        });

        it('handles complex nested values', function () {
            $this->resultFormatter->shouldReceive('format')
                ->with(['nested' => 'value'])
                ->andReturn('[object Object]');

            $result = $this->handler->handle('format', [['nested' => 'value']]);
            expect($result)->toBe('format("[object Object]")');
        });
    });

    describe('requiresRawResult method', function () {
        it('returns false for format function', function () {
            expect($this->handler->requiresRawResult('format'))->toBeFalse();
        });
    });

    describe('getModuleNamespace method', function () {
        it('returns CSS namespace', function () {
            expect($this->handler->getModuleNamespace())->toEqual(SassModule::CSS);
        });
    });

    describe('getSupportedFunctions method', function () {
        it('returns array with format function', function () {
            expect($this->handler->getSupportedFunctions())->toEqual(['format']);
        });
    });

    describe('getModuleFunctions method', function () {
        it('returns empty array for FormatFunctionHandler', function () {
            expect($this->handler->getModuleFunctions())->toEqual([]);
        });
    });

    describe('getGlobalFunctions method', function () {
        it('returns array with format function', function () {
            expect($this->handler->getGlobalFunctions())->toEqual(['format']);
        });
    });
});
