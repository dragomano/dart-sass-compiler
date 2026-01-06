<?php

declare(strict_types=1);

use DartSass\Compilers\CompilerContext;
use DartSass\Compilers\Strategies\MediaRuleStrategy;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Utils\ValueFormatter;

describe('ConditionalRuleStrategy', function () {
    it('throws InvalidArgumentException when required parameters are missing', function () {
        $strategy = new MediaRuleStrategy();

        $node = new AstNode('media', [
            'query' => 'screen',
            'body'  => [
                'declarations' => [],
                'nested'       => [],
            ],
        ]);

        $valueFormatter = mock(ValueFormatter::class);
        $context = new CompilerContext([]);
        $context->valueFormatter = $valueFormatter;

        expect(fn() => $strategy->compile($node, $context, 0, ''))
            ->toThrow(
                InvalidArgumentException::class,
                'Missing required parameters for media rule compilation'
            );
    });

    it('compiles declarations without parent selector when bodyDeclarations are present and parentSelector is empty', function () {
        $strategy = new MediaRuleStrategy();

        $node = new AstNode('media', [
            'query' => 'screen',
            'body'  => [
                'declarations' => [
                    ['property' => 'color', 'value' => 'red'],
                ],
                'nested' => [],
            ],
        ]);

        $valueFormatter = mock(ValueFormatter::class);
        $valueFormatter->shouldReceive('format')->andReturn('screen');

        $context = new CompilerContext([]);
        $context->valueFormatter = $valueFormatter;

        $compileDeclarations = function ($declarations, $level, $selector) {
            return "  color: red;\n";
        };

        $compileAst = function ($nested, $selector, $level) {
            return '';
        };

        $evaluateInterpolations = function ($query) {
            return $query;
        };

        $result = $strategy->compile(
            $node,
            $context,
            0,
            '',
            $evaluateInterpolations,
            $compileDeclarations,
            $compileAst
        );

        expect($result)->toContain("@media screen {\n  color: red;\n}\n");
    });

    it('fixes nested selectors in media queries using regex', function () {
        $strategy = new MediaRuleStrategy();

        $node = new AstNode('media', [
            'query' => 'screen',
            'body'  => [
                'declarations' => [],
                'nested'       => [
                    new AstNode('rule', [
                        'selectors' => ['.class1, .class2'],
                        'body'      => [
                            'declarations' => [['property' => 'color', 'value' => 'blue']],
                            'nested'       => [],
                        ],
                    ]),
                ],
            ],
        ]);

        $valueFormatter = mock(ValueFormatter::class);
        $valueFormatter->shouldReceive('format')->andReturn('screen');

        $context = new CompilerContext([]);
        $context->valueFormatter = $valueFormatter;

        $compileDeclarations = function ($declarations, $level, $selector) {
            return "    color: blue;\n";
        };

        $compileAst = function ($nested, $selector, $level) {
            // Simulate the nested CSS that needs fixing
            return "  .class1, .class2 {\n    color: blue;\n  }\n";
        };

        $evaluateInterpolations = function ($query) {
            return $query;
        };

        $result = $strategy->compile(
            $node,
            $context,
            0,
            '',
            $evaluateInterpolations,
            $compileDeclarations,
            $compileAst
        );

        // The fixNestedSelectorsInMedia should transform ".class1, .class2 {" into ".class1,\n  .class2 {"
        expect($result)->toContain(".class1,\n  .class2 {");
    });
});
