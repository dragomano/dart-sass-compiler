<?php

declare(strict_types=1);

use DartSass\Exceptions\SyntaxException;
use DartSass\Parsers\ExpressionParser;
use DartSass\Parsers\Nodes\DebugNode;
use DartSass\Parsers\Nodes\StringNode;
use DartSass\Parsers\Nodes\VariableNode;
use DartSass\Parsers\Rules\DebugRuleParser;
use DartSass\Parsers\Tokens\Lexer;

describe('DebugRuleParser', function () {
    beforeEach(function () {
        $this->lexer = new Lexer();

        $this->createParser = function (string $content) {
            $tokenStream = $this->lexer->tokenize($content);

            $expressionParser = new ExpressionParser($tokenStream);
            $parseExpression  = fn() => $expressionParser->parse();

            return new DebugRuleParser($tokenStream, $parseExpression);
        };
    });

    it('parses @debug with variable', function () {
        $parser = ($this->createParser)('@debug $color;');
        $result = $parser->parse();

        expect($result)->toBeInstanceOf(DebugNode::class)
            ->and($result->expression)->toBeInstanceOf(VariableNode::class);
    });

    it('parses @debug with string', function () {
        $parser = ($this->createParser)('@debug "Hello World";');
        $result = $parser->parse();

        expect($result)->toBeInstanceOf(DebugNode::class)
            ->and($result->expression)->toBeInstanceOf(StringNode::class);
    });

    it('parses @debug without semicolon', function () {
        $parser = ($this->createParser)('@debug $value');
        $result = $parser->parse();

        expect($result)->toBeInstanceOf(DebugNode::class);
    });

    it('parses @debug with expression', function () {
        $parser = ($this->createParser)('@debug 1 + 2;');
        $result = $parser->parse();

        expect($result)->toBeInstanceOf(DebugNode::class);
    });

    it('parses @debug with map expression', function () {
        $parser = ($this->createParser)('@debug (key: value, another: test);');
        $result = $parser->parse();

        expect($result)->toBeInstanceOf(DebugNode::class);
    });

    it('parses @debug with list expression', function () {
        $parser = ($this->createParser)('@debug 1, 2, 3;');
        $result = $parser->parse();

        expect($result)->toBeInstanceOf(DebugNode::class);
    });

    it('throws exception when not @debug rule', function () {
        $parser = ($this->createParser)('@warn "test";');

        expect(fn() => $parser->parse())->toThrow(
            SyntaxException::class,
            'Expected @debug rule, got @warn'
        );
    });

    it('throws exception for invalid at-rule', function () {
        $parser = ($this->createParser)('@mixin test {}');

        expect(fn() => $parser->parse())->toThrow(SyntaxException::class);
    });

    it('preserves line number from token', function () {
        $parser = ($this->createParser)('@debug $color;');
        $result = $parser->parse();

        expect($result->line)->toBeGreaterThan(0);
    });

    it('parses @debug with nested expression', function () {
        $parser = ($this->createParser)('@debug rgba(0, 0, 0, 0.5);');
        $result = $parser->parse();

        expect($result)->toBeInstanceOf(DebugNode::class);
    });

    it('parses @debug with function call', function () {
        $parser = ($this->createParser)('@debug darken(blue, 10%);');
        $result = $parser->parse();

        expect($result)->toBeInstanceOf(DebugNode::class);
    });

    it('parses @debug with boolean', function () {
        $parser = ($this->createParser)('@debug true;');
        $result = $parser->parse();

        expect($result)->toBeInstanceOf(DebugNode::class);
    });

    it('parses @debug with number', function () {
        $parser = ($this->createParser)('@debug 42;');
        $result = $parser->parse();

        expect($result)->toBeInstanceOf(DebugNode::class);
    });

    it('parses @debug with empty string', function () {
        $parser = ($this->createParser)('@debug "";');
        $result = $parser->parse();

        expect($result)->toBeInstanceOf(DebugNode::class)
            ->and($result->expression)->toBeInstanceOf(StringNode::class);
    });
});
