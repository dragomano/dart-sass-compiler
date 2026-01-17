<?php

declare(strict_types=1);

use DartSass\Handlers\Builtins\CustomFunctionHandler;
use DartSass\Handlers\ModuleRegistry;
use DartSass\Handlers\SassModule;
use Tests\ReflectionAccessor;

beforeEach(function () {
    $this->handler  = new CustomFunctionHandler();
    $this->accessor = new ReflectionAccessor($this->handler);
});

describe('CustomFunctionHandler', function () {
    describe('canHandle method', function () {
        it('returns false when no custom functions are registered', function () {
            expect($this->handler->canHandle('testFunction'))->toBeFalse()
                ->and($this->handler->canHandle('anotherFunction'))->toBeFalse();
        });

        it('returns true for registered custom functions', function () {
            $callback = fn() => 'result';
            $this->handler->addCustomFunction('myFunction', $callback);

            expect($this->handler->canHandle('myFunction'))->toBeTrue();
        });

        it('returns false for non-registered functions even when others are registered', function () {
            $callback = fn() => 'result';
            $this->handler->addCustomFunction('registeredFunction', $callback);

            expect($this->handler->canHandle('unregisteredFunction'))->toBeFalse();
        });

        it('handles multiple custom functions correctly', function () {
            $callback1 = fn() => 'result1';
            $callback2 = fn() => 'result2';

            $this->handler->addCustomFunction('func1', $callback1);
            $this->handler->addCustomFunction('func2', $callback2);

            expect($this->handler->canHandle('func1'))->toBeTrue()
                ->and($this->handler->canHandle('func2'))->toBeTrue()
                ->and($this->handler->canHandle('func3'))->toBeFalse();
        });
    });

    describe('handle method', function () {
        it('returns null for unregistered functions', function () {
            $result = $this->handler->handle('nonExistentFunction', ['arg1', 'arg2']);
            expect($result)->toBeNull();
        });

        it('executes registered function with arguments', function () {
            $callback = fn($a, $b) => $a + $b;
            $this->handler->addCustomFunction('add', $callback);

            $result = $this->handler->handle('add', [5, 3]);
            expect($result)->toBe(8);
        });

        it('passes arguments correctly to custom function', function () {
            $callback = fn($str, $num) => strtoupper($str) . '-' . $num;
            $this->handler->addCustomFunction('format', $callback);

            $result = $this->handler->handle('format', ['hello', 42]);
            expect($result)->toBe('HELLO-42');
        });

        it('handles function with no arguments', function () {
            $callback = fn() => 'no args result';
            $this->handler->addCustomFunction('noArgs', $callback);

            $result = $this->handler->handle('noArgs', []);
            expect($result)->toBe('no args result');
        });

        it('preserves argument types when passing to callback', function () {
            $callback = fn($arr, $bool, $null) => [
                'array' => is_array($arr),
                'bool' => is_bool($bool),
                'null' => is_null($null),
            ];

            $this->handler->addCustomFunction('typeCheck', $callback);

            $result = $this->handler->handle('typeCheck', [['test'], true, null]);
            expect($result)->toBeArray()
                ->and($result['array'])->toBeTrue()
                ->and($result['bool'])->toBeTrue()
                ->and($result['null'])->toBeTrue();
        });

        it('applies unit from first argument to numeric result', function () {
            $callback = fn($value) => $value * 2;
            $this->handler->addCustomFunction('doubleValue', $callback);

            $result = $this->handler->handle('doubleValue', [['value' => 5, 'unit' => 'px']]);
            expect($result)->toBeArray()
                ->and($result['value'])->toEqual(10)
                ->and($result['unit'])->toBe('px');
        });

        it('does not apply unit when result is not numeric', function () {
            $callback = fn($value) => 'result';
            $this->handler->addCustomFunction('stringResult', $callback);

            $result = $this->handler->handle('stringResult', [['value' => 5, 'unit' => 'px']]);
            expect($result)->toBe('result');
        });

        it('handles array arguments with value extraction', function () {
            $callback = fn($val1, $val2) => $val1 + $val2;
            $this->handler->addCustomFunction('sumValues', $callback);

            $result = $this->handler->handle('sumValues', [
                ['value' => 10],
                ['value' => 20],
            ]);
            expect($result)->toEqual(30);
        });
    });

    describe('getSupportedFunctions method', function () {
        it('returns empty array when no functions registered', function () {
            expect($this->handler->getSupportedFunctions())->toBeEmpty();
        });

        it('returns array with registered function names', function () {
            $callback = fn() => 'test';
            $this->handler->addCustomFunction('testFunc', $callback);

            expect($this->handler->getSupportedFunctions())->toEqual(['testFunc']);
        });

        it('returns all registered function names', function () {
            $callback1 = fn() => 'result1';
            $callback2 = fn() => 'result2';

            $this->handler->addCustomFunction('funcA', $callback1);
            $this->handler->addCustomFunction('funcB', $callback2);

            $functions = $this->handler->getSupportedFunctions();
            expect($functions)->toContain('funcA')
                ->and($functions)->toContain('funcB')
                ->and($functions)->toHaveLength(2);
        });
    });

    describe('getModuleNamespace method', function () {
        it('returns CUSTOM namespace', function () {
            expect($this->handler->getModuleNamespace())->toEqual(SassModule::CUSTOM);
        });
    });

    describe('getGlobalFunctions method', function () {
        it('returns same as getSupportedFunctions when no functions registered', function () {
            expect($this->handler->getGlobalFunctions())->toBeEmpty()
                ->and($this->handler->getGlobalFunctions())->toEqual($this->handler->getSupportedFunctions());
        });

        it('returns registered function names', function () {
            $callback = fn() => 'test';
            $this->handler->addCustomFunction('globalFunc', $callback);

            expect($this->handler->getGlobalFunctions())->toEqual(['globalFunc']);
        });
    });

    describe('addCustomFunction method', function () {
        it('adds function to internal registry', function () {
            $callback = fn() => 'added';
            $this->handler->addCustomFunction('newFunc', $callback);

            expect($this->handler->canHandle('newFunc'))->toBeTrue();
        });

        it('allows overriding existing function', function () {
            $callback1 = fn() => 'first';
            $callback2 = fn() => 'second';

            $this->handler->addCustomFunction('overrideFunc', $callback1);
            $result1 = $this->handler->handle('overrideFunc', []);

            $this->handler->addCustomFunction('overrideFunc', $callback2);
            $result2 = $this->handler->handle('overrideFunc', []);

            expect($result1)->toBe('first')
                ->and($result2)->toBe('second');
        });
    });

    describe('setCustomFunctions method', function () {
        it('sets multiple functions at once', function () {
            $functions = [
                'func1' => fn() => 'result1',
                'func2' => fn() => 'result2',
            ];

            $this->handler->setCustomFunctions($functions);

            expect($this->handler->canHandle('func1'))->toBeTrue()
                ->and($this->handler->canHandle('func2'))->toBeTrue()
                ->and($this->handler->handle('func1', []))->toBe('result1')
                ->and($this->handler->handle('func2', []))->toBe('result2');
        });

        it('replaces existing functions', function () {
            // First add some functions
            $this->handler->addCustomFunction('oldFunc', fn() => 'old');

            // Then set new functions
            $this->handler->setCustomFunctions([
                'newFunc' => fn() => 'new',
            ]);

            expect($this->handler->canHandle('oldFunc'))->toBeFalse()
                ->and($this->handler->canHandle('newFunc'))->toBeTrue();
        });

        it('handles empty array correctly', function () {
            $this->handler->addCustomFunction('tempFunc', fn() => 'temp');
            $this->handler->setCustomFunctions([]);

            expect($this->handler->getSupportedFunctions())->toBeEmpty();
        });
    });

    describe('private helper methods', function () {
        describe('extractMetadata method', function () {
            it('extracts unit information from arguments', function () {
                $args = [
                    ['value' => 10, 'unit' => 'px'],
                    ['value' => 20],
                    30,
                ];

                $metadata = $this->accessor->callMethod('extractMetadata', [$args]);

                expect($metadata)->toBeArray()
                    ->and($metadata[0]['unit'])->toBe('px')
                    ->and($metadata[1]['unit'])->toBeNull()
                    ->and($metadata[2]['unit'])->toBeNull();
            });

            it('handles empty arguments array', function () {
                $metadata = $this->accessor->callMethod('extractMetadata', [[]]);
                expect($metadata)->toBeArray()->toBeEmpty();
            });
        });

        describe('extractScalarValue method', function () {
            it('extracts value from array argument', function () {
                $result = $this->accessor->callMethod('extractScalarValue', [['value' => 42]]);
                expect($result)->toBe(42.0);
            });

            it('extracts string value from array argument', function () {
                $result = $this->accessor->callMethod('extractScalarValue', [['value' => 'test']]);
                expect($result)->toBe('test');
            });

            it('returns non-array arguments unchanged', function () {
                expect($this->accessor->callMethod('extractScalarValue', ['string']))->toBe('string')
                    ->and($this->accessor->callMethod('extractScalarValue', [123]))->toBe(123)
                    ->and($this->accessor->callMethod('extractScalarValue', [true]))->toBeTrue()
                    ->and($this->accessor->callMethod('extractScalarValue', [null]))->toBeNull();
            });

            it('handles array without value key', function () {
                $result = $this->accessor->callMethod('extractScalarValue', [['unit' => 'px']]);
                expect($result)->toBeArray()->toHaveKey('unit');
            });
        });
    });

    describe('integration with ModuleRegistry', function () {
        it('registers with registry when setRegistry is called', function () {
            $registry = mock(ModuleRegistry::class);
            $registry->shouldReceive('register')->with($this->handler)->once();

            $this->handler->setRegistry($registry);
            $this->handler->addCustomFunction('test', fn() => 'result');
        });

        it('works without registry being set', function () {
            // Should not throw exception when registry is not set
            expect(fn() => $this->handler->addCustomFunction('test', fn() => 'result'))
                ->not->toThrow(Exception::class)
                ->and($this->handler->canHandle('test'))->toBeTrue();
        });
    });
});
