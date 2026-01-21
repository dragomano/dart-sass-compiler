<?php

declare(strict_types=1);

use DartSass\Compilers\CompilerContext;
use DartSass\Compilers\Strategies\MediaRuleStrategy;
use DartSass\Parsers\Nodes\IdentifierNode;
use DartSass\Parsers\Nodes\MediaNode;
use DartSass\Parsers\Nodes\RuleNode;
use DartSass\Utils\ResultFormatterInterface;

describe('ConditionalRuleStrategy', function () {
    beforeEach(function () {
        $this->resultFormatter = mock(ResultFormatterInterface::class);
        $this->context = new CompilerContext([]);
        $this->context->resultFormatter = $this->resultFormatter;
    });

    it('throws InvalidArgumentException when required parameters are missing', function () {
        $strategy = new MediaRuleStrategy();

        $node = new MediaNode('screen', [
            'declarations' => [],
            'nested'       => [],
        ], 1);

        expect(fn() => $strategy->compile($node, $this->context, 0, ''))
            ->toThrow(
                InvalidArgumentException::class,
                'Missing required parameters for media rule compilation'
            );
    });

    it('compiles declarations without parent selector when bodyDeclarations are present and parentSelector is empty', function () {
        $strategy = new MediaRuleStrategy();

        $node = new MediaNode('screen', [
            'declarations' => [
                [
                    'property' => 'color',
                    'value'    => 'red',
                ],
            ],
            'nested' => [],
        ], 1);

        $this->resultFormatter->shouldReceive('format')->andReturn('screen');

        $result = $strategy->compile(
            $node,
            $this->context,
            0,
            '',
            fn($query) => $query,
            fn() => "  color: red;\n",
            fn() => ''
        );

        expect($result)->toContain("@media screen {\n  color: red;\n}\n");
    });

    it('fixes nested selectors in media queries using regex', function () {
        $strategy   = new MediaRuleStrategy();
        $selector   = new IdentifierNode('.class1, .class2', 1);
        $nestedRule = new RuleNode($selector, [['property' => 'color', 'value' => 'blue']], [], 1);

        $node = new MediaNode('screen', [
            'declarations' => [],
            'nested'       => [$nestedRule],
        ], 1);

        $this->resultFormatter->shouldReceive('format')->andReturn('screen');

        $result = $strategy->compile(
            $node,
            $this->context,
            0,
            '',
            fn($query) => $query,
            fn() => "    color: blue;\n",
            fn() => "  .class1, .class2 {\n    color: blue;\n  }\n"
        );

        expect($result)->toContain(".class1,\n  .class2 {");
    });
});
