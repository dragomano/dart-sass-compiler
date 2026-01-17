<?php

declare(strict_types=1);

use DartSass\Evaluators\UserFunctionEvaluator;
use DartSass\Exceptions\CompilationException;
use DartSass\Handlers\Builtins\CustomFunctionHandler;
use DartSass\Handlers\Builtins\ModuleHandlerInterface;
use DartSass\Handlers\FunctionHandler;
use DartSass\Handlers\FunctionRouter;
use DartSass\Handlers\ModuleHandler;
use DartSass\Handlers\ModuleRegistry;
use DartSass\Handlers\VariableHandler;
use DartSass\Utils\ResultFormatterInterface;
use Tests\ReflectionAccessor;

beforeEach(function () {
    $this->moduleHandler         = mock(ModuleHandler::class);
    $this->moduleRegistry        = mock(ModuleRegistry::class);
    $this->resultFormatter       = mock(ResultFormatterInterface::class);
    $this->router                = new FunctionRouter($this->moduleRegistry, $this->resultFormatter);
    $this->customFunctionHandler = mock(CustomFunctionHandler::class);
    $this->userFunctionEvaluator = new UserFunctionEvaluator();
    $this->evaluateExpression    = fn($expr) => $expr; // Simple mock for callable
    $this->functionHandler       = new FunctionHandler(
        $this->moduleHandler,
        $this->router,
        $this->customFunctionHandler,
        $this->userFunctionEvaluator,
        $this->evaluateExpression
    );
    $this->accessor = new ReflectionAccessor($this->functionHandler);
});

describe('FunctionHandler', function () {
    describe('constructor', function () {
        it('creates instance with dependencies', function () {
            expect($this->functionHandler)->toBeInstanceOf(FunctionHandler::class);
        });
    });

    describe('call method', function () {
        it('routes function call through router', function () {
            $handler = mock(ModuleHandlerInterface::class);
            $handler->shouldReceive('handle')
                ->with('abs', [10])
                ->andReturn(10);
            $handler->shouldReceive('requiresRawResult')
                ->with('abs')
                ->andReturn(false);

            $this->moduleRegistry->shouldReceive('getHandler')
                ->with('abs')
                ->andReturn($handler);

            $this->resultFormatter->shouldReceive('format')
                ->with(10)
                ->andReturn('10');

            $result = $this->functionHandler->call('abs', [10]);

            expect($result)->toBe('10');
        });

        it('handles function with namespace and loads module', function () {
            $this->moduleHandler->shouldReceive('isModuleLoaded')
                ->with('sass:color')
                ->andReturn(false);

            $this->moduleHandler->shouldReceive('loadModule')
                ->with('sass:color', 'color')
                ->once();

            $handler = mock(ModuleHandlerInterface::class);
            $handler->shouldReceive('handle')
                ->with('red', ['#ff0000'])
                ->andReturn('#ff0000');
            $handler->shouldReceive('requiresRawResult')
                ->with('red')
                ->andReturn(false);

            $this->moduleRegistry->shouldReceive('getHandler')
                ->with('color.red')
                ->andReturn($handler);

            $this->resultFormatter->shouldReceive('format')
                ->with('#ff0000')
                ->andReturn('#ff0000');

            $result = $this->functionHandler->call('color.red', ['#ff0000']);

            expect($result)->toBe('#ff0000');
        });

        it('does not load module if already loaded', function () {
            $this->moduleHandler->shouldReceive('isModuleLoaded')
                ->with('sass:color')
                ->andReturn(true);

            $this->moduleHandler->shouldNotReceive('loadModule');

            $handler = mock(ModuleHandlerInterface::class);
            $handler->shouldReceive('handle')
                ->with('red', ['#ff0000'])
                ->andReturn('#ff0000');
            $handler->shouldReceive('requiresRawResult')
                ->with('red')
                ->andReturn(false);

            $this->moduleRegistry->shouldReceive('getHandler')
                ->with('color.red')
                ->andReturn($handler);

            $this->resultFormatter->shouldReceive('format')
                ->with('#ff0000')
                ->andReturn('#ff0000');

            $result = $this->functionHandler->call('color.red', ['#ff0000']);

            expect($result)->toBe('#ff0000');
        });

        it('handles unknown namespace', function () {
            $this->moduleHandler->shouldReceive('isModuleLoaded')
                ->with('unknown')
                ->andReturn(false);

            $this->moduleHandler->shouldReceive('loadModule')
                ->with('unknown', 'unknown')
                ->once();

            $this->moduleRegistry->shouldReceive('getHandler')
                ->with('unknown.func')
                ->andReturn(null);

            $this->moduleRegistry->shouldReceive('getHandler')
                ->with('func')
                ->andReturn(null);

            $this->resultFormatter->shouldReceive('format')
                ->with(1)
                ->andReturn('1');

            $result = $this->functionHandler->call('unknown.func', [1]);

            expect($result)->toBe('unknown.func(1)');
        });

        it('handles single array argument flattening', function () {
            $handler = mock(ModuleHandlerInterface::class);
            $handler->shouldReceive('handle')
                ->with('rgb', [255, 0, 0])
                ->andReturn('#ff0000');
            $handler->shouldReceive('requiresRawResult')
                ->with('rgb')
                ->andReturn(false);

            $this->moduleRegistry->shouldReceive('getHandler')
                ->with('rgb')
                ->andReturn($handler);

            $this->resultFormatter->shouldReceive('format')
                ->with('#ff0000')
                ->andReturn('#ff0000');

            $result = $this->functionHandler->call('rgb', [[255, 0, 0]]);

            expect($result)->toBe('#ff0000');
        });

        it('handles user defined function', function () {
            $variableHandler = mock(VariableHandler::class);

            $this->functionHandler->defineUserFunction(
                'double',
                ['$value'],
                [(object) [
                    'type' => 'return',
                    'properties' => [
                        'value' => (object) [
                            'type' => 'operation',
                            'properties' => [
                                'left' => (object) ['type' => 'variable', 'properties' => ['name' => 'value']],
                                'operator' => '*',
                                'right' => (object) ['type' => 'number', 'properties' => ['value' => 2]],
                            ],
                        ],
                    ],
                ]],
                $variableHandler
            );

            // UserFunctionEvaluator now handles scope management
            $variableHandler->shouldReceive('enterScope')->once();
            $variableHandler->shouldReceive('define')
                ->with('$value', 5)
                ->once();
            $variableHandler->shouldReceive('exitScope')->once();

            $result = $this->functionHandler->call('double', [5]);

            expect($result)->toBe(10);
        });

        it('returns null for user function without return', function () {
            $variableHandler = mock(VariableHandler::class);

            $this->functionHandler->defineUserFunction(
                'noReturn',
                [],
                [(object) ['type' => 'other']],
                $variableHandler
            );

            $variableHandler->shouldReceive('enterScope')->once();
            $variableHandler->shouldReceive('exitScope')->once();

            $result = $this->functionHandler->call('noReturn', []);

            expect($result)->toBeNull();
        });

        it('throws exception for invalid function name', function () {
            $handler = mock(ModuleHandlerInterface::class);
            $handler->shouldReceive('handle')
                ->with('invalid', [])
                ->andThrow(new CompilationException('Invalid function'));
            $handler->shouldReceive('requiresRawResult')
                ->with('invalid')
                ->andReturn(false);

            $this->moduleRegistry->shouldReceive('getHandler')
                ->with('invalid')
                ->andReturn($handler);

            expect(fn() => $this->functionHandler->call('invalid', []))
                ->toThrow(CompilationException::class, 'Invalid function');
        });
    });

    describe('addCustom method', function () {
        it('adds custom function to handler', function () {
            $callback = fn() => 'custom';

            $this->customFunctionHandler->shouldReceive('addCustomFunction')
                ->with('myFunc', $callback)
                ->once();

            $this->functionHandler->addCustom('myFunc', $callback);
        });
    });

    describe('user function management', function () {
        it('defines and retrieves user functions', function () {
            $variableHandler = mock(VariableHandler::class);

            $this->customFunctionHandler->shouldReceive('getSupportedFunctions')
                ->andReturn([]);

            $this->functionHandler->defineUserFunction(
                'testFunc',
                ['$param'],
                [['type' => 'return', 'properties' => ['value' => '$param']]],
                $variableHandler
            );

            $functions = $this->functionHandler->getUserFunctions();

            expect($functions)->toHaveKey('userDefinedFunctions')
                ->and($functions['userDefinedFunctions'])->toHaveKey('testFunc');
        });

        it('sets user functions state', function () {
            $state = [
                'customFunctions' => ['func1' => 'callback1'],
                'userDefinedFunctions' => ['func2' => ['args' => [], 'body' => [], 'handler' => null]],
            ];

            $this->customFunctionHandler->shouldReceive('setCustomFunctions')
                ->with(['func1' => 'callback1'])
                ->once();

            $this->functionHandler->setUserFunctions($state);

            // Verify internal state
            $reflectedUserFunctions = $this->accessor->getProperty('userDefinedFunctions');
            expect($reflectedUserFunctions)->toEqual(['func2' => ['args' => [], 'body' => [], 'handler' => null]]);
        });
    });
})->covers(FunctionHandler::class);
