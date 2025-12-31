<?php

declare(strict_types=1);

use DartSass\Exceptions\CompilationException;
use DartSass\Handlers\ModuleHandler;
use DartSass\Loaders\LoaderInterface;
use DartSass\Parsers\ParserFactory;
use Tests\ReflectionAccessor;

beforeEach(function () {
    $this->loader        = mock(LoaderInterface::class);
    $this->parserFactory = new ParserFactory();
    $this->moduleHandler = new ModuleHandler($this->loader, $this->parserFactory);
    $this->accessor      = new ReflectionAccessor($this->moduleHandler);
});

describe('ModuleHandler', function () {
    describe('constructor', function () {
        it('creates instance with dependencies', function () {
            expect($this->moduleHandler)->toBeInstanceOf(ModuleHandler::class);
        });
    });

    describe('load method', function () {
        it('loads built-in module sass:math', function () {
            $result = $this->moduleHandler->loadModule('sass:math');

            expect($result['namespace'])->toBe('sass:math')
                ->and($result['cssAst'])->toBe([]);

            // Check that built-in properties are registered
            $variables = $this->moduleHandler->getVariables('math');
            expect($variables)->toHaveKey('$pi')
                ->and($variables)->toHaveKey('$e');
        });

        it('loads custom module', function () {
            $path    = 'test.scss';
            $content = '$var: 42;';

            $this->loader->shouldReceive('load')
                ->with($path)
                ->andReturn($content);

            $result = $this->moduleHandler->loadModule($path);

            expect($result['namespace'])->toBe('test')
                ->and($result['cssAst'])->toBe([]);

            // Check that variable is registered
            $variables = $this->moduleHandler->getVariables('test');
            expect($variables)->toHaveKey('$var');
        });

        it('handles loading errors', function () {
            $path = 'nonexistent.scss';

            $this->loader->shouldReceive('load')
                ->with($path)
                ->andThrow(new Exception('File not found'));

            expect(fn() => $this->moduleHandler->loadModule($path))
                ->toThrow(Exception::class, 'File not found');
        });
    });

    describe('has method', function () {
        it('returns true if module is loaded', function () {
            $this->moduleHandler->loadModule('sass:math');

            expect($this->moduleHandler->isModuleLoaded('sass:math'))->toBeTrue();
        });

        it('returns false if module is not loaded', function () {
            expect($this->moduleHandler->isModuleLoaded('unknown'))->toBeFalse();
        });
    });

    describe('get method', function () {
        it('returns property value if found', function () {
            $this->moduleHandler->loadModule('sass:math');

            $pi = $this->moduleHandler->getProperty('math', '$pi');

            expect($pi)->toBe(M_PI);
        });

        it('throws exception if property not found', function () {
            $this->moduleHandler->loadModule('sass:math');

            expect(fn() => $this->moduleHandler->getProperty('math', '$nonexistent'))
                ->toThrow(CompilationException::class, 'Property $nonexistent not found in module math');
        });
    });

    describe('getBuiltInModules method', function () {
        it('returns list of built-in modules', function () {
            // Since there's no actual getBuiltInModules method, we'll test the loaded state
            $this->moduleHandler->loadModule('sass:math');

            $loadedModules = $this->moduleHandler->getLoadedModules();

            expect($loadedModules['loadedModules'])->toHaveKey('sass:math')
                ->and($loadedModules['forwardedProperties'])->toHaveKey('math');
        });
    });
})->covers(ModuleHandler::class);
