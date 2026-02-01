<?php

declare(strict_types=1);

use DartSass\Compilers\CompilerContext;
use DartSass\Compilers\CompilerEngineInterface;
use DartSass\Compilers\Nodes\WarnNodeCompiler;
use DartSass\Parsers\Nodes\NodeType;
use DartSass\Parsers\Nodes\WarnNode;
use DartSass\Utils\LoggerInterface;
use DartSass\Utils\ResultFormatterInterface;

describe('WarnNodeCompiler', function () {
    beforeEach(function () {
        $this->logger   = mock(LoggerInterface::class);
        $this->compiler = new WarnNodeCompiler($this->logger);
    });

    it('compiles warn node and returns empty string', function () {
        $node = new WarnNode('warning message', 15);
        $context = mock(CompilerContext::class)->makePartial();
        $context->engine = mock(CompilerEngineInterface::class);
        $context->engine->shouldReceive('evaluateExpression')->with('warning message')->andReturn('warning message');
        $context->resultFormatter = mock(ResultFormatterInterface::class);
        $context->resultFormatter->shouldReceive('format')->with('warning message')->andReturn('"warning message"');
        $context->options = ['sourceFile' => 'test.scss'];

        $this->logger->shouldReceive('debug')
            ->with('"warning message"', ['file' => 'test.scss', 'line' => 15])
            ->once();

        $result = $this->compiler->compile($node, $context);

        expect($result)->toBe('');
    });

    it('can compile warn node type', function () {
        expect($this->compiler->canCompile(NodeType::WARN))->toBeTrue();
    });

    it('cannot compile other node types', function () {
        expect($this->compiler->canCompile(NodeType::DEBUG))->toBeFalse()
            ->and($this->compiler->canCompile(NodeType::ERROR))->toBeFalse()
            ->and($this->compiler->canCompile(NodeType::RULE))->toBeFalse();
    });
});
