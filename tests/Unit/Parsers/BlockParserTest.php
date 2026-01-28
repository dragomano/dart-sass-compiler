<?php

declare(strict_types=1);

use DartSass\Exceptions\SyntaxException;
use DartSass\Parsers\BlockParser;
use DartSass\Parsers\ExpressionParser;
use DartSass\Parsers\Nodes\NodeType;
use DartSass\Parsers\SelectorParser;
use DartSass\Parsers\Tokens\Lexer;

describe('BlockParser', function () {
    beforeEach(function () {
        $this->lexer = new Lexer();

        $this->createParser = function (string $content): BlockParser {
            $stream = $this->lexer->tokenize($content);

            $expressionParser = new ExpressionParser($stream);
            $selectorParser   = new SelectorParser($stream, fn() => $expressionParser->parse());

            $parseExpression         = fn() => $expressionParser->parse();
            $parseArgumentExpression = fn() => $expressionParser->parseArgumentList();
            $parseSelector           = fn() => $selectorParser->parse();

            return new BlockParser(
                $stream,
                $parseExpression,
                $parseArgumentExpression,
                $parseSelector
            );
        };
    });

    describe('parseDeclaration()', function () {
        it('parses property and value', function () {
            $parser = ($this->createParser)('color: red;');

            $result = $parser->parseDeclaration();

            expect($result)->toHaveKey('color')
                ->and($result['color']->value)->toBe('red');
        });

        it('parses property with number value', function () {
            $parser = ($this->createParser)('font-size: 14px;');

            $result = $parser->parseDeclaration();

            expect($result['font-size']->value)->toBe(14.0)
                ->and($result['font-size']->unit)->toBe('px');
        });

        it('parses property with string value', function () {
            $parser = ($this->createParser)('font-family: "Arial";');

            $result = $parser->parseDeclaration();

            expect($result['font-family']->value)->toBe('Arial');
        });

        it('parses property with multiple values', function () {
            $parser = ($this->createParser)('margin: 10px 20px;');

            $result = $parser->parseDeclaration();

            expect($result['margin']->type)->toBe(NodeType::LIST);
        });

        it('parses CSS custom property', function () {
            $parser = ($this->createParser)('--custom-color: blue;');

            $result = $parser->parseDeclaration();

            expect($result)->toHaveKey('--custom-color');
        });
    });

    describe('parseRule()', function () {
        it('parses selector and block', function () {
            $parser = ($this->createParser)('.class { color: red; }');

            $result = $parser->parseRule();

            expect($result->selector->value)->toBe('.class')
                ->and($result->declarations)->toHaveCount(1);
        });

        it('parses rule with multiple declarations', function () {
            $parser = ($this->createParser)('.class {
                color: red;
                font-size: 14px;
            }');

            $result = $parser->parseRule();

            expect($result->declarations)->toHaveCount(2);
        });

        it('parses rule with nested rules', function () {
            $parser = ($this->createParser)('.parent {
                .child {
                    color: blue;
                }
            }');

            $result = $parser->parseRule();

            expect($result->nested)->toHaveCount(1);
        });

        it('parses id selector', function () {
            $parser = ($this->createParser)('#header { color: green; }');

            $result = $parser->parseRule();

            expect($result->selector->value)->toBe('#header');
        });

        it('parses element selector', function () {
            $parser = ($this->createParser)('div { padding: 10px; }');

            $result = $parser->parseRule();

            expect($result->selector->value)->toBe('div');
        });
    });

    describe('parseExpression()', function () {
        it('parses identifier', function () {
            $parser = ($this->createParser)('red;');

            $result = $parser->parseExpression();

            expect($result->value)->toBe('red');
        });

        it('parses number', function () {
            $parser = ($this->createParser)('42;');

            $result = $parser->parseExpression();

            expect($result->value)->toBe(42.0);
        });

        it('parses string', function () {
            $parser = ($this->createParser)('"hello";');

            $result = $parser->parseExpression();

            expect($result->value)->toBe('hello');
        });

        it('parses variable', function () {
            $parser = ($this->createParser)('$color;');

            $result = $parser->parseExpression();

            expect($result->type)->toBe(NodeType::VARIABLE);
        });
    });

    describe('parseInclude()', function () {
        it('parses mixin name', function () {
            $parser = ($this->createParser)('@include mixin-name;');

            $result = $parser->parseInclude();

            expect($result->name)->toBe('mixin-name')
                ->and($result->args)->toHaveCount(0);
        });

        it('parses mixin with arguments', function () {
            $parser = ($this->createParser)('@include mixin-name(arg1, arg2);');

            $result = $parser->parseInclude();

            expect($result->name)->toBe('mixin-name')
                ->and($result->args)->toHaveCount(2);
        });

        it('parses mixin with content block', function () {
            $parser = ($this->createParser)('@include mixin-name {
                color: red;
            }');

            $result = $parser->parseInclude();

            expect($result->name)->toBe('mixin-name')
                ->and($result->body)->not->toBeNull();
        });

        it('parses mixin with dotted name', function () {
            $parser = ($this->createParser)('@include folder.mixin-name;');

            $result = $parser->parseInclude();

            expect($result->name)->toBe('folder.mixin-name');
        });
    });

    describe('parseVariable()', function () {
        it('parses variable name and value', function () {
            $parser = ($this->createParser)('$color: red;');

            $result = $parser->parseVariable();

            expect($result->name)->toBe('$color')
                ->and($result->value->value)->toBe('red');
        });

        it('parses variable with number value', function () {
            $parser = ($this->createParser)('$size: 42;');

            $result = $parser->parseVariable();

            expect($result->value->value)->toBe(42.0);
        });

        it('parses variable with !global flag', function () {
            $parser = ($this->createParser)('$color: red !global;');

            $result = $parser->parseVariable();

            expect($result->global)->toBeTrue();
        });

        it('parses variable with !default flag', function () {
            $parser = ($this->createParser)('$color: blue !default;');

            $result = $parser->parseVariable();

            expect($result->default)->toBeTrue();
        });
    });

    describe('Declaration Value Parsing', function () {
        it('parses comma separated font family values', function () {
            $parser = ($this->createParser)('font-family: "Arial", "Helvetica", sans-serif;');

            $result = $parser->parseDeclaration();

            expect($result['font-family']->separator)->toBe('comma');
        });

        it('parses space separated margin values', function () {
            $parser = ($this->createParser)('margin: 10px 20px 10px 20px;');

            $result = $parser->parseDeclaration();

            expect($result['margin']->separator)->toBe('space');
        });
    });

    describe('Declaration Terminator', function () {
        it('consumes newline as terminator when semicolon is not present', function () {
            $parser = ($this->createParser)('color: red' . PHP_EOL);

            $result = $parser->parseDeclaration();

            expect($result['color']->value)->toBe('red');
        });
    });

    describe('parseBlock() with function token', function () {
        it('handles function token as declaration value through handleFunction', function () {
            $parser = ($this->createParser)('color: rgb(255, 0, 0); }');

            $result = $parser->parseBlock();

            expect($result['declarations'])->toHaveCount(1);
        });
    });

    describe('handleOperator()', function () {
        it('routes non-nested selector operator to declaration handler', function () {
            $parser = ($this->createParser)('% }');

            try {
                $parser->parseBlock();
                $this->fail('Expected SyntaxException');
            } catch (SyntaxException $e) {
                expect($e->getMessage())->toContain('operator');
            }
        });
    });
});
