<?php

declare(strict_types=1);

use DartSass\Compilers\CompilerEngine;
use DartSass\Compilers\Environment;
use DartSass\Compilers\NodeCompilerRegistry;
use DartSass\Exceptions\CompilationException;
use DartSass\Handlers\ExtendHandler;
use DartSass\Handlers\FunctionHandler;
use DartSass\Handlers\MixinHandler;
use DartSass\Handlers\ModuleHandler;
use DartSass\Handlers\VariableHandler;
use DartSass\Loaders\LoaderInterface;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\NodeType;
use DartSass\Parsers\ParserFactory;
use DartSass\Utils\PositionTracker;
use Tests\ReflectionAccessor;

describe('CompilerEngine', function () {
    beforeEach(function () {
        $this->mixinHandler = mock(MixinHandler::class);

        $this->nodeCompilerRegistry = mock(NodeCompilerRegistry::class);
        $this->nodeCompilerRegistry->shouldReceive('find')->andReturnNull();

        $this->engine = new CompilerEngine(
            [],
            mock(LoaderInterface::class),
            mock(ParserFactory::class),
            new Environment(),
            mock(PositionTracker::class),
            mock(ExtendHandler::class),
            $this->nodeCompilerRegistry,
            mock(ModuleHandler::class),
            $this->mixinHandler,
            mock(VariableHandler::class),
            mock(FunctionHandler::class)
        );
    });

    it('throws CompilationException for unknown AST node type', function () {
        $unknownNode = mock(AstNode::class);
        $unknownNode->type = NodeType::UNKNOWN;
        $accessor = new ReflectionAccessor($this->engine);

        expect(fn() => $accessor->callMethod('compileAst', [[$unknownNode]]))
            ->toThrow(CompilationException::class, 'Unknown AST node type: unknown');
    });

})->covers(CompilerEngine::class);
