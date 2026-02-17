<?php

declare(strict_types=1);

use DartSass\Compilers\Strategies\AtRuleStrategy;
use DartSass\Parsers\Nodes\AtRuleNode;

describe('AtRuleStrategy', function () {
    it('throws InvalidArgumentException when required parameters are missing', function () {
        $strategy = new AtRuleStrategy();

        $node = new AtRuleNode('@media', 'screen', [
            'declarations' => [],
            'nested'       => [],
        ], 1);

        expect(fn() => $strategy->compile($node, '', 0))
            ->toThrow(
                InvalidArgumentException::class,
                'Missing required parameters for at-rule compilation'
            );
    });

    it('throws InvalidArgumentException for @mixin without define callback', function () {
        $strategy = new AtRuleStrategy();

        $node = new AtRuleNode('@mixin', 'test($arg: 1)', [
            'declarations' => [],
            'nested'       => [],
        ], 1);

        expect(fn() => $strategy->compile(
            $node,
            '',
            0,
            fn($value) => $value,
            fn() => '',
            fn() => ''
        ))->toThrow(
            InvalidArgumentException::class,
            'Missing required parameters for at-rule compilation'
        );
    });
});
