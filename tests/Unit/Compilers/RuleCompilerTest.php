<?php

declare(strict_types=1);

use DartSass\Compilers\CompilerContext;
use DartSass\Compilers\RuleCompiler;
use DartSass\Parsers\Nodes\AstNode;
use Tests\ReflectionAccessor;

describe('RuleCompiler', function () {
    it('returns null from findStrategy for unknown rule type', function () {
        $compiler = new RuleCompiler();
        $accessor = new ReflectionAccessor($compiler);

        $result = $accessor->callMethod('findStrategy', ['unknown']);

        expect($result)->toBeNull();
    });

    it('throws InvalidArgumentException with message "Unknown rule type: $ruleType" in compileRule for unknown rule type', function () {
        $compiler = new RuleCompiler();
        $node     = new AstNode('unknown', []);
        $context  = mock(CompilerContext::class);

        expect(fn() => $compiler->compileRule($node, $context, 0, ''))
            ->toThrow(InvalidArgumentException::class, 'Unknown rule type: unknown');
    });
});
