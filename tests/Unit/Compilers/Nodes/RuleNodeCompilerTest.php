<?php

declare(strict_types=1);

use DartSass\Compilers\Nodes\RuleNodeCompiler;
use DartSass\Handlers\ExtendHandler;
use DartSass\Handlers\NestingHandler;
use DartSass\Parsers\Nodes\StringNode;
use Tests\ReflectionAccessor;

describe('RuleNodeCompiler', function () {
    beforeEach(function () {
        $this->compiler = new RuleNodeCompiler(
            mock(NestingHandler::class),
            mock(ExtendHandler::class),
            fn(string $value): string => $value,
            function (): void {},
            function (): void {},
            fn(array $ast, string $parentSelector = '', int $nestingLevel = 0): string => '',
            fn(array $declarations, string $parentSelector = '', int $nestingLevel = 0): string => '',
            fn(): array => [],
            fn(): array => ['line' => 1, 'column' => 0],
            function (array $mapping): void {},
            function (string $text): void {},
            fn(mixed $value): string => (string) $value
        );
        $this->accessor = new ReflectionAccessor($this->compiler);
    });

    it('returns empty string when string is null in evaluateInterpolationsInString', function () {
        $result = $this->accessor->callMethod('evaluateInterpolationsInString', [null]);

        expect($result)->toBe('');
    });

    it('returns empty string when node is not instance of RuleNode', function () {
        $node    = new StringNode('test', 0);
        $result = $this->compiler->compile($node);

        expect($result)->toBe('');
    });

    it('returns empty string when CSS does not contain declarations in extractDeclarations', function () {
        $css = ".nested {\n  /* comment */\n}";

        $result = $this->accessor->callMethod('extractDeclarations', [$css, '.test', 0]);

        expect($result)->toBe('');
    });
});
