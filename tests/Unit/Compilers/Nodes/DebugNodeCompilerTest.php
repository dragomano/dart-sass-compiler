<?php

declare(strict_types=1);

use DartSass\Compilers\CompilerContext;
use DartSass\Compilers\CompilerEngineInterface;
use DartSass\Compilers\Nodes\DebugNodeCompiler;
use DartSass\Parsers\Nodes\DebugNode;
use DartSass\Parsers\Nodes\NodeType;
use DartSass\Utils\LoggerInterface;
use DartSass\Utils\ResultFormatterInterface;

describe('DebugNodeCompiler', function () {
    beforeEach(function () {
        $this->logger   = mock(LoggerInterface::class);
        $this->compiler = new DebugNodeCompiler($this->logger);
    });

    it('compiles debug node and returns empty string', function () {
        $node = new DebugNode('test message', 10);
        $context = mock(CompilerContext::class)->makePartial();
        $context->engine = mock(CompilerEngineInterface::class);
        $context->engine->shouldReceive('evaluateExpression')->with('test message')->andReturn('test message');
        $context->resultFormatter = mock(ResultFormatterInterface::class);
        $context->resultFormatter->shouldReceive('format')->with('test message')->andReturn('"test message"');
        $context->options = ['sourceFile' => 'test.scss'];

        $this->logger->shouldReceive('debug')
            ->with('"test message"', ['file' => 'test.scss', 'line' => 10])
            ->once();

        $result = $this->compiler->compile($node, $context);

        expect($result)->toBe('');
    });

    it('can compile debug node type', function () {
        expect($this->compiler->canCompile(NodeType::DEBUG))->toBeTrue();
    });

    it('cannot compile other node types', function () {
        expect($this->compiler->canCompile(NodeType::ERROR))->toBeFalse()
            ->and($this->compiler->canCompile(NodeType::WARN))->toBeFalse()
            ->and($this->compiler->canCompile(NodeType::RULE))->toBeFalse();
    });
});
