<?php

declare(strict_types=1);

use DartSass\Compilers\CompilerContext;
use DartSass\Compilers\ModuleCompiler;
use DartSass\Handlers\MixinHandler;
use DartSass\Handlers\ModuleHandler;
use DartSass\Handlers\VariableHandler;

beforeEach(function () {
    $this->moduleHandler   = mock(ModuleHandler::class);
    $this->variableHandler = mock(VariableHandler::class);
    $this->mixinHandler    = mock(MixinHandler::class);
    $this->context         = mock(CompilerContext::class);

    $this->context->moduleHandler   = $this->moduleHandler;
    $this->context->variableHandler = $this->variableHandler;
    $this->context->mixinHandler    = $this->mixinHandler;

    $this->moduleCompiler = new ModuleCompiler($this->context);
});

it('registers module mixins', function () {
    $namespace    = 'testNamespace';
    $propertyName = 'mixinName';
    $propertyData = [
        'type' => 'mixin',
        'args' => ['arg1', 'arg2'],
        'body' => ['some', 'body'],
    ];

    $this->moduleHandler
        ->shouldReceive('getVariables')
        ->once()
        ->with($namespace)
        ->andReturn([$propertyName => $propertyData]);

    $this->mixinHandler
        ->shouldReceive('define')
        ->once()
        ->with($namespace . '.' . $propertyName, $propertyData['args'], ['some', 'body']);

    $this->moduleCompiler->registerModuleMixins($namespace);
});
