<?php

declare(strict_types=1);

use DartSass\Compilers\Strategies\KeyframesRuleStrategy;
use DartSass\Parsers\Nodes\KeyframesNode;

describe('KeyframesRuleStrategy', function () {
    it('throws InvalidArgumentException when evaluateExpression is missing', function () {
        $strategy = new KeyframesRuleStrategy();

        $node = new KeyframesNode('slideIn', [
            [
                'selectors'    => ['0%', '100%'],
                'declarations' => [['opacity' => '1']],
            ],
        ], 1);

        expect(fn() => $strategy->compile($node, '', 0))
            ->toThrow(
                InvalidArgumentException::class,
                'Missing required parameters for keyframes rule compilation'
            );
    });
});
