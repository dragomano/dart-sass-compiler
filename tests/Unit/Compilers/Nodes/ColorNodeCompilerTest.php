<?php

declare(strict_types=1);

use DartSass\Compilers\Nodes\ColorNodeCompiler;
use DartSass\Parsers\Nodes\ColorNode;

describe('ColorNodeCompiler', function () {
    beforeEach(function () {
        $this->compiler = new ColorNodeCompiler();
    });

    it('compiles color node to string', function () {
        $node = mock(ColorNode::class);
        $node->shouldReceive('__toString')->andReturn('#ff0000');

        $result = $this->compiler->compile($node);

        expect($result)->toBe('#ff0000');
    });

});
