<?php

declare(strict_types=1);

use DartSass\Compilers\CompilerContext;
use DartSass\Compilers\Strategies\KeyframesRuleStrategy;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Utils\ValueFormatter;

describe('KeyframesRuleStrategy', function () {
    it('throws InvalidArgumentException when evaluateExpression is missing', function () {
        $strategy = new KeyframesRuleStrategy();

        $node = new AstNode('keyframes', [
            'name'      => 'slideIn',
            'keyframes' => [
                [
                    'selectors'    => ['0%', '100%'],
                    'declarations' => [['opacity' => '1']],
                ],
            ],
        ]);

        $valueFormatter = mock(ValueFormatter::class);
        $context = new CompilerContext([]);
        $context->valueFormatter = $valueFormatter;

        expect(fn() => $strategy->compile($node, $context, 0, ''))
            ->toThrow(
                InvalidArgumentException::class,
                'Missing required parameters for keyframes rule compilation'
            );
    });
});
