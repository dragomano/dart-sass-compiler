<?php

declare(strict_types=1);

use DartSass\Compilers\CompilerContext;
use DartSass\Compilers\Nodes\RuleNodeCompiler;
use DartSass\Parsers\Nodes\StringNode;
use Tests\ReflectionAccessor;

describe('RuleNodeCompiler', function () {
    beforeEach(function () {
        $this->compiler = new RuleNodeCompiler();
        $this->accessor = new ReflectionAccessor($this->compiler);
    });

    it('returns empty string when string is null in evaluateInterpolationsInString', function () {
        $context = mock(CompilerContext::class);

        $result = $this->accessor->callMethod('evaluateInterpolationsInString', [null, $context]);

        expect($result)->toBe('');
    });

    it('returns empty string when node is not instance of RuleNode', function () {
        $node    = new StringNode('test', 0);
        $context = mock(CompilerContext::class);

        $result = $this->compiler->compile($node, $context);

        expect($result)->toBe('');
    });

    it('returns empty string when CSS does not contain declarations in extractDeclarations', function () {
        $css = ".nested {\n  /* comment */\n}";

        $result = $this->accessor->callMethod('extractDeclarations', [$css, '.test', 0]);

        expect($result)->toBe('');
    });
});
