<?php

declare(strict_types=1);

use DartSass\Exceptions\CompilationException;
use DartSass\Handlers\BuiltInModuleProvider;
use DartSass\Handlers\ModuleForwarder;
use DartSass\Handlers\ModuleHandler;
use DartSass\Handlers\ModuleLoader;
use DartSass\Loaders\LoaderInterface;
use DartSass\Parsers\Nodes\StringNode;
use DartSass\Parsers\Nodes\VariableDeclarationNode;
use DartSass\Parsers\ParserFactory;
use Tests\ReflectionAccessor;

beforeEach(function () {
    $this->loaderInterface = mock(LoaderInterface::class);
    $this->parserFactory   = mock(ParserFactory::class);
    $this->moduleLoader    = mock(ModuleLoader::class, [$this->loaderInterface, $this->parserFactory]);
    $this->forwarder       = mock(ModuleForwarder::class, [$this->moduleLoader])->makePartial();
    $this->provider        = mock(BuiltInModuleProvider::class);
    $this->handler         = new ModuleHandler($this->moduleLoader, $this->forwarder, $this->provider);
    $this->accessor        = new ReflectionAccessor($this->handler);
});

describe('ModuleHandler', function () {
    describe('loadModule method', function () {
        it('returns cached result for already loaded module', function () {
            $this->moduleLoader->shouldReceive('loadAst')->never();

            // Simulate already loaded
            $loadedModules = $this->accessor->getProperty('loadedModules');
            $loadedModules['test.scss'] = ['namespace' => 'test', 'cssAst' => []];
            $this->handler->setLoadedModules(['loadedModules' => $loadedModules]);

            $result = $this->handler->loadModule('test.scss');

            expect($result)->toEqual(['cssAst' => [], 'namespace' => 'test']);
        });

        it('loads sass: module and registers properties', function () {
            $this->provider->shouldReceive('provideProperties')->with('sass:math')->andReturn(['$pi' => 3.14]);

            $result = $this->handler->loadModule('sass:math', 'math');

            expect($result)->toEqual(['cssAst' => [], 'namespace' => 'math']);
        });

        it('loads regular module and processes ast', function () {
            $ast = [
                (object) ['type' => 'variable', 'properties' => ['name' => '$var', 'value' => 'val']],
                (object) ['type' => 'mixin', 'properties' => ['name' => 'testMixin', 'args' => [], 'body' => []]],
                (object) ['type' => 'function', 'properties' => ['name' => 'testFunc', 'args' => [], 'body' => []]],
            ];
            $this->moduleLoader->shouldReceive('loadAst')->andReturn($ast);

            $result = $this->handler->loadModule('test.scss', 'test');

            expect($result)->toHaveKey('cssAst')
                ->and($result)->toHaveKey('namespace')
                ->and($result['namespace'])->toBe('test');
        });

        it('handles global variables for namespace *', function () {
            $ast = [(object) ['type' => 'variable', 'properties' => ['name' => '$global', 'value' => 'val']]];
            $this->moduleLoader->shouldReceive('loadAst')->andReturn($ast);

            $this->handler->loadModule('test.scss', '*');

            $globals = $this->handler->getGlobalVariables();
            expect($globals)->toHaveKey('$global');
        });

        it('loads module with css nodes', function () {
            $cssNode = (object) ['type' => 'rule', 'properties' => ['selector' => '.test']];
            $ast     = [$cssNode];
            $this->moduleLoader->shouldReceive('loadAst')->andReturn($ast);

            $result = $this->handler->loadModule('test.scss', 'test');

            expect($result['cssAst'])->toContain($cssNode);
        });
    });

    describe('forwardModule method', function () {
        it('returns empty when already loaded', function () {
            // Simulate loaded
            $loadedModules = $this->accessor->getProperty('loadedModules');
            $loadedModules['test.scss'] = ['namespace' => 'test', 'cssAst' => []];
            $this->handler->setLoadedModules(['loadedModules' => $loadedModules]);

            $result = $this->handler->forwardModule('test.scss', fn() => null);

            expect($result)->toBe([]);
        });

        it('forwards and stores properties', function () {
            $this->forwarder->shouldReceive('forwardModule')->andReturn([
                'variables' => ['$var' => 'value'],
                'mixins'    => ['mixin' => ['args' => [], 'body' => []]],
                'functions' => ['func' => ['args' => [], 'body' => []]],
            ]);

            $result = $this->handler->forwardModule('test.scss', fn() => null, 'test');

            expect($result)->toHaveKey('variables')
                ->and($this->handler->getVariables('test'))->toHaveKey('$var');
        });
    });

    describe('getProperty method', function () {
        it('returns property directly', function () {
            // Simulate property
            $properties = $this->accessor->getProperty('forwardedProperties');
            $properties['test']['$prop'] = 'value';
            $this->handler->setLoadedModules(['forwardedProperties' => $properties]);

            $result = $this->handler->getProperty('test', '$prop');

            expect($result)->toBe('value');
        });

        it('evaluates VariableDeclarationNode', function () {
            $node = new VariableDeclarationNode('$prop', new StringNode('expr', 1), 1);

            // Simulate property
            $properties = $this->accessor->getProperty('forwardedProperties');
            $properties['test']['$prop'] = $node;
            $this->handler->setLoadedModules(['forwardedProperties' => $properties]);

            $evaluate = fn($expr) => 'evaluated';

            $result = $this->handler->getProperty('test', '$prop', $evaluate);

            expect($result)->toBe('evaluated');
        });

        it('throws exception for missing property', function () {
            expect(fn() => $this->handler->getProperty('test', '$missing'))
                ->toThrow(
                    CompilationException::class,
                    'Property $missing not found in module test'
                );
        });
    });

    describe('getLoadedModules and setLoadedModules methods', function () {
        it('gets and sets state', function () {
            $state = [
                'loadedModules'       => ['mod' => ['namespace' => 'ns', 'cssAst' => []]],
                'forwardedProperties' => ['ns' => ['$var' => 'val']],
                'globalVariables'     => ['$global' => 'val'],
            ];

            $this->handler->setLoadedModules($state);
            $result = $this->handler->getLoadedModules();

            expect($result)->toEqual($state);
        });
    });

    describe('registerBuiltInModuleProperties method', function () {
        it('registers built-in properties', function () {
            $this->provider->shouldReceive('provideProperties')->with('sass:math')->andReturn(['$pi' => 3.14]);
            $this->accessor->callMethod('registerBuiltInModuleProperties', ['sass:math']);

            $properties = $this->accessor->getProperty('forwardedProperties');
            expect($properties['math']['$pi'])->toBe(3.14);
        });
    });
});
