<?php

declare(strict_types=1);

use DartSass\Compilers\CompilerContext;
use DartSass\Compilers\CompilerEngineInterface;
use DartSass\Compilers\ModuleCompiler;
use DartSass\Compilers\Nodes\UseNodeCompiler;
use DartSass\Handlers\MixinHandler;
use DartSass\Handlers\ModuleHandler;
use DartSass\Handlers\VariableHandler;
use DartSass\Parsers\Nodes\UseNode;
use Tests\ReflectionAccessor;

describe('UseNodeCompiler', function () {
    beforeEach(function () {
        $this->compiler = new UseNodeCompiler();
        $this->accessor = new ReflectionAccessor($this->compiler);
    });

    it('registers mixins in current scope when using @use without namespace', function () {
        $node = new UseNode('path/to/module', null, 0);

        $context         = mock(CompilerContext::class);
        $moduleHandler   = mock(ModuleHandler::class);
        $variableHandler = mock(VariableHandler::class);
        $mixinHandler    = mock(MixinHandler::class);
        $engine          = mock(CompilerEngineInterface::class);
        $moduleCompiler  = mock(ModuleCompiler::class);

        $context->moduleHandler   = $moduleHandler;
        $context->variableHandler = $variableHandler;
        $context->mixinHandler    = $mixinHandler;
        $context->engine          = $engine;
        $context->moduleCompiler  = $moduleCompiler;

        $moduleHandler->shouldReceive('isModuleLoaded')->with('path/to/module')
            ->andReturn(false);
        $moduleHandler->shouldReceive('loadModule')->with('path/to/module', null)
            ->andReturn(['namespace' => 'actualNamespace', 'cssAst' => []]);
        $moduleHandler->shouldReceive('getVariables')->with('actualNamespace')->andReturn([
            'mixinName' => [
                'type' => 'mixin',
                'args' => ['arg1', 'arg2'],
                'body' => ['some', 'body'],
            ],
        ]);

        $moduleCompiler->shouldReceive('registerModuleMixins')->with('actualNamespace');
        $mixinHandler->shouldReceive('define')->with('mixinName', ['arg1', 'arg2'], ['some', 'body']);
        $moduleCompiler->shouldReceive('compile')->andReturn('');

        $result = $this->accessor->callMethod('compileNode', [$node, $context]);

        expect($result)->toBe('');
    });
});
