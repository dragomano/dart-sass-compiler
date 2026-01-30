<?php

declare(strict_types=1);

use DartSass\Exceptions\SyntaxException;
use DartSass\Parsers\ExpressionParser;
use DartSass\Parsers\Nodes\ConditionNode;
use DartSass\Parsers\Nodes\IfNode;
use DartSass\Parsers\Nodes\OperationNode;
use DartSass\Parsers\Rules\IfRuleParser;
use DartSass\Parsers\Tokens\Lexer;
use Tests\ReflectionAccessor;

describe('IfRuleParser', function () {
    beforeEach(function () {
        $this->lexer = new Lexer();

        $this->createParser = function (string $content) {
            $tokenStream = $this->lexer->tokenize($content);

            $expressionParser = new ExpressionParser($tokenStream);
            $parseExpression  = fn() => $expressionParser->parse();
            $parseBlock       = fn() => ['declarations' => [], 'nested' => []];

            return new IfRuleParser($tokenStream, $parseExpression, $parseBlock);
        };
    });

    it('parses basic if rule with simple condition', function () {
        $parser = ($this->createParser)('@if $debug { color: red; }');
        $result = $parser->parse();

        expect($result)->toBeInstanceOf(IfNode::class)
            ->and($result->condition)->toBeInstanceOf(ConditionNode::class)
            ->and($result->body)->toHaveCount(0)
            ->and($result->else)->toBeNull();
    });

    it('parses if rule with complex condition', function () {
        $parser = ($this->createParser)('@if $width > 768px { font-size: 18px; }');
        $result = $parser->parse();

        expect($result)->toBeInstanceOf(IfNode::class)
            ->and($result->condition->expression)->toBeInstanceOf(OperationNode::class);
    });

    it('parses if rule with logical operators', function () {
        $parser = ($this->createParser)('@if $mobile and $landscape { padding: 10px; }');
        $result = $parser->parse();

        expect($result)->toBeInstanceOf(IfNode::class)
            ->and($result->condition->expression)->toBeInstanceOf(OperationNode::class);
    });

    it('throws exception when not @if rule', function () {
        $parser = ($this->createParser)('@for $i from 1 to 10 {}');

        expect(fn() => $parser->parse())->toThrow(
            SyntaxException::class,
            'Expected @if rule, got @for'
        );
    });

    it('throws exception when opening brace is missing', function () {
        $parser = ($this->createParser)('@if $debug color: red;');

        expect(fn() => $parser->parse())->toThrow(
            SyntaxException::class,
            'Expected "{" to start @if block'
        );
    });

    it('handles multiple declarations in if body', function () {
        $parser = ($this->createParser)('@if $theme == dark { background: black; color: white; font-size: 16px; }');
        $result = $parser->parse();

        expect($result)->toBeInstanceOf(IfNode::class);
    });

    it('validates condition parsing with reflection', function () {
        $parser = ($this->createParser)('@if $responsive { display: block; }');

        $accessor = new ReflectionAccessor($parser);
        $result   = $accessor->callMethod('parse');

        expect($result)->toBeInstanceOf(IfNode::class)
            ->and($result->condition)->toBeInstanceOf(ConditionNode::class);
    });

    it('validates full condition expression parsing with reflection', function () {
        $parser = ($this->createParser)('@if $width > 100px and $height < 200px { margin: 10px; }');

        $accessor = new ReflectionAccessor($parser);
        $result   = $accessor->callMethod('parse');

        expect($result)->toBeInstanceOf(IfNode::class)
            ->and($result->condition->expression)->toBeInstanceOf(OperationNode::class);
    });

    it('handles complex nested conditions', function () {
        $parser = ($this->createParser)('@if ($width > 768px) and ($height > 400px) { display: flex; }');
        $result = $parser->parse();

        expect($result)->toBeInstanceOf(IfNode::class)
            ->and($result->condition->expression)->toBeInstanceOf(OperationNode::class);
    });

    it('handles null current token in ternary operator', function () {
        $parser = ($this->createParser)('@if $var value');

        expect(fn() => $parser->parse())->toThrow(
            SyntaxException::class,
            'Expected "{" to start @if block. No current token'
        );
    });

    it('throws exception when else if block missing opening brace', function () {
        $tokens = $this->lexer->tokenize('@else if $condition color: red;');

        $expressionParser = new ExpressionParser($tokens);
        $parseExpression  = fn() => $expressionParser->parse();
        $parseBlock       = fn() => ['declarations' => [], 'nested' => []];

        $parser   = new IfRuleParser($tokens, $parseExpression, $parseBlock);
        $accessor = new ReflectionAccessor($parser);

        expect(fn() => $accessor->callMethod('parseElseChain'))->toThrow(
            SyntaxException::class,
            'Expected "{" to start @else if block'
        );
    });

    it('throws exception when else block missing opening brace', function () {
        $tokens = $this->lexer->tokenize('@else color: red;');

        $expressionParser = new ExpressionParser($tokens);
        $parseExpression  = fn() => $expressionParser->parse();
        $parseBlock       = fn() => ['declarations' => [], 'nested' => []];

        $parser   = new IfRuleParser($tokens, $parseExpression, $parseBlock);
        $accessor = new ReflectionAccessor($parser);

        expect(fn() => $accessor->callMethod('parseElseChain'))->toThrow(
            SyntaxException::class,
            'Expected "{" to start @else block'
        );
    });
});
