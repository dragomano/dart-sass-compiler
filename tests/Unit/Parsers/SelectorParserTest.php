<?php

declare(strict_types=1);

use DartSass\Parsers\Nodes\IdentifierNode;
use DartSass\Parsers\Nodes\InterpolationNode;
use DartSass\Parsers\Nodes\NullNode;
use DartSass\Parsers\Nodes\SelectorNode;
use DartSass\Parsers\Nodes\StringNode;
use DartSass\Parsers\Nodes\VariableNode;
use DartSass\Parsers\SelectorParser;
use DartSass\Parsers\Tokens\Lexer;
use DartSass\Parsers\Tokens\Token;
use DartSass\Parsers\Tokens\TokenStream;
use Tests\ReflectionAccessor;

describe('SelectorParser', function () {
    beforeEach(function () {
        $this->lexer = new Lexer();

        $this->createParser = function (string $content, ?callable $parseExpression = null): SelectorParser {
            $stream = $this->lexer->tokenize($content);

            $parseExpression = $parseExpression ?? fn() => new IdentifierNode('default', 1);

            return new SelectorParser($stream, $parseExpression(...));
        };
    });

    describe('parse()', function () {
        it('parses simple class selector', function () {
            $parser = ($this->createParser)('.button');

            $result = $parser->parse();

            expect($result)->toBeInstanceOf(SelectorNode::class)
                ->and($result->value)->toBe('.button');
        });

        it('parses id selector', function () {
            $parser = ($this->createParser)('#header');

            $result = $parser->parse();

            expect($result->value)->toBe('#header');
        });

        it('parses element selector', function () {
            $parser = ($this->createParser)('div');

            $result = $parser->parse();

            expect($result->value)->toBe('div');
        });

        it('parses compound selector', function () {
            $parser = ($this->createParser)('div.container');

            $result = $parser->parse();

            expect($result->value)->toBe('div.container');
        });

        it('parses descendant selector', function () {
            $parser = ($this->createParser)('.parent .child');

            $result = $parser->parse();

            expect($result->value)->toBe('.parent.child');
        });

        it('parses child selector', function () {
            $parser = ($this->createParser)('.parent > .child');

            $result = $parser->parse();

            expect($result->value)->toBe('.parent>.child');
        });

        it('parses adjacent sibling selector', function () {
            $parser = ($this->createParser)('h1 + p');

            $result = $parser->parse();

            expect($result->value)->toBe('h1+p');
        });

        it('parses general sibling selector', function () {
            $parser = ($this->createParser)('h1 ~ p');

            $result = $parser->parse();

            expect($result->value)->toBe('h1 p');
        });

        it('parses attribute selector', function () {
            $parser = ($this->createParser)('[data-value]');

            $result = $parser->parse();

            expect($result->value)->toBe('[data-value]');
        });

        it('parses attribute selector with value', function () {
            $parser = ($this->createParser)('[type="text"]');

            $result = $parser->parse();

            expect($result->value)->toBe('[type=text]');
        });

        it('parses pseudo-class selector', function () {
            $parser = ($this->createParser)(':hover');

            $result = $parser->parse();

            expect($result->value)->toBe(':hover');
        });

        it('parses pseudo-element selector', function () {
            $parser = ($this->createParser)('::before');

            $result = $parser->parse();

            expect($result->value)->toBe('::before');
        });

        it('parses pseudo-class with argument', function () {
            $parser = ($this->createParser)(':nth-child(2n+1)');

            $result = $parser->parse();

            expect($result->value)->toBe(':nth-child(2n+1)');
        });

        it('parses multiple selectors', function () {
            $parser = ($this->createParser)('h1, h2, h3');

            $result = $parser->parse();

            expect($result->value)->toBe('h1, h2, h3');
        });

        it('parses selector with interpolation', function () {
            $parser = ($this->createParser)('"#{$class}"');

            $result = $parser->parse();

            expect($result->value)->toContain('#{');
        });

        it('parses @content in selector', function () {
            $parser = ($this->createParser)('@content');

            $result = $parser->parse();

            expect($result->value)->toBe('@content');
        });

        it('stops at semicolon', function () {
            $parser = ($this->createParser)('.button; color: red;');

            $result = $parser->parse();

            expect($result->value)->toBe('.button');
        });

        it('stops at brace open', function () {
            $parser = ($this->createParser)('.button { color: red; }');

            $result = $parser->parse();

            expect($result->value)->toBe('.button');
        });

        it('preserves case in selector', function () {
            $parser = ($this->createParser)('.MyClass');

            $result = $parser->parse();

            expect($result->value)->toBe('.MyClass');
        });

        it('parses selector with numbers', function () {
            $parser = ($this->createParser)('.col-md-6');

            $result = $parser->parse();

            expect($result->value)->toBe('.col-md-6');
        });

        it('skips comment in selector', function () {
            $parser = ($this->createParser)('.button /* hover */:hover');

            $result = $parser->parse();

            expect($result->value)->toBe('.button:hover');
        });
    });

    describe('parsePseudoClassFunction()', function () {
        it('parses pseudo-class function with simple argument', function () {
            $parser = ($this->createParser)(':lang(en)');

            $result = $parser->parse();

            expect($result->value)->toBe(':lang(en)');
        });

        it('parses pseudo-class function with complex argument', function () {
            $parser = ($this->createParser)(':not(.active)');

            $result = $parser->parse();

            expect($result->value)->toBe(':not(.active)');
        });

        it('parses nested pseudo-class functions', function () {
            $parser = ($this->createParser)(':is(:hover, :focus)');

            $result = $parser->parse();

            expect($result->value)->toBe(':is(:hover,:focus)');
        });

        it('handles nested parentheses in function', function () {
            $parser = ($this->createParser)(':nth-child(2n + (1))');

            $result = $parser->parse();

            expect($result->value)->toBe(':nth-child(2n+(1))');
        });
    });

    describe('needsSpaceBeforeToken()', function () {
        beforeEach(function () {
            $parser = ($this->createParser)('div');

            $this->accessor = new ReflectionAccessor($parser);
        });

        it('needs space before identifier', function () {
            $token = new Token('identifier', 'test', 1, 1);

            $result = $this->accessor->callMethod('needsSpaceBeforeToken', ['div', $token]);

            expect($result)->toBeTrue();
        });

        it('no space after dot', function () {
            $token = new Token('identifier', '.test', 1, 1);

            $result = $this->accessor->callMethod('needsSpaceBeforeToken', ['.', $token]);

            expect($result)->toBeFalse();
        });

        it('no space before dot', function () {
            $token = new Token('selector', '.', 1, 1);

            $result = $this->accessor->callMethod('needsSpaceBeforeToken', ['test', $token]);

            expect($result)->toBeFalse();
        });

        it('no space for number token', function () {
            $token = new Token('number', '123', 1, 1);

            $result = $this->accessor->callMethod('needsSpaceBeforeToken', ['test', $token]);

            expect($result)->toBeFalse();
        });
    });

    describe('optimizeAttributeSelector()', function () {
        beforeEach(function () {
            $parser = ($this->createParser)('div');

            $this->accessor = new ReflectionAccessor($parser);
        });

        it('optimizes attribute selector with unquoted value', function () {
            $result = $this->accessor->callMethod('optimizeAttributeSelector', ['[type="text"]']);

            expect($result)->toBe('[type=text]');
        });

        it('keeps quoted attribute selector with special chars', function () {
            $result = $this->accessor->callMethod('optimizeAttributeSelector', ['[data-value="hello world"]']);

            expect($result)->toBe('[data-value="hello world"]');
        });
    });

    describe('Complex Selectors', function () {
        it('parses complex nested selector', function () {
            $parser = ($this->createParser)('.card > .card-header + .card-body');

            $result = $parser->parse();

            expect($result->value)->toBe('.card>.card-header+.card-body');
        });

        it('parses selector with multiple pseudo-classes', function () {
            $parser = ($this->createParser)('a:hover:focus');

            $result = $parser->parse();

            expect($result->value)->toBe('a:hover:focus');
        });

        it('parses universal selector', function () {
            $parser = ($this->createParser)('*');

            $result = $parser->parse();

            expect($result->value)->toBe('*');
        });

        it('parses universal with class', function () {
            $parser = ($this->createParser)('*.my-class');

            $result = $parser->parse();

            expect($result->value)->toBe('*.my-class');
        });
    });

    describe('Error Handling', function () {
        it('throws exception for unexpected at-rule', function () {
            $parser = ($this->createParser)('@import url("style.css")');

            expect(fn() => $parser->parse())->toThrow(Exception::class);
        });

        it('throws exception for unexpected token', function () {
            $tokens = [
                new Token('selector', '.button', 1, 1),
                new Token('paren_open', '(', 1, 10),
            ];

            $stream = new TokenStream($tokens);
            $parser = new SelectorParser($stream, fn() => new IdentifierNode('default', 1));

            expect(fn() => $parser->parse())->toThrow(Exception::class);
        });
    });

    describe('formatExpressionForSelector()', function () {
        beforeEach(function () {
            $parser = ($this->createParser)('div');

            $this->accessor = new ReflectionAccessor($parser);
        });

        it('formats VariableNode', function () {
            $node = new VariableNode('color');

            $result = $this->accessor->callMethod('formatExpressionForSelector', [$node]);

            expect($result)->toBe('color');
        });

        it('formats IdentifierNode', function () {
            $node = new IdentifierNode('my-class');

            $result = $this->accessor->callMethod('formatExpressionForSelector', [$node]);

            expect($result)->toBe('my-class');
        });

        it('formats StringNode with quotes', function () {
            $node = new StringNode('"test"');

            $result = $this->accessor->callMethod('formatExpressionForSelector', [$node]);

            expect($result)->toBe('test');
        });

        it('formats StringNode without quotes', function () {
            $node = new StringNode('test');

            $result = $this->accessor->callMethod('formatExpressionForSelector', [$node]);

            expect($result)->toBe('test');
        });

        it('formats InterpolationNode', function () {
            $innerNode = new IdentifierNode('var');
            $node      = new InterpolationNode($innerNode);

            $result = $this->accessor->callMethod('formatExpressionForSelector', [$node]);

            expect($result)->toBe('#{var}');
        });

        it('returns expression for unknown node type', function () {
            $node = new NullNode();

            $result = $this->accessor->callMethod('formatExpressionForSelector', [$node]);

            expect($result)->toBe('expression');
        });
    });
});
