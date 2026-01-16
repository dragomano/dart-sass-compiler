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

    it('throws InvalidArgumentException for unknown rule type', function () {
        $compiler = new RuleCompiler();
        $context  = mock(CompilerContext::class);

        $unknownNode = mock(AstNode::class);
        $unknownNode->type = 'unknown';

        expect(fn() => $compiler->compileRule($unknownNode, $context, 0, ''))
            ->toThrow(InvalidArgumentException::class, 'Unknown rule type: unknown');
    });
});
