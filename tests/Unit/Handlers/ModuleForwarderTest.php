<?php

declare(strict_types=1);

use DartSass\Handlers\ModuleForwarder;
use DartSass\Handlers\ModuleLoader;
use DartSass\Loaders\LoaderInterface;
use DartSass\Parsers\ParserFactory;
use Tests\ReflectionAccessor;

describe('ModuleForwarder', function () {
    beforeEach(function () {
        $this->loader        = mock(LoaderInterface::class);
        $this->parserFactory = mock(ParserFactory::class);
        $this->moduleLoader  = mock(ModuleLoader::class, [$this->loader, $this->parserFactory]);
        $this->forwarder     = new ModuleForwarder($this->moduleLoader);
        $this->accessor      = new ReflectionAccessor($this->forwarder);
    });

    describe('processAst method', function () {
        it('calls onVariable for variable nodes', function () {
            $variableNode = (object) ['type' => 'variable'];
            $ast = [$variableNode];

            $called = false;
            $this->forwarder->processAst(
                $ast,
                onVariable: function ($node) use (&$called, $variableNode) {
                    $called = true;
                    expect($node)->toBe($variableNode);
                }
            );

            expect($called)->toBeTrue();
        });

        it('calls onMixin for mixin nodes', function () {
            $mixinNode = (object) ['type' => 'mixin'];
            $ast = [$mixinNode];

            $called = false;
            $this->forwarder->processAst(
                $ast,
                onMixin: function ($node) use (&$called, $mixinNode) {
                    $called = true;
                    expect($node)->toBe($mixinNode);
                }
            );

            expect($called)->toBeTrue();
        });

        it('calls onFunction for function nodes', function () {
            $functionNode = (object) ['type' => 'function'];
            $ast = [$functionNode];

            $called = false;
            $this->forwarder->processAst(
                $ast,
                onFunction: function ($node) use (&$called, $functionNode) {
                    $called = true;
                    expect($node)->toBe($functionNode);
                }
            );

            expect($called)->toBeTrue();
        });

        it('calls onCssNode for other nodes', function () {
            $cssNode = (object) ['type' => 'rule'];
            $ast = [$cssNode];

            $called = false;
            $this->forwarder->processAst(
                $ast,
                onCssNode: function ($node) use (&$called, $cssNode) {
                    $called = true;
                    expect($node)->toBe($cssNode);
                }
            );

            expect($called)->toBeTrue();
        });
    });

    describe('isAllowed method', function () {
        it('returns true when no hide or show', function () {
            $result = $this->accessor->callMethod('isAllowed', ['$var', [], []]);

            expect($result)->toBeTrue();
        });

        it('returns false when name in hide', function () {
            $result = $this->accessor->callMethod('isAllowed', ['$var', ['$var'], []]);

            expect($result)->toBeFalse();
        });

        it('returns false when show exists and name not in show', function () {
            $result = $this->accessor->callMethod('isAllowed', ['$var', [], ['$other']]);

            expect($result)->toBeFalse();
        });

        it('returns true when name in show', function () {
            $result = $this->accessor->callMethod('isAllowed', ['$var', [], ['$var']]);

            expect($result)->toBeTrue();
        });
    });

    describe('forwardCallable method', function () {
        it('forwards callable when allowed', function () {
            $node = (object) [
                'properties' => [
                    'name' => 'testMixin',
                    'args' => ['$param'],
                    'body' => [],
                ],
            ];

            $result = [];

            $this->accessor->callMethod('forwardCallable', [$node, 'mixins', &$result, [], []]);

            expect($result)->toHaveKey('mixins')
                ->and($result['mixins'])->toHaveKey('testMixin')
                ->and($result['mixins']['testMixin'])->toHaveKey('args')
                ->and($result['mixins']['testMixin'])->toHaveKey('body');
        });

        it('does not forward when hidden', function () {
            $node = (object) [
                'properties' => [
                    'name' => 'testMixin',
                    'args' => [],
                    'body' => [],
                ],
            ];

            $result = [];

            $this->accessor->callMethod('forwardCallable', [$node, 'mixins', &$result, ['testMixin'], []]);

            expect($result)->toBe([]);
        });
    });

    describe('forwardModule method', function () {
        it('forwards variables from ast', function () {
            $variableNode = (object) [
                'type'       => 'variable',
                'properties' => [
                    'name'  => '$testVar',
                    'value' => (object) ['type' => 'number', 'properties' => ['value' => 42]],
                ],
            ];

            $this->moduleLoader->shouldReceive('loadAst')->andReturn([$variableNode]);

            $evaluateExpression = fn($expr) => 42;

            $result = $this->forwarder->forwardModule('test.scss', $evaluateExpression);

            expect($result)->toHaveKey('variables')
                ->and($result['variables'])->toHaveKey('$testVar');
        });

        it('respects config for variables', function () {
            $variableNode = (object) [
                'type'       => 'variable',
                'properties' => [
                    'name'  => '$testVar',
                    'value' => (object) ['type' => 'number', 'properties' => ['value' => 42]],
                ],
            ];

            $this->moduleLoader->shouldReceive('loadAst')->andReturn([$variableNode]);

            $evaluateExpression = fn($expr) => 42;

            $result = $this->forwarder->forwardModule('test.scss', $evaluateExpression, ['testVar' => 'configured']);

            expect($result['variables']['$testVar'])->toBe('configured');
        });
    });
})->covers(ModuleForwarder::class);
