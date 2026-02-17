<?php

declare(strict_types=1);

use DartSass\Compilers\Nodes\WarnNodeCompiler;
use DartSass\Parsers\Nodes\WarnNode;
use DartSass\Utils\LoggerInterface;

describe('WarnNodeCompiler', function () {
    beforeEach(function () {
        $this->logger = mock(LoggerInterface::class);

        $this->compiler = new WarnNodeCompiler(
            $this->logger,
            fn(mixed $expr): mixed => $expr,
            fn(mixed $value): string => "\"$value\"",
            fn(): array => ['sourceFile' => 'test.scss']
        );
    });

    it('compiles warn node and returns empty string', function () {
        $node = new WarnNode('warning message', 15);
        $this->logger->shouldReceive('debug')
            ->with('"warning message"', ['file' => 'test.scss', 'line' => 15])
            ->once();

        $result = $this->compiler->compile($node);

        expect($result)->toBe('');
    });

});
