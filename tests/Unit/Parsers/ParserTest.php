<?php

declare(strict_types=1);

use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\CommentNode;
use DartSass\Parsers\Nodes\RuleNode;
use DartSass\Parsers\Parser;
use DartSass\Parsers\Tokens\Lexer;
use DartSass\Parsers\Tokens\TokenStream;

describe('Parser', function () {
    beforeEach(function () {
        $this->lexer = new Lexer();

        $this->createParser = function (string $content): Parser {
            $stream = $this->lexer->tokenize($content);

            return new Parser($stream);
        };
    });

    describe('parse()', function () {
        it('parses empty input', function () {
            $parser = ($this->createParser)('');

            $result = $parser->parse();

            expect($result)->toBe([]);
        });

        it('handles null token gracefully', function () {
            $mockStream = mock(TokenStream::class);
            $mockStream->shouldReceive('current')->andReturn(null);
            $mockStream->shouldReceive('isEnd')->andReturn(false);

            $parser = new Parser($mockStream);

            $result = $parser->parse();

            expect($result)->toBe([]);
        });

        it('parses single rule', function () {
            $parser = ($this->createParser)('div { color: red; }');

            $result = $parser->parse();

            expect($result)->toHaveCount(1)
                ->and($result[0])->toBeInstanceOf(RuleNode::class);
        });

        it('parses multiple rules', function () {
            $parser = ($this->createParser)('div { color: red; } span { font-size: 14px; }');

            $result = $parser->parse();

            expect($result)->toHaveCount(2);
        });

        it('parses rule with variable', function () {
            $parser = ($this->createParser)('$color: red; div { color: $color; }');

            $result = $parser->parse();

            expect($result)->toHaveCount(2);
        });

        it('parses comment', function () {
            $parser = ($this->createParser)('/* comment */ div { color: red; }');

            $result = $parser->parse();

            expect($result)->toHaveCount(2)
                ->and($result[0])->toBeInstanceOf(CommentNode::class);
        });

        it('ignores leading whitespace', function () {
            $parser = ($this->createParser)('   div { color: red; }');

            $result = $parser->parse();

            expect($result)->toHaveCount(1);
        });

        it('ignores whitespace between rules', function () {
            $parser = ($this->createParser)('div { color: red; }   \n\nspan { font-size: 14px; }');

            $result = $parser->parse();

            expect($result)->toHaveCount(2);
        });

        it('parses function definition', function () {
            $parser = ($this->createParser)('@function test($arg) { @return $arg; }');

            $result = $parser->parse();

            expect($result)->toHaveCount(1);
        });

        it('parses mixin definition', function () {
            $parser = ($this->createParser)('@mixin test { color: red; }');

            $result = $parser->parse();

            expect($result)->toHaveCount(1);
        });

        it('parses include statement', function () {
            $parser = ($this->createParser)('@mixin test { color: red; } div { @include test; }');

            $result = $parser->parse();

            expect($result)->toHaveCount(2);
        });

        it('parses import statement', function () {
            $parser = ($this->createParser)('@import "colors.scss";');

            $result = $parser->parse();

            expect($result)->toHaveCount(1);
        });

        it('parses at-rule', function () {
            $parser = ($this->createParser)('@media screen { div { color: red; } }');

            $result = $parser->parse();

            expect($result)->toHaveCount(1);
        });

        it('parses nested rules', function () {
            $parser = ($this->createParser)('div { span { color: red; } }');

            $result = $parser->parse();

            expect($result)->toHaveCount(1);
        });

        it('returns correct line numbers', function () {
            $parser = ($this->createParser)("div {\n  color: red;\n}");

            $result = $parser->parse();

            expect($result[0]->line)->toBe(1);
        });
    });

    describe('parseRule()', function () {
        it('parses simple rule', function () {
            $parser = ($this->createParser)('div { color: red; }');

            $result = $parser->parseRule();

            expect($result)->toBeInstanceOf(RuleNode::class)
                ->and($result->selector->value)->toBe('div');
        });

        it('parses rule with multiple selectors', function () {
            $parser = ($this->createParser)('div, span, .class { color: red; }');

            $result = $parser->parseRule();

            expect($result)->toBeInstanceOf(RuleNode::class)
                ->and($result->selector->value)->toContain('div');
        });

        it('parses rule with nested selectors', function () {
            $parser = ($this->createParser)('div { span { color: red; } }');

            $result = $parser->parseRule();

            expect($result)->toBeInstanceOf(RuleNode::class)
                ->and($result->selector->value)->toBe('div');
        });
    });

    describe('parseDeclaration()', function () {
        it('parses single declaration', function () {
            $parser = ($this->createParser)('color: red;');

            $result = $parser->parseDeclaration();

            expect($result)->toHaveCount(1);
        });

        it('parses declaration with variable', function () {
            $parser = ($this->createParser)('color: $myColor;');

            $result = $parser->parseDeclaration();

            expect($result)->toHaveCount(1);
        });

        it('parses declaration with expression', function () {
            $parser = ($this->createParser)('width: 100% - 20px;');

            $result = $parser->parseDeclaration();

            expect($result)->toHaveCount(1);
        });

        it('handles important declaration', function () {
            $parser = ($this->createParser)('color: red !important;');

            $result = $parser->parseDeclaration();

            expect($result)->toHaveCount(1);
        });
    });

    describe('parseExpression()', function () {
        it('parses simple value', function () {
            $parser = ($this->createParser)('red');

            $result = $parser->parseExpression();

            expect($result)->toBeInstanceOf(AstNode::class);
        });

        it('parses number', function () {
            $parser = ($this->createParser)('42');

            $result = $parser->parseExpression();

            expect($result)->toBeInstanceOf(AstNode::class);
        });

        it('parses variable', function () {
            $parser = ($this->createParser)('$color');

            $result = $parser->parseExpression();

            expect($result)->toBeInstanceOf(AstNode::class);
        });

        it('parses expression with operation', function () {
            $parser = ($this->createParser)('10 + 5');

            $result = $parser->parseExpression();

            expect($result)->toBeInstanceOf(AstNode::class);
        });

        it('parses expression with multiplication', function () {
            $parser = ($this->createParser)('10 * 2');

            $result = $parser->parseExpression();

            expect($result)->toBeInstanceOf(AstNode::class);
        });

        it('parses expression with parentheses', function () {
            $parser = ($this->createParser)('(10 + 5) * 2');

            $result = $parser->parseExpression();

            expect($result)->toBeInstanceOf(AstNode::class);
        });

        it('parses string', function () {
            $parser = ($this->createParser)('"hello"');

            $result = $parser->parseExpression();

            expect($result)->toBeInstanceOf(AstNode::class);
        });

        it('parses function call', function () {
            $parser = ($this->createParser)('rgba(255, 0, 0, 1)');

            $result = $parser->parseExpression();

            expect($result)->toBeInstanceOf(AstNode::class);
        });

        it('parses negative number', function () {
            $parser = ($this->createParser)('-10');

            $result = $parser->parseExpression();

            expect($result)->toBeInstanceOf(AstNode::class);
        });

        it('parses color value', function () {
            $parser = ($this->createParser)('#ff0000');

            $result = $parser->parseExpression();

            expect($result)->toBeInstanceOf(AstNode::class);
        });

        it('parses list', function () {
            $parser = ($this->createParser)('1px solid red');

            $result = $parser->parseExpression();

            expect($result)->toBeInstanceOf(AstNode::class);
        });

        it('throws on invalid expression', function () {
            $parser = ($this->createParser)('(');

            expect(fn() => $parser->parseExpression())->toThrow(Exception::class);
        });
    });

    describe('Error Handling', function () {
        it('throws syntax exception for invalid rule', function () {
            $parser = ($this->createParser)('div { color: }');

            expect(fn() => $parser->parse())->toThrow(Error::class);
        });

        it('throws syntax exception for missing closing brace', function () {
            $parser = ($this->createParser)('div { color: red;');

            expect(fn() => $parser->parse())->toThrow(Exception::class);
        });
    });
});
