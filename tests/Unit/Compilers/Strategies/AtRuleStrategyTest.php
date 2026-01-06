<?php

declare(strict_types=1);

use DartSass\Compilers\CompilerContext;
use DartSass\Compilers\Strategies\AtRuleStrategy;
use DartSass\Parsers\Nodes\AstNode;

describe('AtRuleStrategy', function () {
    it('throws InvalidArgumentException when required parameters are missing', function () {
        $strategy = new AtRuleStrategy();

        $node = new AstNode('at-rule', [
            'name'  => '@media',
            'value' => 'screen',
            'body'  => [
                'declarations' => [],
                'nested'       => [],
            ],
        ]);

        $context = new CompilerContext([]);

        expect(fn() => $strategy->compile($node, $context, 0, ''))
            ->toThrow(
                InvalidArgumentException::class,
                'Missing required parameters for at-rule compilation'
            );
    });
});
