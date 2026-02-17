<?php

declare(strict_types=1);

use DartSass\Compilers\Nodes\DebugNodeCompiler;
use DartSass\Parsers\Nodes\DebugNode;
use DartSass\Utils\LoggerInterface;

describe('DebugNodeCompiler', function () {
    beforeEach(function () {
        $this->logger   = mock(LoggerInterface::class);
        $this->compiler = new DebugNodeCompiler(
            $this->logger,
            fn(mixed $expr): mixed => $expr,
            fn(mixed $value): string => "\"$value\"",
            fn(): array => ['sourceFile' => 'test.scss']
        );
    });

    it('compiles debug node and returns empty string', function () {
        $node = new DebugNode('test message', 10);
        $this->logger->shouldReceive('debug')
            ->with('"test message"', ['file' => 'test.scss', 'line' => 10])
            ->once();

        $result = $this->compiler->compile($node);

        expect($result)->toBe('');
    });

});
