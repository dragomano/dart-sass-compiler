<?php

declare(strict_types=1);

use DartSass\Compilers\CompilerContext;
use DartSass\Compilers\CompilerEngine;
use DartSass\Exceptions\CompilationException;
use DartSass\Parsers\Nodes\AstNode;

describe('CompilerEngine', function () {
    beforeEach(function () {
        $this->context = mock(CompilerContext::class);
        $this->context->engine = null; // Will be set by constructor
        $this->engine = new CompilerEngine($this->context);
    });

    describe('Unknown AST Node Type Coverage', function () {
        it('throws CompilationException for unknown AST node type', function () {
            $unknownNode = new AstNode('unknown_type', []);

            expect(fn() => $this->engine->compileAst([$unknownNode]))
                ->toThrow(CompilationException::class, 'Unknown AST node type: unknown_type');
        });
    });
});
