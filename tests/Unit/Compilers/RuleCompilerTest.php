<?php

declare(strict_types=1);

use DartSass\Compilers\CompilerContext;
use DartSass\Compilers\RuleCompiler;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\NodeType;
use Tests\ReflectionAccessor;

describe('RuleCompiler', function () {
    beforeEach(function () {
        $this->compiler = new RuleCompiler();
        $this->accessor = new ReflectionAccessor($this->compiler);
    });

    it('returns null from findStrategy for unknown rule type', function () {
        $result = $this->accessor->callMethod('findStrategy', [NodeType::UNKNOWN]);

        expect($result)->toBeNull();
    });

    it('throws InvalidArgumentException for unknown rule type', function () {
        $context     = mock(CompilerContext::class);
        $unknownNode = mock(AstNode::class);
        $unknownNode->type = NodeType::UNKNOWN;

        expect(fn() => $this->compiler->compileRule($unknownNode, $context, 0, ''))
            ->toThrow(InvalidArgumentException::class, 'Unknown rule type: unknown');
    });
});
