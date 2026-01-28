<?php

declare(strict_types=1);

use DartSass\Exceptions\SyntaxException;
use DartSass\Parsers\ExpressionParser;
use DartSass\Parsers\Nodes\OperationNode;
use DartSass\Parsers\Nodes\VariableNode;
use DartSass\Parsers\Nodes\WhileNode;
use DartSass\Parsers\Rules\WhileRuleParser;
use DartSass\Parsers\Tokens\Lexer;
use Tests\ReflectionAccessor;

describe('WhileRuleParser', function () {
    beforeEach(function () {
        $this->lexer = new Lexer();

        $this->createParser = function (string $content) {
            $tokenStream = $this->lexer->tokenize($content);

            $expressionParser = new ExpressionParser($tokenStream);
            $parseExpression  = fn() => $expressionParser->parse();
            $parseBlock       = fn() => ['declarations' => [], 'nested' => []];

            return new WhileRuleParser($tokenStream, $parseExpression, $parseBlock);
        };
    });

    it('parses basic while rule with simple condition', function () {
        $parser = ($this->createParser)('@while $index < 5 { $index: $index + 1; }');

        $result = $parser->parse();

        expect($result)->toBeInstanceOf(WhileNode::class)
            ->and($result->condition)->toBeInstanceOf(OperationNode::class)
            ->and($result->body)->toHaveCount(0);
    });

    it('parses while rule with variable condition', function () {
        $parser = ($this->createParser)('@while $count > 0 { $count: $count - 1; }');

        $result = $parser->parse();

        expect($result)->toBeInstanceOf(WhileNode::class)
            ->and($result->condition)->toBeInstanceOf(OperationNode::class);
    });

    it('parses while rule with complex expression', function () {
        $parser = ($this->createParser)('@while ($width > 100px) and ($height < 500px) { $width: $width - 10px; }');

        $result = $parser->parse();

        expect($result)->toBeInstanceOf(WhileNode::class)
            ->and($result->condition)->toBeInstanceOf(OperationNode::class);
    });

    it('parses while rule with multiple declarations in body', function () {
        $parser = ($this->createParser)('@while $i < 3 { $result: $result + $i; $i: $i + 1; }');

        $result = $parser->parse();

        expect($result)->toBeInstanceOf(WhileNode::class);
    });

    it('throws exception when not @while rule', function () {
        $parser = ($this->createParser)('@for $i from 1 to 10 {}');

        expect(fn() => $parser->parse())->toThrow(
            SyntaxException::class,
            'Expected @while rule, got @for'
        );
    });

    it('throws exception when opening brace is missing', function () {
        $parser = ($this->createParser)('@while $condition $index: $index + 1;');

        expect(fn() => $parser->parse())->toThrow(
            SyntaxException::class,
            'Expected "{" to start @while block'
        );
    });

    it('handles nested while rules', function () {
        $tokens = $this->lexer->tokenize('@while $outer > 0 { @while $inner < 5 { $inner: $inner + 1; } $outer: $outer - 1; }');

        $expressionParser = new ExpressionParser($tokens);
        $parseExpression  = fn() => $expressionParser->parse();
        $parseBlock       = fn() => ['declarations' => [], 'nested' => [new stdClass()]];

        $parser = new WhileRuleParser($tokens, $parseExpression, $parseBlock);

        /* @var $result WhileNode */
        $result = $parser->parse();

        expect($result)->toBeInstanceOf(WhileNode::class)
            ->and($result->body)->toHaveCount(1);
    });

    it('handles while rule with variable declarations only', function () {
        $tokens = $this->lexer->tokenize('@while $debug { $temp: red; $size: 10px; }');

        $expressionParser = new ExpressionParser($tokens);
        $parseExpression  = fn() => $expressionParser->parse();
        $parseBlock       = fn() => ['declarations' => [], 'nested' => []];

        $parser = new WhileRuleParser($tokens, $parseExpression, $parseBlock);

        $result = $parser->parse();

        expect($result)->toBeInstanceOf(WhileNode::class);
    });

    it('validates condition parsing with reflection', function () {
        $parser = ($this->createParser)('@while $active { color: blue; }');

        $accessor = new ReflectionAccessor($parser);
        $result   = $accessor->callMethod('parse');

        expect($result)->toBeInstanceOf(WhileNode::class)
            ->and($result->condition)->toBeInstanceOf(VariableNode::class);
    });

    it('handles while rule with complex arithmetic', function () {
        $parser = ($this->createParser)('@while $value >= 0.1 { $value: $value * 0.9; }');

        $result = $parser->parse();

        expect($result)->toBeInstanceOf(WhileNode::class)
            ->and($result->condition)->toBeInstanceOf(OperationNode::class);
    });

    it('handles while rule with equality comparison', function () {
        $parser = ($this->createParser)('@while $status != done { $status: processing; }');

        $result = $parser->parse();

        expect($result)->toBeInstanceOf(WhileNode::class)
            ->and($result->condition)->toBeInstanceOf(OperationNode::class);
    });

    it('handles empty while body', function () {
        $parser = ($this->createParser)('@while $flag { }');

        $result = $parser->parse();

        expect($result)->toBeInstanceOf(WhileNode::class)
            ->and($result->body)->toBe([]);
    });
});
