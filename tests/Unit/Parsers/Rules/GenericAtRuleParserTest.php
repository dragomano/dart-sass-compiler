<?php

declare(strict_types=1);

use DartSass\Exceptions\SyntaxException;
use DartSass\Parsers\BlockParser;
use DartSass\Parsers\ExpressionParser;
use DartSass\Parsers\Nodes\AtRuleNode;
use DartSass\Parsers\Nodes\RuleNode;
use DartSass\Parsers\Rules\GenericAtRuleParser;
use DartSass\Parsers\SelectorParser;
use DartSass\Parsers\Tokens\Lexer;
use Tests\ReflectionAccessor;

describe('GenericAtRuleParser', function () {
    beforeEach(function () {
        $this->lexer = new Lexer();

        $this->createParser = function (string $content) {
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

            $parseAtRuleClosure = Closure::bind(
                fn() => $blockParser->parseAtRule(),
                $blockParser,
                BlockParser::class
            );

            return new GenericAtRuleParser(
                $stream,
                $parseAtRuleClosure,
                fn() => $blockParser->parseVariable(),
                fn() => $blockParser->parseRule(),
                fn() => $blockParser->parseDeclaration()
            );
        };
    });

    it('parses basic at-rule with semicolon', function () {
        $parser = ($this->createParser)('@charset "UTF-8";');
        $result = $parser->parse();

        expect($result)->toBeInstanceOf(AtRuleNode::class)
            ->and($result->name)->toBe('@charset')
            ->and($result->value)->toBe('"UTF-8"')
            ->and($result->body)->toBeNull();
    });

    it('parses at-rule with value and semicolon', function () {
        $parser = ($this->createParser)('@import url("styles.css");');
        $result = $parser->parse();

        expect($result)->toBeInstanceOf(AtRuleNode::class)
            ->and($result->value)->toBe('url("styles.css")');
    });

    it('parses at-rule with block content', function () {
        $parser = ($this->createParser)('@media screen { color: red; }');
        $result = $parser->parse();

        expect($result)->toBeInstanceOf(AtRuleNode::class)
            ->and($result->name)->toBe('@media')
            ->and($result->value)->toBe('screen')
            ->and($result->body)->not->toBeNull()
            ->and($result->body['declarations'])->toHaveCount(1);
    });

    it('parses at-rule with complex value', function () {
        $parser = ($this->createParser)('@supports (display: grid) and (gap: 10px) { display: grid; }');
        $result = $parser->parse();

        expect($result)->toBeInstanceOf(AtRuleNode::class)
            ->and($result->name)->toBe('@supports')
            ->and($result->value)->toBe('(display:grid)and(gap:10px)');
    });

    it('throws exception when neither brace nor semicolon follows', function () {
        $parser = ($this->createParser)('@unknown-rule');

        expect(fn() => $parser->parse())->toThrow(
            SyntaxException::class,
            'Expected "{" or ";" after @unknown-rule'
        );
    });

    it('handles at-rule with only name', function () {
        $parser = ($this->createParser)('@custom {}');
        $result = $parser->parse();

        expect($result)->toBeInstanceOf(AtRuleNode::class)
            ->and($result->value)->toBe('');
    });

    it('validates block parsing with reflection', function () {
        $parser = ($this->createParser)('@test-rule { color: red; }');

        $accessor = new ReflectionAccessor($parser);
        $result   = $accessor->callMethod('parse');

        expect($result)->toBeInstanceOf(AtRuleNode::class)
            ->and($result->body['declarations'])->toHaveCount(1);
    });

    it('handles at-rule with vendor prefix', function () {
        $parser = ($this->createParser)('@-webkit-keyframes slide { from { left: 0; } to { left: 100px; } }');
        $result = $parser->parse();

        expect($result)->toBeInstanceOf(AtRuleNode::class)
            ->and($result->name)->toBe('@-webkit-keyframes')
            ->and($result->value)->toBe('slide');
    });

    it('parses at-rule with nested selector rule', function () {
        $parser = ($this->createParser)('@media screen { .test-class { color: blue; } }');
        $result = $parser->parse();

        expect($result)->toBeInstanceOf(AtRuleNode::class)
            ->and($result->body)->not->toBeNull()
            ->and($result->body['nested'])->toHaveCount(1)
            ->and($result->body['nested'][0])->toBeInstanceOf(RuleNode::class)
            ->and($result->body['nested'][0]->selector->value)->toBe('.test-class')
            ->and($result->body['declarations'])->toHaveCount(0);
    });

    it('parses at-rule with operator-starting rule', function () {
        $parser = ($this->createParser)('@media screen { + .modifier { color: green; } }');
        $result = $parser->parse();

        expect($result)->toBeInstanceOf(AtRuleNode::class)
            ->and($result->body['nested'])->toHaveCount(1)
            ->and($result->body['declarations'])->toHaveCount(0);
    });

    it('parses at-rule with direct declaration (fallback case)', function () {
        $parser = ($this->createParser)('@media screen { color: red; }');
        $result = $parser->parse();

        expect($result)->toBeInstanceOf(AtRuleNode::class)
            ->and($result->body['declarations'])->toHaveCount(1)
            ->and($result->body['nested'])->toHaveCount(0);
    });
});
