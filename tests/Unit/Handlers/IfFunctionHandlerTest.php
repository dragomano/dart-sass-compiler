<?php

declare(strict_types=1);

use DartSass\Handlers\ModuleHandlers\IfFunctionHandler;
use DartSass\Handlers\SassModule;
use Tests\ReflectionAccessor;

beforeEach(function () {
    $this->evaluateExpression = fn($expr) => $expr;

    $this->handler  = new IfFunctionHandler($this->evaluateExpression);
    $this->accessor = new ReflectionAccessor($this->handler);
});

describe('IfFunctionHandler', function () {
    describe('canHandle method', function () {
        it('returns true for if function', function () {
            expect($this->handler->canHandle('if'))->toBeTrue();
        });

        it('returns false for other functions', function () {
            expect($this->handler->canHandle('else'))->toBeFalse()
                ->and($this->handler->canHandle('elseif'))->toBeFalse()
                ->and($this->handler->canHandle('ternary'))->toBeFalse();
        });
    });

    describe('handle method with indexed arguments', function () {
        it('returns true branch when condition is truthy', function () {
            $evaluateFn = fn($expr) => $expr === 'true-condition';
            $handler    = new IfFunctionHandler($evaluateFn);

            $result = $handler->handle('if', ['true-condition', 'true-value', 'false-value']);
            expect($result)->toBe('true-value');
        });

        it('returns false branch when condition is falsy', function () {
            $evaluateFn = fn($expr) => $expr === 'true-condition';
            $handler    = new IfFunctionHandler($evaluateFn);

            $result = $handler->handle('if', ['false-condition', 'true-value', 'false-value']);
            expect($result)->toBe('false-value');
        });

        it('handles numeric truthy conditions', function () {
            $evaluateFn = fn($expr) => $expr;
            $handler    = new IfFunctionHandler($evaluateFn);

            $result = $handler->handle('if', [42, 'positive', 'zero-or-negative']);
            expect($result)->toBe('positive');
        });

        it('handles zero as falsy condition', function () {
            $evaluateFn = fn($expr) => ! ($expr === 0);
            $handler    = new IfFunctionHandler($evaluateFn);

            $result = $handler->handle('if', [0, 'positive', 'zero']);
            expect($result)->toBe('zero');
        });

        it('handles string "null" as falsy condition', function () {
            $evaluateFn = fn($expr) => $expr;
            $handler    = new IfFunctionHandler($evaluateFn);

            $result = $handler->handle('if', ['null', 'not-null', 'is-null']);
            expect($result)->toBe('is-null');
        });

        it('handles empty string as truthy condition', function () {
            $evaluateFn = fn($expr) => $expr;
            $handler    = new IfFunctionHandler($evaluateFn);

            $result = $handler->handle('if', ['', 'empty', 'not-empty']);
            expect($result)->toBe('empty');
        });

        it('returns null when insufficient arguments provided', function () {
            $result = $this->handler->handle('if', ['condition', 'true-value']);
            expect($result)->toBeNull();

            $result = $this->handler->handle('if', ['condition']);
            expect($result)->toBeNull();

            $result = $this->handler->handle('if', []);
            expect($result)->toBeNull();
        });

        it('handles complex data types in branches', function () {
            $evaluateFn = fn($expr) => $expr;
            $handler    = new IfFunctionHandler($evaluateFn);

            $complexTrue  = ['nested' => 'value', 'array' => [1, 2, 3]];
            $complexFalse = (object) ['property' => 'value'];

            $result = $handler->handle('if', [true, $complexTrue, $complexFalse]);
            expect($result)->toEqual($complexTrue);
        });
    });

    describe('handle method with associative arguments', function () {
        it('returns true branch with named parameters', function () {
            $evaluateFn = fn($expr) => $expr === 'truthy' ? 'truthy' : false;
            $handler    = new IfFunctionHandler($evaluateFn);

            $result = $handler->handle('if', [
                'condition' => 'truthy',
                'then'      => 'success',
                'else'      => 'failure',
            ]);
            expect($result)->toBe('success');
        });

        it('returns false branch with named parameters', function () {
            $evaluateFn = fn($expr) => $expr;
            $handler    = new IfFunctionHandler($evaluateFn);

            $result = $handler->handle('if', [
                'condition' => false,
                'then'      => 'success',
                'else'      => 'failure',
            ]);
            expect($result)->toBe('failure');
        });

        it('returns null when named parameters are incomplete', function () {
            $result = $this->handler->handle('if', [
                'condition' => 'test',
                'then'      => 'value',
                // missing 'else'
            ]);
            expect($result)->toBeNull();

            $result = $this->handler->handle('if', [
                'condition' => 'test',
                // missing 'then' and 'else'
            ]);
            expect($result)->toBeNull();
        });
    });

    describe('requiresRawResult method', function () {
        it('returns true for if function', function () {
            expect($this->handler->requiresRawResult('if'))->toBeTrue();
        });
    });

    describe('getModuleNamespace method', function () {
        it('returns CSS namespace', function () {
            expect($this->handler->getModuleNamespace())->toEqual(SassModule::CSS);
        });
    });

    describe('getSupportedFunctions method', function () {
        it('returns array with if function', function () {
            expect($this->handler->getSupportedFunctions())->toEqual(['if']);
        });
    });

    describe('getModuleFunctions method', function () {
        it('returns empty array for IfFunctionHandler', function () {
            expect($this->handler->getModuleFunctions())->toEqual([]);
        });
    });

    describe('getGlobalFunctions method', function () {
        it('returns array with if function', function () {
            expect($this->handler->getGlobalFunctions())->toEqual(['if']);
        });
    });

    describe('private isTruthy method', function () {
        it('returns false for null', function () {
            $result = $this->accessor->callMethod('isTruthy', [null]);
            expect($result)->toBeFalse();
        });

        it('returns false for false boolean', function () {
            $result = $this->accessor->callMethod('isTruthy', [false]);
            expect($result)->toBeFalse();
        });

        it('returns false for string "null"', function () {
            $result = $this->accessor->callMethod('isTruthy', ['null']);
            expect($result)->toBeFalse();

            $result = $this->accessor->callMethod('isTruthy', ['NULL']);
            expect($result)->toBeFalse();

            $result = $this->accessor->callMethod('isTruthy', ['Null']);
            expect($result)->toBeFalse();
        });

        it('returns true for truthy values', function () {
            expect($this->accessor->callMethod('isTruthy', [true]))->toBeTrue()
                ->and($this->accessor->callMethod('isTruthy', [1]))->toBeTrue()
                ->and($this->accessor->callMethod('isTruthy', [0.1]))->toBeTrue()
                ->and($this->accessor->callMethod('isTruthy', ['string']))->toBeTrue()
                ->and($this->accessor->callMethod('isTruthy', ['false']))->toBeTrue()
                ->and($this->accessor->callMethod('isTruthy', ['0']))->toBeTrue()
                ->and($this->accessor->callMethod('isTruthy', [['array']]))->toBeTrue()
                ->and($this->accessor->callMethod('isTruthy', [(object) ['prop' => 'value']]))->toBeTrue();
        });

        it('returns true for empty string and other edge cases', function () {
            expect($this->accessor->callMethod('isTruthy', ['']))->toBeTrue()
                ->and($this->accessor->callMethod('isTruthy', [0]))->toBeTrue()
                ->and($this->accessor->callMethod('isTruthy', ['0.0']))->toBeTrue();
        });
    });

    describe('integration scenarios', function () {
        it('handles nested if expressions', function () {
            $evaluateFn = fn($expr) => match($expr) {
                'outer-condition' => true,
                'inner-condition' => false,
                default           => $expr,
            };
            $handler = new IfFunctionHandler($evaluateFn);

            $result = $handler->handle('if', [
                'outer-condition',
                ['if', 'inner-condition', 'inner-true', 'inner-false'],
                'outer-false',
            ]);

            // Should return the inner if expression for further evaluation
            expect($result)->toBeArray()
                ->and($result[0])->toBe('if')
                ->and($result[1])->toBe('inner-condition')
                ->and($result[2])->toBe('inner-true')
                ->and($result[3])->toBe('inner-false');
        });

        it('preserves original argument types', function () {
            $evaluateFn = fn($expr) => $expr;
            $handler    = new IfFunctionHandler($evaluateFn);

            $originalArgs = [1, ['complex' => 'structure'], 42];
            $result = $handler->handle('if', $originalArgs);
            expect($result)->toEqual(['complex' => 'structure']);
        });
    });
});
