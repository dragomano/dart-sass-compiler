<?php

declare(strict_types=1);

use DartSass\Compilers\MixinCompiler;
use DartSass\Exceptions\CompilationException;
use DartSass\Handlers\MixinHandler;
use DartSass\Handlers\ModuleHandler;
use DartSass\Parsers\Nodes\IdentifierNode;
use DartSass\Parsers\Nodes\IncludeNode;
use DartSass\Values\SassList;
use DartSass\Values\SassNumber;
use Tests\ReflectionAccessor;

beforeEach(function () {
    $this->mixinHandler  = mock(MixinHandler::class);
    $this->moduleHandler = mock(ModuleHandler::class);

    $this->mixinCompiler = new MixinCompiler($this->mixinHandler, $this->moduleHandler);
    $this->accessor      = new ReflectionAccessor($this->mixinCompiler);
});

describe('compile()', function () {
    it('compiles a simple mixin include', function () {
        $includeNode    = new IncludeNode('testMixin', [], null, 0);
        $parentSelector = '.parent';
        $nestingLevel   = 0;
        $expression     = fn($expr) => $expr;

        $this->mixinHandler
            ->shouldReceive('include')
            ->once()
            ->with('testMixin', [], null, $parentSelector, $nestingLevel)
            ->andReturn('.testMixin { color: red; }');

        $result = $this->mixinCompiler->compile(
            $includeNode,
            $parentSelector,
            $nestingLevel,
            $expression
        );

        expect($result)->toBe('.testMixin { color: red; }');
    });

    it('compiles a mixin include with arguments', function () {
        $includeNode    = new IncludeNode('testMixin', ['arg1', 'arg2'], null, 0);
        $parentSelector = '.parent';
        $nestingLevel   = 0;
        $expression     = fn($expr) => 'evaluated_' . $expr;

        $this->mixinHandler
            ->shouldReceive('include')
            ->once()
            ->with('testMixin', ['evaluated_arg1', 'evaluated_arg2'], null, $parentSelector, $nestingLevel)
            ->andReturn('.testMixin { color: blue; }');

        $result = $this->mixinCompiler->compile(
            $includeNode,
            $parentSelector,
            $nestingLevel,
            $expression
        );

        expect($result)->toBe('.testMixin { color: blue; }');
    });

    it('compiles a mixin include with content', function () {
        $contentNode    = new IdentifierNode('content', 0);
        $includeNode    = new IncludeNode('testMixin', [], [$contentNode], 0);
        $parentSelector = '.parent';
        $nestingLevel   = 0;
        $expression     = fn($expr) => $expr;

        $this->mixinHandler
            ->shouldReceive('include')
            ->once()
            ->with('testMixin', [], [$contentNode], $parentSelector, $nestingLevel)
            ->andReturn('.testMixin { @content; }');

        $result = $this->mixinCompiler->compile(
            $includeNode,
            $parentSelector,
            $nestingLevel,
            $expression
        );

        expect($result)->toBe('.testMixin { @content; }');
    });

    it('compiles a module mixin include with dot in name', function () {
        $includeNode    = new IncludeNode('module.mixin', [], null, 0);
        $parentSelector = '.parent';
        $nestingLevel   = 0;
        $expression     = fn($expr) => $expr;

        $mixinData = [
            'type' => 'mixin',
            'args' => [],
            'body' => [],
        ];

        $this->moduleHandler
            ->shouldReceive('getProperty')
            ->once()
            ->with('module', 'mixin', $expression)
            ->andReturn($mixinData);

        $this->mixinHandler
            ->shouldReceive('define')
            ->once()
            ->with(Mockery::pattern('/^temp_\w+$/'), [], []);

        $this->mixinHandler
            ->shouldReceive('include')
            ->once()
            ->with(Mockery::pattern('/^temp_\w+$/'), [], null, $parentSelector, $nestingLevel)
            ->andReturn('.module-mixin { color: green; }');

        $this->mixinHandler
            ->shouldReceive('removeMixin')
            ->once()
            ->with(Mockery::pattern('/^temp_\w+$/'));

        $result = $this->mixinCompiler->compile(
            $includeNode,
            $parentSelector,
            $nestingLevel,
            $expression
        );

        expect($result)->toBe('.module-mixin { color: green; }');
    });

    it('searches for module mixin without dot when local search throws exception', function () {
        $includeNode    = new IncludeNode('mixinWithoutDot', [], null, 0);
        $parentSelector = '.parent';
        $nestingLevel   = 0;
        $expression     = fn($expr) => $expr;

        $mixinData = [
            'type' => 'mixin',
            'args' => [],
            'body' => [],
        ];

        // Local search throws exception
        $this->mixinHandler
            ->shouldReceive('include')
            ->once()
            ->with('mixinWithoutDot', [], null, $parentSelector, $nestingLevel)
            ->andThrow(new CompilationException('Mixin not found locally'));

        // Get loaded modules
        $this->moduleHandler
            ->shouldReceive('getLoadedModules')
            ->once()
            ->andReturn(['loadedModules' => [
                'module1' => ['namespace' => 'module1'],
                'module2' => ['namespace' => 'module2'],
            ]]);

        // First module throws exception
        $this->moduleHandler
            ->shouldReceive('getProperty')
            ->once()
            ->with('module1', 'mixinWithoutDot', $expression)
            ->andThrow(new CompilationException('Not in module1'));

        // Second module returns mixin data
        $this->moduleHandler
            ->shouldReceive('getProperty')
            ->once()
            ->with('module2', 'mixinWithoutDot', $expression)
            ->andReturn($mixinData);

        $this->mixinHandler
            ->shouldReceive('define')
            ->once()
            ->with(Mockery::pattern('/^temp_\w+$/'), [], []);

        $this->mixinHandler
            ->shouldReceive('include')
            ->once()
            ->with(Mockery::pattern('/^temp_\w+$/'), [], null, $parentSelector, $nestingLevel)
            ->andReturn('.mixin-without-dot { color: yellow; }');

        $this->mixinHandler
            ->shouldReceive('removeMixin')
            ->once()
            ->with(Mockery::pattern('/^temp_\w+$/'));

        $result = $this->mixinCompiler->compile(
            $includeNode,
            $parentSelector,
            $nestingLevel,
            $expression
        );

        expect($result)->toBe('.mixin-without-dot { color: yellow; }');
    });

    it('throws original exception when mixin not found locally or in modules', function () {
        $includeNode    = new IncludeNode('nonExistentMixin', [], null, 0);
        $parentSelector = '.parent';
        $nestingLevel   = 0;
        $expression     = fn($expr) => $expr;

        $originalException = new CompilationException('Mixin not found locally');

        // Local search throws exception
        $this->mixinHandler
            ->shouldReceive('include')
            ->once()
            ->with('nonExistentMixin', [], null, $parentSelector, $nestingLevel)
            ->andThrow($originalException);

        // Get loaded modules
        $this->moduleHandler
            ->shouldReceive('getLoadedModules')
            ->once()
            ->andReturn(['loadedModules' => [
                'module1' => ['namespace' => 'module1'],
                'module2' => ['namespace' => 'module2'],
            ]]);

        // Both modules throw exceptions
        $this->moduleHandler
            ->shouldReceive('getProperty')
            ->once()
            ->with('module1', 'nonExistentMixin', Mockery::any())
            ->andThrow(new CompilationException('Not in module1'));

        $this->moduleHandler
            ->shouldReceive('getProperty')
            ->once()
            ->with('module2', 'nonExistentMixin', Mockery::any())
            ->andThrow(new CompilationException('Not in module2'));

        // Expect the original exception to be thrown
        expect(fn() => $this->mixinCompiler->compile(
            $includeNode,
            $parentSelector,
            $nestingLevel,
            $expression
        ))->toThrow(CompilationException::class)
            ->and(fn($exception) => $exception === $originalException);
    });

    it('throws exception when property in module is not a mixin', function () {
        $includeNode    = new IncludeNode('module.variable', [], null, 0);
        $parentSelector = '.parent';
        $nestingLevel   = 0;
        $expression     = fn($expr) => $expr;

        $this->moduleHandler
            ->shouldReceive('getProperty')
            ->once()
            ->with('module', 'variable', $expression)
            ->andReturn(['type' => 'variable', 'value' => 'some value']);

        expect(fn() => $this->mixinCompiler->compile(
            $includeNode,
            $parentSelector,
            $nestingLevel,
            $expression
        ))->toThrow(CompilationException::class)
            ->and(fn($exception) => $exception->getMessage() === 'Property variable is not a mixin in module module');
    });

    it('ensures correct sequence of define, include, removeMixin for temporary mixin in module call', function () {
        $includeNode    = new IncludeNode('module.tempMixin', [], null, 0);
        $parentSelector = '.parent';
        $nestingLevel   = 0;
        $expression     = fn($expr) => $expr;

        $mixinData = [
            'type' => 'mixin',
            'args' => [],
            'body' => [],
        ];

        $this->moduleHandler
            ->shouldReceive('getProperty')
            ->once()
            ->with('module', 'tempMixin', $expression)
            ->andReturn($mixinData);

        // Ensure the sequence: define, include, removeMixin
        $this->mixinHandler
            ->shouldReceive('define')
            ->once()
            ->with(Mockery::pattern('/^temp_\w+$/'), [], [])
            ->ordered();

        $this->mixinHandler
            ->shouldReceive('include')
            ->once()
            ->with(Mockery::pattern('/^temp_\w+$/'), [], null, $parentSelector, $nestingLevel)
            ->andReturn('.temp-mixin { color: purple; }')
            ->ordered();

        $this->mixinHandler
            ->shouldReceive('removeMixin')
            ->once()
            ->with(Mockery::pattern('/^temp_\w+$/'))
            ->ordered();

        $result = $this->mixinCompiler->compile(
            $includeNode,
            $parentSelector,
            $nestingLevel,
            $expression
        );

        expect($result)->toBe('.temp-mixin { color: purple; }');
    });

    it('passes parentSelector and nestingLevel correctly to MixinHandler::include', function () {
        $includeNode    = new IncludeNode('testMixin', [], null, 0);
        $parentSelector = '.nested .parent';
        $nestingLevel   = 2;
        $expression     = fn($expr) => $expr;

        $this->mixinHandler
            ->shouldReceive('include')
            ->once()
            ->with('testMixin', [], null, $parentSelector, $nestingLevel)
            ->andReturn('.testMixin { color: red; }');

        $result = $this->mixinCompiler->compile(
            $includeNode,
            $parentSelector,
            $nestingLevel,
            $expression
        );

        expect($result)->toBe('.testMixin { color: red; }');
    });

    it('throws exception for unknown meta mixin', function () {
        $includeNode    = new IncludeNode('meta.unknown', [], null, 0);
        $parentSelector = '.parent';
        $nestingLevel   = 0;
        $expression     = fn($expr) => $expr;

        expect(fn() => $this->mixinCompiler->compile(
            $includeNode,
            $parentSelector,
            $nestingLevel,
            $expression
        ))->toThrow(CompilationException::class)
            ->and(fn($exception) => $exception->getMessage() === 'Unknown mixin: meta.unknown');
    });

    describe('normalizeArguments()', function () {
        it('normalizes array with value and unit to SassNumber', function () {
            $args = [['value' => 10.0, 'unit' => 'px']];

            $result = $this->accessor->callMethod('normalizeArguments', [$args]);

            expect($result)->toHaveCount(1)
                ->and($result[0])->toBeInstanceOf(SassNumber::class)
                ->and($result[0]->getValue())->toBe(10.0)
                ->and($result[0]->getUnit())->toBe('px');
        });

        it('leaves non-array arguments unchanged', function () {
            $args = ['stringArg', 42];

            $result = $this->accessor->callMethod('normalizeArguments', [$args]);

            expect($result)->toBe(['stringArg', 42]);
        });
    });
});

describe('handleMetaApply()', function () {
    it('throws exception when apply() has no arguments', function () {
        $includeNode = new IncludeNode('meta.apply', [], null, 0);

        expect(fn() => $this->accessor->callMethod('handleMetaApply', [$includeNode, '.parent', 0, fn($expr) => $expr]))
            ->toThrow(CompilationException::class)
            ->and(fn($exception) => $exception->getMessage() === 'apply() requires at least one argument');
    });

    it('handles string mixin in apply()', function () {
        $includeNode = new IncludeNode('meta.apply', ['mixinName'], null, 0);

        $this->mixinHandler
            ->shouldReceive('hasMixin')
            ->once()
            ->with('mixinName')
            ->andReturn(true);

        $this->mixinHandler
            ->shouldReceive('include')
            ->once()
            ->with('mixinName', [], null, '.parent', 0)
            ->andReturn('.mixin { content: "applied"; }');

        $result = $this->accessor->callMethod('handleMetaApply', [$includeNode, '.parent', 0, fn($expr) => $expr]);

        expect($result)->toBe('.mixin { content: "applied"; }');
    });

    it('handles string mixin in apply() with SassMixin', function () {
        $includeNode = new IncludeNode('meta.apply', ['mixinName'], null, 0);

        $this->mixinHandler
            ->shouldReceive('hasMixin')
            ->once()
            ->with('mixinName')
            ->andReturn(false);

        $this->mixinHandler
            ->shouldReceive('include')
            ->once()
            ->with('mixinName', [], null)
            ->andReturn('.mixin { content: "applied with SassMixin"; }');

        $result = $this->accessor->callMethod('handleMetaApply', [$includeNode, '.parent', 0, fn($expr) => $expr]);

        expect($result)->toBe('.mixin { content: "applied with SassMixin"; }');
    });

    it('throws exception for invalid mixin type in apply()', function () {
        $includeNode = new IncludeNode('meta.apply', [123], null, 0);

        expect(fn() => $this->accessor->callMethod('handleMetaApply', [$includeNode, '.parent', 0, fn($expr) => $expr]))
            ->toThrow(CompilationException::class)
            ->and(fn($exception) => $exception->getMessage() === 'apply() first argument must be a SassMixin or callable');
    });

    it('handles SassList argument in apply()', function () {
        $sassList = new SassList(['mixinName', ['value' => 10.0, 'unit' => 'px']], 'space');
        $includeNode = new IncludeNode('meta.apply', [$sassList], null, 0);

        $this->mixinHandler
            ->shouldReceive('hasMixin')
            ->once()
            ->with('mixinName')
            ->andReturn(true);

        $this->mixinHandler
            ->shouldReceive('include')
            ->once()
            ->with('mixinName', [new SassNumber(10.0, 'px')], null, '.parent', 0)
            ->andReturn('.mixin { content: "applied with list"; }');

        $result = $this->accessor->callMethod('handleMetaApply', [$includeNode, '.parent', 0, fn($expr) => $expr]);

        expect($result)->toBe('.mixin { content: "applied with list"; }');
    });
});
