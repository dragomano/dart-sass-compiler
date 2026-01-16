<?php

declare(strict_types=1);

use DartSass\Compilers\CompilerContext;
use DartSass\Compilers\CompilerEngineInterface;
use DartSass\Exceptions\CompilationException;
use DartSass\Handlers\MixinHandler;
use DartSass\Handlers\VariableHandler;
use DartSass\Modules\SassList;
use DartSass\Parsers\Nodes\IdentifierNode;
use Tests\ReflectionAccessor;

beforeEach(function () {
    $this->mixinHandler    = new MixinHandler();
    $this->compilerEngine  = mock(CompilerEngineInterface::class);
    $this->context         = mock(CompilerContext::class);
    $this->variableHandler = mock(VariableHandler::class);

    $this->context->variableHandler = $this->variableHandler;
    $this->mixinHandler->setCompilerEngine($this->compilerEngine);
});

describe('MixinHandler', function () {
    describe('define method', function () {
        it('defines a mixin', function () {
            $this->mixinHandler->define('testMixin', ['$param'], [['property' => 'color', 'value' => 'red']]);

            $mixins = $this->mixinHandler->getMixins();
            expect($mixins['mixins'])->toHaveKey('testMixin')
                ->and($mixins['mixins']['testMixin']['args'])->toBe(['$param']);
        });
    });

    describe('include method', function () {
        it('throws exception for undefined mixin', function () {
            expect(fn() => $this->mixinHandler->include('undefinedMixin', []))
                ->toThrow(CompilationException::class, 'Undefined mixin: undefinedMixin');
        });

        it('includes mixin without content', function () {
            $this->mixinHandler->define('simpleMixin', [], [['property' => 'color', 'value' => 'blue']]);

            $this->compilerEngine->shouldReceive('getContext')->andReturn($this->context);
            $this->compilerEngine->shouldReceive('compileDeclarations')->andReturn('color: blue;');
            $this->variableHandler->shouldReceive('enterScope')->once();
            $this->variableHandler->shouldReceive('exitScope')->once();

            $result = $this->mixinHandler->include('simpleMixin', []);

            expect($result)->toContain('color: blue;');
        });

        it('includes mixin with arguments', function () {
            $this->mixinHandler->define(
                'paramMixin',
                ['$color' => null],
                [['property' => 'color', 'value' => '$color']]
            );

            $this->compilerEngine->shouldReceive('getContext')->andReturn($this->context);
            $this->compilerEngine->shouldReceive('compileDeclarations')->andReturn('color: red;');
            $this->variableHandler->shouldReceive('enterScope')->once();
            $this->variableHandler->shouldReceive('define')->with('$color', 'red')->once();
            $this->variableHandler->shouldReceive('exitScope')->once();

            $result = $this->mixinHandler->include('paramMixin', ['red']);

            expect($result)->toContain('color: red;');
        });

        it('handles default arguments', function () {
            $identifierNode = new IdentifierNode('blue', 0);
            $this->mixinHandler->define(
                'defaultMixin',
                ['$color' => $identifierNode],
                [['property' => 'color', 'value' => '$color']]
            );

            $this->compilerEngine->shouldReceive('getContext')->andReturn($this->context);
            $this->compilerEngine->shouldReceive('compileDeclarations')->andReturn('color: blue;');
            $this->variableHandler->shouldReceive('enterScope')->once();
            $this->variableHandler->shouldReceive('define')->with('$color', $identifierNode)->once();
            $this->variableHandler->shouldReceive('exitScope')->once();

            $result = $this->mixinHandler->include('defaultMixin', []);

            expect($result)->toContain('color: blue;');
        });

        it('handles non-identifier default arguments', function () {
            $this->mixinHandler->define(
                'nonIdMixin',
                ['$color' => 'blue'],
                [['property' => 'color', 'value' => '$color']]
            );

            $this->compilerEngine->shouldReceive('getContext')->andReturn($this->context);
            $this->compilerEngine->shouldReceive('compileDeclarations')->andReturn('color: blue;');
            $this->variableHandler->shouldReceive('enterScope')->once();
            $this->variableHandler->shouldReceive('define')->with('$color', 'blue')->once();
            $this->variableHandler->shouldReceive('exitScope')->once();

            $result = $this->mixinHandler->include('nonIdMixin', []);

            expect($result)->toContain('color: blue;');
        });

        it('includes mixin with content', function () {
            $this->mixinHandler->define(
                'contentMixin',
                [],
                [['property' => 'color', 'value' => 'green'], '@content']
            );

            $content = [['property' => 'font-size', 'value' => '14px']];

            $this->compilerEngine
                ->shouldReceive('getContext')
                ->andReturn($this->context);
            $this->compilerEngine
                ->shouldReceive('compileDeclarations')
                ->andReturn('color: green; font-size: 14px;');
            $this->compilerEngine->shouldReceive('formatRule')->andReturn('  font-size: 14px;');
            $this->variableHandler->shouldReceive('enterScope')->once();
            $this->variableHandler->shouldReceive('exitScope')->once();

            $result = $this->mixinHandler->include('contentMixin', [], $content, '.parent');

            expect($result)->toContain('color: green')
                ->and($result)->toContain('font-size: 14px');
        });

        it('includes mixin with content without parent selector', function () {
            $this->mixinHandler->define(
                'contentMixinNoParent',
                [],
                [['property' => 'color', 'value' => 'green'], '@content']
            );

            $content = [['property' => 'font-size', 'value' => '14px']];

            $this->compilerEngine
                ->shouldReceive('getContext')
                ->andReturn($this->context);
            $this->compilerEngine
                ->shouldReceive('compileDeclarations')
                ->andReturn('color: green;@content', 'font-size: 14px;');
            $this->variableHandler->shouldReceive('enterScope')->once();
            $this->variableHandler->shouldReceive('exitScope')->once();

            $result = $this->mixinHandler->include('contentMixinNoParent', [], $content);

            expect($result)->toContain('font-size: 14px');
        });

        it('caches mixin results', function () {
            $this->mixinHandler->define('cachedMixin', [], [['property' => 'margin', 'value' => '0']]);

            $this->compilerEngine->shouldReceive('getContext')->andReturn($this->context);
            $this->compilerEngine->shouldReceive('compileDeclarations')->andReturn('margin: 0;');
            $this->variableHandler->shouldReceive('enterScope')->once();
            $this->variableHandler->shouldReceive('exitScope')->once();

            // First call
            $result1 = $this->mixinHandler->include('cachedMixin', []);
            // Second call should use cache
            $result2 = $this->mixinHandler->include('cachedMixin', []);

            expect($result1)->toBe($result2);
        });

        it('cleans up cache when limit exceeded', function () {
            // Clear the static cache
            $this->accessor = new ReflectionAccessor($this->mixinHandler);
            $this->accessor->setProperty('mixinCache', []);

            // Define a mixin
            $this->mixinHandler->define(
                'cacheTestMixin',
                ['$arg' => null],
                [['property' => 'color', 'value' => '$arg']]
            );

            // Mock methods for include calls
            $this->compilerEngine->shouldReceive('getContext')->andReturn($this->context)->atLeast(1);
            $this->compilerEngine->shouldReceive('compileDeclarations')->andReturn('color: test;')->atLeast(1);
            $this->variableHandler->shouldReceive('enterScope')->atLeast(1);
            $this->variableHandler->shouldReceive('define')->atLeast(1);
            $this->variableHandler->shouldReceive('exitScope')->atLeast(1);

            // Fill cache beyond limit (101 entries)
            for ($i = 0; $i < 101; $i++) {
                $this->mixinHandler->include('cacheTestMixin', [(string) $i]);
            }

            // Check cache size
            $cache = $this->accessor->getProperty('mixinCache');
            expect(count($cache))->toBe(100);

            // Verify first entry was removed
            $firstKey = $this->accessor->callMethod('generateCacheKey', ['cacheTestMixin', ['0'], null]);
            expect($cache)->not->toHaveKey($firstKey);

            // Verify last entry is present
            $lastKey = $this->accessor->callMethod('generateCacheKey', ['cacheTestMixin', ['100'], null]);
            expect($cache)->toHaveKey($lastKey);
        });

        it('handles SassList arguments', function () {
            $this->mixinHandler->define(
                'listMixin',
                ['$a' => null, '$b' => null],
                [['property' => 'width', 'value' => '$a'], ['property' => 'height', 'value' => '$b']]
            );

            $sassList = mock(SassList::class, [['10px', '20px']]);

            $this->compilerEngine
                ->shouldReceive('getContext')
                ->andReturn($this->context);
            $this->compilerEngine
                ->shouldReceive('compileDeclarations')
                ->andReturn('width: 10px; height: 20px;');
            $this->variableHandler->shouldReceive('enterScope')->once();
            $this->variableHandler->shouldReceive('define')->with('$a', '10px')->once();
            $this->variableHandler->shouldReceive('define')->with('$b', '20px')->once();
            $this->variableHandler->shouldReceive('exitScope')->once();

            $result = $this->mixinHandler->include('listMixin', [$sassList]);

            expect($result)->toContain('width: 10px')
                ->and($result)->toContain('height: 20px');
        });

        it('handles array argument without value key', function () {
            $this->mixinHandler->define(
                'arrayArgMixin',
                ['$a' => null, '$b' => null],
                [['property' => 'width', 'value' => '$a'], ['property' => 'height', 'value' => '$b']]
            );

            $args = [['10px', '20px']]; // array without 'value' key

            $this->compilerEngine
                ->shouldReceive('getContext')
                ->andReturn($this->context);
            $this->compilerEngine
                ->shouldReceive('compileDeclarations')
                ->andReturn('width: 10px; height: 20px;');
            $this->variableHandler->shouldReceive('enterScope')->once();
            $this->variableHandler->shouldReceive('define')->with('$a', '10px')->once();
            $this->variableHandler->shouldReceive('define')->with('$b', '20px')->once();
            $this->variableHandler->shouldReceive('exitScope')->once();

            $result = $this->mixinHandler->include('arrayArgMixin', $args);

            expect($result)->toContain('width: 10px')
                ->and($result)->toContain('height: 20px');
        });

        it('creates fallback compiler when no parent compiler provided', function () {
            $this->mixinHandler->define('fallbackMixin', [], [['color' => 'red']]);

            $this->compilerEngine->shouldReceive('getContext')->andReturn($this->context);
            $this->compilerEngine->shouldReceive('compileDeclarations')->andReturn('color: red;');
            $this->variableHandler->shouldReceive('enterScope')->once();
            $this->variableHandler->shouldReceive('exitScope')->once();

            $result = $this->mixinHandler->include('fallbackMixin', []);

            expect($result)->toContain('color: red;');
        });
    });

    describe('getMixins and setMixins methods', function () {
        it('gets and sets mixin state', function () {
            $this->mixinHandler->define('testMixin', [], []);

            $state = $this->mixinHandler->getMixins();
            expect($state)->toHaveKey('mixins');

            $this->mixinHandler->setMixins($state);
            $newState = $this->mixinHandler->getMixins();
            expect($newState)->toEqual($state);
        });
    });

    describe('removeMixin method', function () {
        it('removes a mixin', function () {
            $this->mixinHandler->define('testMixin', [], []);
            $this->mixinHandler->removeMixin('testMixin');

            $mixins = $this->mixinHandler->getMixins();
            expect($mixins['mixins'])->not->toHaveKey('testMixin');
        });
    });

    describe('generateCacheKey method', function () {
        it('generates cache key', function () {
            $this->accessor = new ReflectionAccessor($this->mixinHandler);
            $key = $this->accessor->callMethod('generateCacheKey', ['test', ['arg'], ['content']]);

            expect($key)->toBeString()
                ->and($key)->toContain('test');
        });
    });
})->covers(MixinHandler::class);
