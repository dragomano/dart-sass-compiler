<?php

declare(strict_types=1);

use DartSass\Compilers\CompilerContext;
use DartSass\Compilers\CompilerEngineInterface;
use DartSass\Compilers\Nodes\ErrorNodeCompiler;
use DartSass\Exceptions\CompilationException;
use DartSass\Parsers\Nodes\ErrorNode;
use DartSass\Parsers\Nodes\NodeType;
use DartSass\Utils\LoggerInterface;
use DartSass\Utils\ResultFormatterInterface;

describe('ErrorNodeCompiler', function () {
    beforeEach(function () {
        $this->logger   = mock(LoggerInterface::class);
        $this->compiler = new ErrorNodeCompiler($this->logger);
    });

    it('compiles error node and throws CompilationException', function () {
        $node = new ErrorNode('error message', 20);
        $context = mock(CompilerContext::class)->makePartial();
        $context->engine = mock(CompilerEngineInterface::class);
        $context->engine->shouldReceive('evaluateExpression')->with('error message')->andReturn('error message');
        $context->resultFormatter = mock(ResultFormatterInterface::class);
        $context->resultFormatter->shouldReceive('format')->with('error message')->andReturn('"error message"');
        $context->options = ['sourceFile' => 'test.scss'];

        $this->logger->shouldReceive('error')
            ->with('"error message"', ['file' => 'test.scss', 'line' => 20])
            ->once();

        expect(fn() => $this->compiler->compile($node, $context))
            ->toThrow(CompilationException::class, 'Error at test.scss:20: "error message"');
    });

    it('can compile error node type', function () {
        expect($this->compiler->canCompile(NodeType::ERROR))->toBeTrue();
    });

    it('cannot compile other node types', function () {
        expect($this->compiler->canCompile(NodeType::DEBUG))->toBeFalse()
            ->and($this->compiler->canCompile(NodeType::WARN))->toBeFalse()
            ->and($this->compiler->canCompile(NodeType::RULE))->toBeFalse();
    });

    it('uses default source file when not provided', function () {
        $node = new ErrorNode('error message', 25);
        $context = mock(CompilerContext::class)->makePartial();
        $context->engine = mock(CompilerEngineInterface::class);
        $context->engine->shouldReceive('evaluateExpression')->andReturn('error message');
        $context->resultFormatter = mock(ResultFormatterInterface::class);
        $context->resultFormatter->shouldReceive('format')->andReturn('"error message"');
        $context->options = [];

        $this->logger->shouldReceive('error')
            ->with('"error message"', ['file' => 'unknown', 'line' => 25])
            ->once();

        expect(fn() => $this->compiler->compile($node, $context))
            ->toThrow(CompilationException::class, 'Error at unknown:25: "error message"');
    });

    it('uses default line number when not provided', function () {
        $node = new ErrorNode('error message');
        $context = mock(CompilerContext::class)->makePartial();
        $context->engine = mock(CompilerEngineInterface::class);
        $context->engine->shouldReceive('evaluateExpression')->andReturn('error message');
        $context->resultFormatter = mock(ResultFormatterInterface::class);
        $context->resultFormatter->shouldReceive('format')->andReturn('"error message"');
        $context->options = ['sourceFile' => 'test.scss'];

        $this->logger->shouldReceive('error')
            ->with('"error message"', ['file' => 'test.scss', 'line' => 0])
            ->once();

        expect(fn() => $this->compiler->compile($node, $context))
            ->toThrow(CompilationException::class, 'Error at test.scss:0: "error message"');
    });
});
