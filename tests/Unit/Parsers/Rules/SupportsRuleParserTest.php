<?php

declare(strict_types=1);

use DartSass\Parsers\BlockParser;
use DartSass\Parsers\ExpressionParser;
use DartSass\Parsers\Nodes\NodeType;
use DartSass\Parsers\Rules\SupportsRuleParser;
use DartSass\Parsers\SelectorParser;
use DartSass\Parsers\Tokens\Lexer;

describe('SupportsRuleParser', function () {
    beforeEach(function () {
        $this->lexer = new Lexer();

        $this->createParser = function (string $content): SupportsRuleParser {
            $stream = $this->lexer->tokenize($content);

            $expressionParser = new ExpressionParser($stream);
            $selectorParser   = new SelectorParser($stream, fn() => $expressionParser->parse());

            $parseExpression         = fn() => $expressionParser->parse();
            $parseArgumentExpression = fn() => $expressionParser->parseArgumentList();
            $parseSelector           = fn() => $selectorParser->parse();

            $blockParser = new BlockParser(
                $stream,
                $parseExpression,
                $parseArgumentExpression,
                $parseSelector
            );

            $parseAtRuleClosure = Closure::bind(fn() => $blockParser->parseAtRule(), $blockParser, BlockParser::class);

            return new SupportsRuleParser(
                $stream,
                $parseAtRuleClosure,
                fn() => $blockParser->parseVariable(),
                fn() => $blockParser->parseRule(),
                fn() => $blockParser->parseDeclaration()
            );
        };
    });

    describe('parse()', function () {
        it('parses basic @supports rule', function () {
            $parser = ($this->createParser)('@supports (display: grid) { color: red; }');

            $result = $parser->parse();

            expect($result->query)->toBe('(display: grid)')
                ->and($result->type)->toBe(NodeType::SUPPORTS)
                ->and($result->body['declarations'])->toHaveCount(1);
        });

        it('parses @supports with not keyword', function () {
            $parser = ($this->createParser)('@supports not (display: grid) { color: blue; }');

            $result = $parser->parse();

            expect($result->query)->toContain('not')
                ->and($result->query)->toContain('(display: grid)');
        });

        it('parses @supports with and operator', function () {
            $parser = ($this->createParser)('@supports (display: grid) and (display: flex) { padding: 10px; }');

            $result = $parser->parse();

            expect($result->query)->toContain('and')
                ->and($result->query)->toContain('(display: grid)')
                ->and($result->query)->toContain('(display: flex)');
        });

        it('parses @supports with selector function', function () {
            $parser = ($this->createParser)('@supports selector(:is(a, b)) { color: green; }');

            $result = $parser->parse();

            expect($result->query)->toBe('selector(:is(a, b))');
        });

        it('parses @supports with nested selector function', function () {
            $parser = ($this->createParser)('@supports selector(:is(a, b, c)) { color: red; }');

            $result = $parser->parse();

            expect($result->query)->toBe('selector(:is(a, b, c))');
        });

        it('parses @supports with not selector', function () {
            $parser = ($this->createParser)('@supports not selector(:is(a, b)) { color: blue; }');

            $result = $parser->parse();

            expect($result->query)->toContain('not')
                ->and($result->query)->toContain('selector(:is(a, b))');
        });

        it('parses @supports with multiple declarations', function () {
            $parser = ($this->createParser)('@supports (display: grid) {
                color: red;
                font-size: 14px;
                margin: 10px;
            }');

            $result = $parser->parse();

            expect($result->body['declarations'])->toHaveCount(3);
        });

        it('parses @supports with nested rules', function () {
            $parser = ($this->createParser)('@supports (display: grid) {
                .container {
                    padding: 8px;
                }
            }');

            $result = $parser->parse();

            expect($result->body['nested'])->toHaveCount(1)
                ->and($result->body['nested'][0]->selector->value)->toBe('.container');
        });

        it('parses @supports with mixed content', function () {
            $parser = ($this->createParser)('@supports (display: grid) {
                $bg-color: #fff;
                color: red;

                .header {
                    font-size: 18px;
                }
            }');

            $result = $parser->parse();

            expect($result->body['declarations'])->toHaveCount(1)
                ->and($result->body['nested'])->toHaveCount(2);
        });

        it('parses @supports with custom property', function () {
            $parser = ($this->createParser)('@supports (--custom-property: value) { color: blue; }');

            $result = $parser->parse();

            expect($result->query)->toContain('--custom-property');
        });

        it('parses @supports with transform-origin', function () {
            $parser = ($this->createParser)('@supports (transform-origin: 5px 5px) { color: green; }');

            $result = $parser->parse();

            expect($result->query)->toBe('(transform-origin: 5px 5px)');
        });

        it('parses @supports with selector :nth-child', function () {
            $parser = ($this->createParser)('@supports selector(:nth-child(1n of a)) { color: red; }');

            $result = $parser->parse();

            expect($result->query)->toContain('selector')
                ->and($result->query)->toContain(':nth-child');
        });

        it('parses @supports with semicolon instead of block', function () {
            $parser = ($this->createParser)('@supports (display: grid);');

            $result = $parser->parse();

            expect($result->query)->toBe('(display: grid)')
                ->and($result->body)->toBe([]);
        });

        it('parses @supports without block or semicolon', function () {
            $parser = ($this->createParser)('@supports (display: grid)');

            $result = $parser->parse();

            expect($result->query)->toBe('(display: grid)')
                ->and($result->body)->toBe([]);
        });

        it('parses @supports with not selector keyword', function () {
            $parser = ($this->createParser)('@supports not selector(.test) { color: blue; }');

            $result = $parser->parse();

            expect($result->query)->toBe('not selector(.test)');
        });

        it('parses @supports with number value after colon', function () {
            $parser = ($this->createParser)('@supports (opacity: 0.5) { color: red; }');

            $result = $parser->parse();

            expect($result->query)->toBe('(opacity: 0.5)');
        });

        it('parses @supports with selector token type', function () {
            $parser = ($this->createParser)('@supports selector(:hover) { color: red; }');

            $result = $parser->parse();

            expect($result->query)->toBe('selector(:hover)');
        });

        it('parses @supports with identifier after selector keyword', function () {
            $parser = ($this->createParser)('@supports selector(div) { color: red; }');

            $result = $parser->parse();

            expect($result->query)->toBe('selector(div)');
        });

        it('parses @supports with function selector after not', function () {
            $parser = ($this->createParser)('@supports not selector(:focus) { color: red; }');

            $result = $parser->parse();

            expect($result->query)->toBe('not selector(:focus)');
        });

        it('parses @supports ending with identifier', function () {
            $parser = ($this->createParser)('@supports (display: grid) { }');

            $result = $parser->parse();

            expect($result->query)->toBe('(display: grid)');
        });

        it('parses @supports with not followed by identifier starting with letter', function () {
            $parser = ($this->createParser)('@supports not test { color: red; }');

            $result = $parser->parse();

            expect($result->query)->toContain('not');
        });

        it('parses @supports with nested at-rule', function () {
            $parser = ($this->createParser)('@supports (display: grid) {
                @media (min-width: 768px) {
                    color: blue;
                }
            }');

            $result = $parser->parse();

            expect($result->body['nested'])->toHaveCount(1)
                ->and($result->body['nested'][0]->type)->toBe(NodeType::MEDIA);
        });

        it('parses @supports with selector followed by comma', function () {
            $parser = ($this->createParser)('@supports selector(a, b) { color: red; }');

            $result = $parser->parse();

            expect($result->query)->toBe('selector(a, b)');
        });
    });
});
