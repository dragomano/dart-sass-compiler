<?php

declare(strict_types=1);

use DartSass\Exceptions\SyntaxException;
use DartSass\Parsers\ExpressionParser;
use DartSass\Parsers\Nodes\EachNode;
use DartSass\Parsers\Nodes\FunctionNode;
use DartSass\Parsers\Nodes\IdentifierNode;
use DartSass\Parsers\Nodes\VariableNode;
use DartSass\Parsers\Rules\EachRuleParser;
use DartSass\Parsers\Tokens\Lexer;

describe('EachRuleParser', function () {
    beforeEach(function () {
        $this->lexer = new Lexer();

        $this->createParser = function (string $content) {
            $tokenStream = $this->lexer->tokenize($content);

            $expressionParser = new ExpressionParser($tokenStream);
            $parseExpression  = fn() => $expressionParser->parse();
            $parseBlock       = fn() => ['declarations' => [], 'nested' => []];

            return new EachRuleParser($tokenStream, $parseExpression, $parseBlock);
        };
    });

    it('parses basic each rule with single variable', function () {
        $parser = ($this->createParser)('@each $item in $list {}');

        $result = $parser->parse();

        expect($result)->toBeInstanceOf(EachNode::class)
            ->and($result->variables)->toBe(['$item'])
            ->and($result->condition)->toBeInstanceOf(VariableNode::class);
    });

    it('parses each rule with multiple variables', function () {
        $parser = ($this->createParser)('@each $key, $value in $map {}');

        $result = $parser->parse();

        expect($result)->toBeInstanceOf(EachNode::class)
            ->and($result->variables)->toBe(['$key', '$value']);
    });

    it('throws exception when not @each rule', function () {
        $parser = ($this->createParser)('@for $i from 1 to 10 {}');

        expect(fn() => $parser->parse())->toThrow(
            SyntaxException::class,
            'Expected @each rule, got @for'
        );
    });

    it('throws exception when variable is missing', function () {
        $parser = ($this->createParser)('@each in $list {}');

        expect(fn() => $parser->parse())->toThrow(
            SyntaxException::class,
            'Expected variable for @each loop'
        );
    });

    it('throws exception when "in" keyword is missing', function () {
        $parser = ($this->createParser)('@each $item $list {}');

        expect(fn() => $parser->parse())->toThrow(
            SyntaxException::class,
            'Expected "in" keyword in @each rule'
        );
    });

    it('throws exception when opening brace is missing', function () {
        $parser = ($this->createParser)('@each $item in $list');

        expect(fn() => $parser->parse())->toThrow(
            SyntaxException::class,
            'Expected "{" to start @each block'
        );
    });

    it('handles complex condition expression', function () {
        $tokenStream = $this->lexer->tokenize('@each $item in map-keys($map) {}');

        $expressionParser = new ExpressionParser($tokenStream);
        $parseExpression  = fn() => $expressionParser->parse();
        $parseBlock       = fn() => ['declarations' => [], 'nested' => []];

        $parser = new EachRuleParser($tokenStream, $parseExpression, $parseBlock);

        /* @var $result EachNode */
        $result = $parser->parse();

        expect($result)->toBeInstanceOf(EachNode::class)
            ->and($result->condition)->toBeInstanceOf(FunctionNode::class);
    });

    it('parses each rule with body content', function () {
        $tokenStream = $this->lexer->tokenize('@each $color in $colors { .#{$color} { color: $color; } }');

        $expressionParser = new ExpressionParser($tokenStream);
        $parseExpression = fn() => $expressionParser->parse();

        $parseBlock = fn() => [
            'declarations' => [new IdentifierNode('color: $color;', 1)],
            'nested'       => [new IdentifierNode('.#{$color}', 1)],
        ];

        $parser = new EachRuleParser($tokenStream, $parseExpression, $parseBlock);

        /* @var $result EachNode */
        $result = $parser->parse();

        expect($result)->toBeInstanceOf(EachNode::class)
            ->and($result->body)->toHaveCount(2);
    });
});
