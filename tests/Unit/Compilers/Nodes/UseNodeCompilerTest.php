<?php

declare(strict_types=1);

use DartSass\Compilers\Nodes\UseNodeCompiler;
use DartSass\Handlers\MixinHandler;
use DartSass\Handlers\ModuleHandler;
use DartSass\Handlers\VariableHandler;
use DartSass\Parsers\Nodes\UseNode;
use Tests\ReflectionAccessor;

describe('UseNodeCompiler', function () {
    beforeEach(function () {
        $this->moduleHandler   = mock(ModuleHandler::class);
        $this->variableHandler = mock(VariableHandler::class);
        $this->mixinHandler    = mock(MixinHandler::class);

        $this->compiler = new UseNodeCompiler(
            $this->moduleHandler,
            $this->variableHandler,
            $this->mixinHandler,
            fn(mixed $expr): mixed => $expr,
            function (string $namespace): void {},
            fn(array $result, string $actualNamespace, ?string $namespace, int $nestingLevel): string => ''
        );

        $this->accessor = new ReflectionAccessor($this->compiler);
    });

    it('registers mixins in current scope when using @use without namespace', function () {
        $node = new UseNode('path/to/module', null, 0);

        $this->compiler = new UseNodeCompiler(
            $this->moduleHandler,
            $this->variableHandler,
            $this->mixinHandler,
            fn(mixed $expr): mixed => throw new RuntimeException('not expected'),
            fn(string $namespace) => expect($namespace)->toBe('actualNamespace'),
            fn(array $result, string $actualNamespace, ?string $namespace, int $nestingLevel): string => ''
        );

        $this->moduleHandler->shouldReceive('isModuleLoaded')->with('path/to/module')
            ->andReturn(false);
        $this->moduleHandler->shouldReceive('loadModule')->with('path/to/module', '')
            ->andReturn(['namespace' => 'actualNamespace', 'cssAst' => []]);
        $this->moduleHandler->shouldReceive('getVariables')->with('actualNamespace')->andReturn([
            'mixinName' => [
                'type' => 'mixin',
                'args' => ['arg1', 'arg2'],
                'body' => ['some', 'body'],
            ],
        ]);

        $this->mixinHandler->shouldReceive('define')->with('mixinName', ['arg1', 'arg2'], ['some', 'body']);
        $result = $this->accessor->callMethod('compileNode', [$node]);

        expect($result)->toBe('');
    });

});
