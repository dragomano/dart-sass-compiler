<?php

declare(strict_types=1);

use DartSass\Compilers\Nodes\ErrorNodeCompiler;
use DartSass\Exceptions\CompilationException;
use DartSass\Parsers\Nodes\ErrorNode;
use DartSass\Utils\LoggerInterface;

describe('ErrorNodeCompiler', function () {
    beforeEach(function () {
        $this->logger   = mock(LoggerInterface::class);
        $this->compiler = new ErrorNodeCompiler(
            $this->logger,
            fn(mixed $expr): mixed => $expr,
            fn(mixed $value): string => "\"$value\"",
            fn(): array => ['sourceFile' => 'test.scss']
        );
    });

    it('compiles error node and throws CompilationException', function () {
        $node = new ErrorNode('error message', 20);
        $this->logger->shouldReceive('error')
            ->with('"error message"', ['file' => 'test.scss', 'line' => 20])
            ->once();

        expect(fn() => $this->compiler->compile($node))
            ->toThrow(CompilationException::class, 'Error at test.scss:20: "error message"');
    });

    it('uses default source file when not provided', function () {
        $node = new ErrorNode('error message', 25);
        $this->compiler = new ErrorNodeCompiler(
            $this->logger,
            fn(mixed $expr): mixed => $expr,
            fn(mixed $value): string => "\"$value\"",
            fn(): array => []
        );

        $this->logger->shouldReceive('error')
            ->with('"error message"', ['file' => 'unknown', 'line' => 25])
            ->once();

        expect(fn() => $this->compiler->compile($node))
            ->toThrow(CompilationException::class, 'Error at unknown:25: "error message"');
    });

    it('uses default line number when not provided', function () {
        $node = new ErrorNode('error message');
        $this->logger->shouldReceive('error')
            ->with('"error message"', ['file' => 'test.scss', 'line' => 0])
            ->once();

        expect(fn() => $this->compiler->compile($node))
            ->toThrow(CompilationException::class, 'Error at test.scss:0: "error message"');
    });
});
