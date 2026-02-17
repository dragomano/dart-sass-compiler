<?php

declare(strict_types=1);

use DartSass\Compilers\Environment;
use DartSass\Exceptions\CompilationException;
use DartSass\Handlers\MixinHandler;
use DartSass\Handlers\VariableHandler;
use DartSass\Parsers\Nodes\IdentifierNode;
use DartSass\Values\SassList;
use Tests\ReflectionAccessor;

beforeEach(function () {
    $this->environment     = new Environment();
    $this->compilerEngine  = mock();
    $this->variableHandler = mock(VariableHandler::class);
    $this->mixinHandler = new MixinHandler($this->environment, $this->variableHandler);
    $this->mixinHandler->setCompilerCallbacks(
        fn(array $declarations, string $parentSelector = '', int $nestingLevel = 0): string
            => $this->compilerEngine->compileDeclarations($declarations, $parentSelector, $nestingLevel),
        fn(array $ast, string $parentSelector = '', int $nestingLevel = 0): string
            => $this->compilerEngine->compileAst($ast, $parentSelector, $nestingLevel),
        function (string $content, string $selector, int $nestingLevel): string {
            $indent  = str_repeat('  ', $nestingLevel);
            $content = rtrim($content, "\n");

            return "$indent$selector {\n$content\n$indent}\n";
        }
    );
});

describe('MixinHandler', function () {
    describe('define method', function () {
        it('defines a mixin', function () {
            $this->mixinHandler->define('testMixin', ['$param'], [['property' => 'color', 'value' => 'red']]);

            expect($this->mixinHandler->hasMixin('testMixin'))->toBeTrue();
        });
    });

    describe('include method', function () {
        it('throws exception for undefined mixin', function () {
            expect(fn() => $this->mixinHandler->include('undefinedMixin', []))
                ->toThrow(CompilationException::class, 'Undefined mixin: undefinedMixin');
        });

        it('includes mixin without content', function () {
            $this->mixinHandler->define('simpleMixin', [], [['property' => 'color', 'value' => 'blue']]);

            $this->compilerEngine->shouldReceive('compileDeclarations')->andReturn('color: blue;');
            $this->variableHandler->shouldReceive('define')->never();

            $result = $this->mixinHandler->include('simpleMixin', []);

            expect($result)->toContain('color: blue;');
        });

        it('includes mixin with arguments', function () {
            $this->mixinHandler->define(
                'paramMixin',
                ['$color' => null],
                [['property' => 'color', 'value' => '$color']]
            );

            $this->compilerEngine->shouldReceive('compileDeclarations')->andReturn('color: red;');
            $this->variableHandler->shouldReceive('define')->with('$color', 'red');

            $result = $this->mixinHandler->include('paramMixin', ['red']);

            expect($result)->toContain('color: red;');
        });

        it('includes mixin with named arguments', function () {
            $this->mixinHandler->define(
                'namedMixin',
                ['$color' => null],
                [['property' => 'color', 'value' => '$color']]
            );

            $this->compilerEngine->shouldReceive('compileDeclarations')->andReturn('color: blue;');
            $this->variableHandler->shouldReceive('define')->with('$color', 'blue');

            $result = $this->mixinHandler->include('namedMixin', ['$color' => 'blue']);

            expect($result)->toContain('color: blue;');
        });

        it('handles default arguments', function () {
            $identifierNode = new IdentifierNode('blue', 0);
            $this->mixinHandler->define(
                'defaultMixin',
                ['$color' => $identifierNode],
                [['property' => 'color', 'value' => '$color']]
            );

            $this->compilerEngine->shouldReceive('compileDeclarations')->andReturn('color: blue;');
            $this->variableHandler->shouldReceive('define')->with('$color', $identifierNode);

            $result = $this->mixinHandler->include('defaultMixin', []);

            expect($result)->toContain('color: blue;');
        });

        it('handles non-identifier default arguments', function () {
            $this->mixinHandler->define(
                'nonIdMixin',
                ['$color' => 'blue'],
                [['property' => 'color', 'value' => '$color']]
            );

            $this->compilerEngine->shouldReceive('compileDeclarations')->andReturn('color: blue;');
            $this->variableHandler->shouldReceive('define')->with('$color', 'blue');

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
                ->shouldReceive('compileDeclarations')
                ->andReturn('color: green; font-size: 14px;');

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
                ->shouldReceive('compileDeclarations')
                ->andReturn('color: green;@content', 'font-size: 14px;');

            $result = $this->mixinHandler->include('contentMixinNoParent', [], $content);

            expect($result)->toContain('font-size: 14px');
        });

        it('caches mixin results', function () {
            $this->mixinHandler->define('cachedMixin', [], [['property' => 'margin', 'value' => '0']]);

            $this->compilerEngine->shouldReceive('compileDeclarations')->andReturn('margin: 0;');

            $result1 = $this->mixinHandler->include('cachedMixin', []);
            $result2 = $this->mixinHandler->include('cachedMixin', []);

            expect($result1)->toBe($result2);
        });

        it('cleans up cache when limit exceeded', function () {
            $this->accessor = new ReflectionAccessor($this->mixinHandler);
            $this->accessor->setProperty('mixinCache', []);

            $this->mixinHandler->define(
                'cacheTestMixin',
                ['$arg' => null],
                [['property' => 'color', 'value' => '$arg']]
            );

            $this->compilerEngine
                ->shouldReceive('compileDeclarations')
                ->andReturn('color: test;')
                ->atLeast();
            $this->variableHandler->shouldReceive('define')->atLeast();

            for ($i = 0; $i < 101; $i++) {
                $this->mixinHandler->include('cacheTestMixin', [(string) $i]);
            }

            $cache = $this->accessor->getProperty('mixinCache');
            expect(count($cache))->toBe(100);

            $firstKey = $this->accessor->callMethod('generateCacheKey', ['cacheTestMixin', ['0'], null]);
            expect($cache)->not->toHaveKey($firstKey);

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
                ->shouldReceive('compileDeclarations')
                ->andReturn('width: 10px; height: 20px;');

            $this->variableHandler->shouldReceive('define')->with('$a', '10px');
            $this->variableHandler->shouldReceive('define')->with('$b', '20px');

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

            $args = [['10px', '20px']];

            $this->compilerEngine
                ->shouldReceive('compileDeclarations')
                ->andReturn('width: 10px; height: 20px;');

            $this->variableHandler->shouldReceive('define')->with('$a', '10px');
            $this->variableHandler->shouldReceive('define')->with('$b', '20px');

            $result = $this->mixinHandler->include('arrayArgMixin', $args);

            expect($result)->toContain('width: 10px')
                ->and($result)->toContain('height: 20px');
        });

        it('handles spread arguments with used keys', function () {
            $this->mixinHandler->define(
                'spreadMixin',
                ['$a' => null, '$rest...' => null],
                [['property' => 'content', 'value' => '"$a and $rest"']]
            );

            $this->compilerEngine
                ->shouldReceive('compileDeclarations')
                ->andReturn('content: "val1 and val2,val3";');

            $this->variableHandler->shouldReceive('define');

            $result = $this->mixinHandler->include('spreadMixin', ['val1', 'val2', 'val3']);

            expect($result)->toContain('content: "val1 and val2,val3";');
        });

        it('creates fallback compiler when no parent compiler provided', function () {
            $this->mixinHandler->define('fallbackMixin', [], [['color' => 'red']]);

            $this->compilerEngine->shouldReceive('compileDeclarations')->andReturn('color: red;');

            $result = $this->mixinHandler->include('fallbackMixin', []);

            expect($result)->toContain('color: red;');
        });

        it('throws exception when engine is not set', function () {
            $handlerWithoutEngine = new MixinHandler($this->environment, $this->variableHandler);
            $handlerWithoutEngine->define('noEngineMixin', [], [['property' => 'color', 'value' => 'red']]);

            expect(fn() => $handlerWithoutEngine->include('noEngineMixin', []))
                ->toThrow(LogicException::class, 'MixinHandler compiler callbacks are not set.');
        });
    });

    describe('removeMixin method', function () {
        it('defines mixin in scope when scopes are not empty', function () {
            $this->environment->enterScope();

            $this->mixinHandler->define('scopedMixin', [], [['property' => 'color', 'value' => 'green']]);

            $this->compilerEngine->shouldReceive('compileDeclarations')->andReturn('color: green;');

            $result = $this->mixinHandler->include('scopedMixin', []);
            expect($result)->toContain('color: green;');
        });
    });

    describe('enterScope and exitScope methods', function () {
        it('enters and exits scope', function () {
            $this->environment->enterScope();

            $this->mixinHandler->define('scopedMixin', [], [['property' => 'color', 'value' => 'yellow']]);

            $this->compilerEngine->shouldReceive('compileDeclarations')->andReturn('color: green;');

            $result = $this->mixinHandler->include('scopedMixin', []);
            expect($result)->toContain('color: green;');

            $this->environment->exitScope();

            expect(fn() => $this->mixinHandler->include('scopedMixin', []))
                ->toThrow(CompilationException::class);
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

    describe('compiler callbacks guards', function () {
        it('throws when compileAst callback is not set', function () {
            $handlerWithoutCallbacks = new MixinHandler($this->environment, $this->variableHandler);
            $accessor = new ReflectionAccessor($handlerWithoutCallbacks);

            expect(fn() => $accessor->callMethod('compileAst', [[[]], '', 0]))
                ->toThrow(LogicException::class, 'MixinHandler compiler callbacks are not set.');
        });

        it('throws when formatRule callback is not set', function () {
            $handlerWithoutCallbacks = new MixinHandler($this->environment, $this->variableHandler);
            $accessor = new ReflectionAccessor($handlerWithoutCallbacks);

            expect(fn() => $accessor->callMethod('formatRule', ['', '.selector', 0]))
                ->toThrow(LogicException::class, 'MixinHandler compiler callbacks are not set.');
        });
    });
})->covers(MixinHandler::class);
