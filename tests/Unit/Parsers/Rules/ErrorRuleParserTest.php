<?php

declare(strict_types=1);

use DartSass\Exceptions\SyntaxException;
use DartSass\Parsers\ExpressionParser;
use DartSass\Parsers\Nodes\ErrorNode;
use DartSass\Parsers\Nodes\StringNode;
use DartSass\Parsers\Nodes\VariableNode;
use DartSass\Parsers\Rules\ErrorRuleParser;
use DartSass\Parsers\Tokens\Lexer;

describe('ErrorRuleParser', function () {
    beforeEach(function () {
        $this->lexer = new Lexer();

        $this->createParser = function (string $content) {
            $tokenStream = $this->lexer->tokenize($content);

            $expressionParser = new ExpressionParser($tokenStream);
            $parseExpression  = fn() => $expressionParser->parse();

            return new ErrorRuleParser($tokenStream, $parseExpression);
        };
    });

    it('parses @error with string message', function () {
        $parser = ($this->createParser)('@error "Something went wrong";');
        $result = $parser->parse();

        expect($result)->toBeInstanceOf(ErrorNode::class)
            ->and($result->expression)->toBeInstanceOf(StringNode::class);
    });

    it('parses @error with variable', function () {
        $parser = ($this->createParser)('@error $error-message;');
        $result = $parser->parse();

        expect($result)->toBeInstanceOf(ErrorNode::class)
            ->and($result->expression)->toBeInstanceOf(VariableNode::class);
    });

    it('parses @error without semicolon', function () {
        $parser = ($this->createParser)('@error "Fatal error"');
        $result = $parser->parse();

        expect($result)->toBeInstanceOf(ErrorNode::class);
    });

    it('parses @error with interpolated string', function () {
        $parser = ($this->createParser)('@error "Expected #{$expected} but got #{$actual}";');
        $result = $parser->parse();

        expect($result)->toBeInstanceOf(ErrorNode::class);
    });

    it('parses @error with function call', function () {
        $parser = ($this->createParser)('@error type-of($value) + " is not valid";');
        $result = $parser->parse();

        expect($result)->toBeInstanceOf(ErrorNode::class);
    });

    it('throws exception when not @error rule', function () {
        $parser = ($this->createParser)('@debug "test";');

        expect(fn() => $parser->parse())->toThrow(
            SyntaxException::class,
            'Expected @error rule, got @debug'
        );
    });

    it('throws exception for invalid at-rule', function () {
        $parser = ($this->createParser)('@if $condition {}');

        expect(fn() => $parser->parse())->toThrow(SyntaxException::class);
    });

    it('preserves line number from token', function () {
        $parser = ($this->createParser)('@error "Test error";');
        $result = $parser->parse();

        expect($result->line)->toBeGreaterThan(0);
    });

    it('parses @error with complex message', function () {
        $parser = ($this->createParser)('@error "Value must be positive, got #{$value}";');
        $result = $parser->parse();

        expect($result)->toBeInstanceOf(ErrorNode::class);
    });

    it('parses @error with concatenation', function () {
        $parser = ($this->createParser)('@error "File not found: " + $filename;');
        $result = $parser->parse();

        expect($result)->toBeInstanceOf(ErrorNode::class);
    });

    it('parses @error with number', function () {
        $parser = ($this->createParser)('@error $error-code;');
        $result = $parser->parse();

        expect($result)->toBeInstanceOf(ErrorNode::class);
    });

    it('parses @error with boolean', function () {
        $parser = ($this->createParser)('@error $is-invalid;');
        $result = $parser->parse();

        expect($result)->toBeInstanceOf(ErrorNode::class);
    });

    it('parses @error with empty string', function () {
        $parser = ($this->createParser)('@error "";');
        $result = $parser->parse();

        expect($result)->toBeInstanceOf(ErrorNode::class)
            ->and($result->expression)->toBeInstanceOf(StringNode::class);
    });

    it('parses @error with map expression', function () {
        $parser = ($this->createParser)('@error (error: "Invalid", code: 404);');
        $result = $parser->parse();

        expect($result)->toBeInstanceOf(ErrorNode::class);
    });
});
