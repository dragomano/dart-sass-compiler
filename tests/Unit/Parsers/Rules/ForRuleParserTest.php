<?php

declare(strict_types=1);

use DartSass\Exceptions\SyntaxException;
use DartSass\Parsers\ExpressionParser;
use DartSass\Parsers\Nodes\ForNode;
use DartSass\Parsers\Nodes\NumberNode;
use DartSass\Parsers\Nodes\VariableNode;
use DartSass\Parsers\Rules\ForRuleParser;
use DartSass\Parsers\Tokens\Lexer;
use Tests\ReflectionAccessor;

describe('ForRuleParser', function () {
    beforeEach(function () {
        $this->lexer = new Lexer();

        $this->createParser = function (string $content) {
            $tokenStream = $this->lexer->tokenize($content);

            $expressionParser = new ExpressionParser($tokenStream);
            $parseExpression  = fn() => $expressionParser->parse();
            $parseBlock       = fn() => ['declarations' => [], 'nested' => []];

            return new ForRuleParser($tokenStream, $parseExpression, $parseBlock);
        };
    });

    it('parses basic for rule with "to" keyword', function () {
        $parser = ($this->createParser)('@for $i from 1 to 10 {}');

        $result = $parser->parse();

        expect($result)->toBeInstanceOf(ForNode::class)
            ->and($result->variable)->toBe('$i')
            ->and($result->inclusive)->toBeFalse()
            ->and($result->from)->toBeInstanceOf(NumberNode::class)
            ->and($result->to)->toBeInstanceOf(NumberNode::class);
    });

    it('parses for rule with "through" keyword', function () {
        $parser = ($this->createParser)('@for $i from 1 through 10 {}');

        $result = $parser->parse();

        expect($result)->toBeInstanceOf(ForNode::class)
            ->and($result->inclusive)->toBeTrue();
    });

    it('throws exception when not @for rule', function () {
        $parser = ($this->createParser)('@each $item in $list {}');

        expect(fn() => $parser->parse())->toThrow(
            SyntaxException::class,
            'Expected @for rule, got @each'
        );
    });

    it('throws exception when variable is missing', function () {
        $parser = ($this->createParser)('@for from 1 to 10 {}');

        expect(fn() => $parser->parse())->toThrow(
            SyntaxException::class,
            'Expected variable for @for loop'
        );
    });

    it('throws exception when "from" keyword is missing', function () {
        $parser = ($this->createParser)('@for $i 1 to 10 {}');

        expect(fn() => $parser->parse())->toThrow(
            SyntaxException::class,
            'Expected "from" keyword in @for rule'
        );
    });

    it('throws exception when "to" or "through" keyword is missing', function () {
        $parser = ($this->createParser)('@for $i from 1 10 {}');

        expect(fn() => $parser->parse())->toThrow(
            SyntaxException::class,
            'Expected "to" or "through" keyword in @for rule'
        );
    });

    it('throws exception when opening brace is missing', function () {
        $parser = ($this->createParser)('@for $i from 1 to 10');

        expect(fn() => $parser->parse())->toThrow(
            SyntaxException::class,
            'Expected "{" to start @for block'
        );
    });

    it('handles complex from and to expressions', function () {
        $tokenStream = $this->lexer->tokenize('@for $i from $start to $end {}');

        $expressionParser = new ExpressionParser($tokenStream);
        $parseExpression  = fn() => $expressionParser->parse();
        $parseBlock       = fn() => ['declarations' => [], 'nested' => []];

        $parser = new ForRuleParser($tokenStream, $parseExpression, $parseBlock);

        /* @var $result ForNode */
        $result = $parser->parse();

        expect($result)->toBeInstanceOf(ForNode::class)
            ->and($result->from)->toBeInstanceOf(VariableNode::class)
            ->and($result->to)->toBeInstanceOf(VariableNode::class);
    });

    it('parses for rule with body content', function () {
        $tokenStream = $this->lexer->tokenize('@for $i from 1 to 3 { .item-#{$i} { width: $i * 10px; } }');

        $expressionParser = new ExpressionParser($tokenStream);
        $parseExpression  = fn() => $expressionParser->parse();

        $parseBlock = fn() => [
            'declarations' => [new NumberNode(10, 'px', 1)],
            'nested'       => [new NumberNode(1, null, 1)],
        ];

        $parser = new ForRuleParser($tokenStream, $parseExpression, $parseBlock);

        /* @var $result ForNode */
        $result = $parser->parse();

        expect($result)->toBeInstanceOf(ForNode::class)
            ->and($result->body)->toHaveCount(2);
    });

    it('validates inclusive flag for "through" keyword', function () {
        $parser = ($this->createParser)('@for $i from 1 through 5 {}');

        $accessor = new ReflectionAccessor($parser);

        $result = $accessor->callMethod('parse');

        expect($result)->toBeInstanceOf(ForNode::class)
            ->and($result->inclusive)->toBeTrue();
    });

    it('validates exclusive flag for "to" keyword', function () {
        $parser = ($this->createParser)('@for $i from 1 to 5 {}');

        $accessor = new ReflectionAccessor($parser);

        $result = $accessor->callMethod('parse');

        expect($result)->toBeInstanceOf(ForNode::class)
            ->and($result->inclusive)->toBeFalse();
    });
});
