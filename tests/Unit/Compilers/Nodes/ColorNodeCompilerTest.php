<?php

declare(strict_types=1);

use DartSass\Compilers\CompilerContext;
use DartSass\Compilers\Nodes\ColorNodeCompiler;
use DartSass\Parsers\Nodes\ColorNode;

describe('ColorNodeCompiler', function () {
    beforeEach(function () {
        $this->compiler = new ColorNodeCompiler();
    });

    it('compiles color node to string', function () {
        $node = mock(ColorNode::class);
        $node->shouldReceive('__toString')->andReturn('#ff0000');

        $context = mock(CompilerContext::class);

        $result = $this->compiler->compile($node, $context);

        expect($result)->toBe('#ff0000');
    });

    it('can compile color node type', function () {
        expect($this->compiler->canCompile('color'))->toBeTrue();
    });

    it('cannot compile other node types', function () {
        expect($this->compiler->canCompile('rule'))->toBeFalse();
    });
});
