<?php

declare(strict_types=1);

use DartSass\Compilers\CompilerContext;
use DartSass\Compilers\CompilerEngineInterface;
use DartSass\Compilers\MixinCompiler;
use DartSass\Exceptions\CompilationException;
use DartSass\Handlers\MixinHandler;
use DartSass\Handlers\ModuleHandler;
use DartSass\Parsers\Nodes\IdentifierNode;
use DartSass\Parsers\Nodes\IncludeNode;

beforeEach(function () {
    $this->mixinHandler  = mock(MixinHandler::class);
    $this->moduleHandler = mock(ModuleHandler::class);
    $this->context       = mock(CompilerContext::class);
    $this->compiler      = mock(CompilerEngineInterface::class);

    $this->context->mixinHandler  = $this->mixinHandler;
    $this->context->moduleHandler = $this->moduleHandler;
    $this->context->engine        = $this->compiler;

    $this->mixinCompiler = new MixinCompiler($this->context);
});

it('compiles a simple mixin include', function () {
    $includeNode        = new IncludeNode('testMixin', [], null, 0);
    $parentSelector     = '.parent';
    $nestingLevel       = 0;
    $evaluateExpression = fn($expr) => $expr;

    $this->mixinHandler
        ->shouldReceive('include')
        ->once()
        ->with('testMixin', [], null, $parentSelector, $nestingLevel)
        ->andReturn('.testMixin { color: red; }');

    $result = $this->mixinCompiler->compile(
        $includeNode,
        $parentSelector,
        $nestingLevel,
        $evaluateExpression
    );

    expect($result)->toBe('.testMixin { color: red; }');
});

it('compiles a mixin include with arguments', function () {
    $includeNode        = new IncludeNode('testMixin', ['arg1', 'arg2'], null, 0);
    $parentSelector     = '.parent';
    $nestingLevel       = 0;
    $evaluateExpression = fn($expr) => 'evaluated_' . $expr;

    $this->mixinHandler
        ->shouldReceive('include')
        ->once()
        ->with('testMixin', ['evaluated_arg1', 'evaluated_arg2'], null, $parentSelector, $nestingLevel)
        ->andReturn('.testMixin { color: blue; }');

    $result = $this->mixinCompiler->compile(
        $includeNode,
        $parentSelector,
        $nestingLevel,
        $evaluateExpression
    );

    expect($result)->toBe('.testMixin { color: blue; }');
});

it('compiles a mixin include with content', function () {
    $contentNode        = new IdentifierNode('content', 0);
    $includeNode        = new IncludeNode('testMixin', [], [$contentNode], 0);
    $parentSelector     = '.parent';
    $nestingLevel       = 0;
    $evaluateExpression = fn($expr) => $expr;

    $this->mixinHandler
        ->shouldReceive('include')
        ->once()
        ->with('testMixin', [], [$contentNode], $parentSelector, $nestingLevel)
        ->andReturn('.testMixin { @content; }');

    $result = $this->mixinCompiler->compile(
        $includeNode,
        $parentSelector,
        $nestingLevel,
        $evaluateExpression
    );

    expect($result)->toBe('.testMixin { @content; }');
});

it('compiles a module mixin include with dot in name', function () {
    $includeNode        = new IncludeNode('module.mixin', [], null, 0);
    $parentSelector     = '.parent';
    $nestingLevel       = 0;
    $evaluateExpression = fn($expr) => $expr;

    $mixinData = [
        'type' => 'mixin',
        'args' => [],
        'body' => [],
    ];

    $this->moduleHandler
        ->shouldReceive('getProperty')
        ->once()
        ->with('module', 'mixin', $evaluateExpression)
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
        $evaluateExpression
    );

    expect($result)->toBe('.module-mixin { color: green; }');
});

it('searches for module mixin without dot when local search throws exception', function () {
    $includeNode        = new IncludeNode('mixinWithoutDot', [], null, 0);
    $parentSelector     = '.parent';
    $nestingLevel       = 0;
    $evaluateExpression = fn($expr) => $expr;

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
        ->andReturn(['loadedModules' => ['module1', 'module2']]);

    // First module throws exception
    $this->moduleHandler
        ->shouldReceive('getProperty')
        ->once()
        ->with('module1', 'mixinWithoutDot', $evaluateExpression)
        ->andThrow(new CompilationException('Not in module1'));

    // Second module returns mixin data
    $this->moduleHandler
        ->shouldReceive('getProperty')
        ->once()
        ->with('module2', 'mixinWithoutDot', $evaluateExpression)
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
        $evaluateExpression
    );

    expect($result)->toBe('.mixin-without-dot { color: yellow; }');
});

it('throws original exception when mixin not found locally or in modules', function () {
    $includeNode        = new IncludeNode('nonExistentMixin', [], null, 0);
    $parentSelector     = '.parent';
    $nestingLevel       = 0;
    $evaluateExpression = fn($expr) => $expr;

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
        ->andReturn(['loadedModules' => ['module1', 'module2']]);

    // Both modules throw exceptions
    $this->moduleHandler
        ->shouldReceive('getProperty')
        ->once()
        ->with('module1', 'nonExistentMixin', $evaluateExpression)
        ->andThrow(new CompilationException('Not in module1'));

    $this->moduleHandler
        ->shouldReceive('getProperty')
        ->once()
        ->with('module2', 'nonExistentMixin', $evaluateExpression)
        ->andThrow(new CompilationException('Not in module2'));

    // Expect the original exception to be thrown
    expect(fn() => $this->mixinCompiler->compile(
        $includeNode,
        $parentSelector,
        $nestingLevel,
        $evaluateExpression
    ))->toThrow(CompilationException::class)
        ->and(fn($exception) => $exception === $originalException);
});

it('throws exception when property in module is not a mixin', function () {
    $includeNode        = new IncludeNode('module.variable', [], null, 0);
    $parentSelector     = '.parent';
    $nestingLevel       = 0;
    $evaluateExpression = fn($expr) => $expr;

    $this->moduleHandler
        ->shouldReceive('getProperty')
        ->once()
        ->with('module', 'variable', $evaluateExpression)
        ->andReturn(['type' => 'variable', 'value' => 'some value']);

    expect(fn() => $this->mixinCompiler->compile(
        $includeNode,
        $parentSelector,
        $nestingLevel,
        $evaluateExpression
    ))->toThrow(CompilationException::class)
        ->and(fn($exception) => $exception->getMessage() === 'Property variable is not a mixin in module module');
});

it('ensures correct sequence of define, include, removeMixin for temporary mixin in module call', function () {
    $includeNode        = new IncludeNode('module.tempMixin', [], null, 0);
    $parentSelector     = '.parent';
    $nestingLevel       = 0;
    $evaluateExpression = fn($expr) => $expr;

    $mixinData = [
        'type' => 'mixin',
        'args' => [],
        'body' => [],
    ];

    $this->moduleHandler
        ->shouldReceive('getProperty')
        ->once()
        ->with('module', 'tempMixin', $evaluateExpression)
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
        $evaluateExpression
    );

    expect($result)->toBe('.temp-mixin { color: purple; }');
});

it('passes parentSelector and nestingLevel correctly to MixinHandler::include', function () {
    $includeNode        = new IncludeNode('testMixin', [], null, 0);
    $parentSelector     = '.nested .parent';
    $nestingLevel       = 2;
    $evaluateExpression = fn($expr) => $expr;

    $this->mixinHandler
        ->shouldReceive('include')
        ->once()
        ->with('testMixin', [], null, $parentSelector, $nestingLevel)
        ->andReturn('.testMixin { color: red; }');

    $result = $this->mixinCompiler->compile(
        $includeNode,
        $parentSelector,
        $nestingLevel,
        $evaluateExpression
    );

    expect($result)->toBe('.testMixin { color: red; }');
});
