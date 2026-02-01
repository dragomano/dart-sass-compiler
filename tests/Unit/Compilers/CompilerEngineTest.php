<?php

declare(strict_types=1);

use DartSass\Compilers\CompilerContext;
use DartSass\Compilers\CompilerEngine;
use DartSass\Exceptions\CompilationException;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\NodeType;
use DartSass\Utils\LoggerInterface;

describe('CompilerEngine', function () {
    beforeEach(function () {
        $this->context = mock(CompilerContext::class);
        $this->logger  = mock(LoggerInterface::class);
        $this->engine  = new CompilerEngine($this->context, $this->logger);
    });

    describe('findNodeCompiler', function () {
        it('returns null when no suitable compiler is found', function () {
            expect($this->engine->findNodeCompiler(NodeType::UNKNOWN))->toBeNull();
        });
    });

    it('throws CompilationException for unknown AST node type', function () {
        $unknownNode = mock(AstNode::class);
        $unknownNode->type = NodeType::UNKNOWN;

        expect(fn() => $this->engine->compileAst([$unknownNode]))
            ->toThrow(CompilationException::class, 'Unknown AST node type: unknown');
    });
})->covers(CompilerEngine::class);
