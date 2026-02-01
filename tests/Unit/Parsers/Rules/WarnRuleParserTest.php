<?php

declare(strict_types=1);

use DartSass\Exceptions\SyntaxException;
use DartSass\Parsers\ExpressionParser;
use DartSass\Parsers\Nodes\StringNode;
use DartSass\Parsers\Nodes\VariableNode;
use DartSass\Parsers\Nodes\WarnNode;
use DartSass\Parsers\Rules\WarnRuleParser;
use DartSass\Parsers\Tokens\Lexer;

describe('WarnRuleParser', function () {
    beforeEach(function () {
        $this->lexer = new Lexer();

        $this->createParser = function (string $content) {
            $tokenStream = $this->lexer->tokenize($content);

            $expressionParser = new ExpressionParser($tokenStream);
            $parseExpression  = fn() => $expressionParser->parse();

            return new WarnRuleParser($tokenStream, $parseExpression);
        };
    });

    it('parses @warn with string message', function () {
        $parser = ($this->createParser)('@warn "This is a warning";');
        $result = $parser->parse();

        expect($result)->toBeInstanceOf(WarnNode::class)
            ->and($result->expression)->toBeInstanceOf(StringNode::class);
    });

    it('parses @warn with variable', function () {
        $parser = ($this->createParser)('@warn $warning-message;');
        $result = $parser->parse();

        expect($result)->toBeInstanceOf(WarnNode::class)
            ->and($result->expression)->toBeInstanceOf(VariableNode::class);
    });

    it('parses @warn without semicolon', function () {
        $parser = ($this->createParser)('@warn "Check this"');
        $result = $parser->parse();

        expect($result)->toBeInstanceOf(WarnNode::class);
    });

    it('parses @warn with interpolated string', function () {
        $parser = ($this->createParser)('@warn "Value #{$value} is deprecated";');
        $result = $parser->parse();

        expect($result)->toBeInstanceOf(WarnNode::class);
    });

    it('parses @warn with function call', function () {
        $parser = ($this->createParser)('@warn "Deprecated function: " + function-name($arg);');
        $result = $parser->parse();

        expect($result)->toBeInstanceOf(WarnNode::class);
    });

    it('throws exception when not @warn rule', function () {
        $parser = ($this->createParser)('@error "test";');

        expect(fn() => $parser->parse())->toThrow(
            SyntaxException::class,
            'Expected @warn rule, got @error'
        );
    });

    it('throws exception for invalid at-rule', function () {
        $parser = ($this->createParser)('@for $i from 1 to 10 {}');

        expect(fn() => $parser->parse())->toThrow(SyntaxException::class);
    });

    it('preserves line number from token', function () {
        $parser = ($this->createParser)('@warn "Test warning";');
        $result = $parser->parse();

        expect($result->line)->toBeGreaterThan(0);
    });

    it('parses @warn with complex message', function () {
        $parser = ($this->createParser)('@warn "Property #{$prop} will be removed in next version";');
        $result = $parser->parse();

        expect($result)->toBeInstanceOf(WarnNode::class);
    });

    it('parses @warn with concatenation', function () {
        $parser = ($this->createParser)('@warn "Warning: " + $message;');
        $result = $parser->parse();

        expect($result)->toBeInstanceOf(WarnNode::class);
    });

    it('parses @warn with number', function () {
        $parser = ($this->createParser)('@warn $warning-level;');
        $result = $parser->parse();

        expect($result)->toBeInstanceOf(WarnNode::class);
    });

    it('parses @warn with boolean', function () {
        $parser = ($this->createParser)('@warn $show-warning;');
        $result = $parser->parse();

        expect($result)->toBeInstanceOf(WarnNode::class);
    });

    it('parses @warn with empty string', function () {
        $parser = ($this->createParser)('@warn "";');
        $result = $parser->parse();

        expect($result)->toBeInstanceOf(WarnNode::class)
            ->and($result->expression)->toBeInstanceOf(StringNode::class);
    });

    it('parses @warn with list expression', function () {
        $parser = ($this->createParser)('@warn $list;');
        $result = $parser->parse();

        expect($result)->toBeInstanceOf(WarnNode::class);
    });

    it('parses @warn with map expression', function () {
        $parser = ($this->createParser)('@warn (deprecated: true, message: "Use new API");');
        $result = $parser->parse();

        expect($result)->toBeInstanceOf(WarnNode::class);
    });

    it('parses @warn with multiple expressions', function () {
        $parser = ($this->createParser)('@warn $msg1 $msg2;');
        $result = $parser->parse();

        expect($result)->toBeInstanceOf(WarnNode::class);
    });
});
