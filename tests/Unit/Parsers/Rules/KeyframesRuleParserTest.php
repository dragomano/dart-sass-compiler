<?php

declare(strict_types=1);

use DartSass\Exceptions\SyntaxException;
use DartSass\Parsers\ExpressionParser;
use DartSass\Parsers\Nodes\KeyframesNode;
use DartSass\Parsers\Rules\KeyframesRuleParser;
use DartSass\Parsers\Tokens\Lexer;
use Tests\ReflectionAccessor;

describe('KeyframesRuleParser', function () {
    beforeEach(function () {
        $this->lexer = new Lexer();

        $this->createParser = function (string $content) {
            $tokenStream = $this->lexer->tokenize($content);

            $expressionParser = new ExpressionParser($tokenStream);
            $parseExpression  = fn() => $expressionParser->parse();

            return new KeyframesRuleParser($tokenStream, $parseExpression);
        };
    });

    it('parses basic keyframes rule with animation name', function () {
        $parser = ($this->createParser)('@keyframes slide { from { left: 0; } to { left: 100px; } }');

        $result = $parser->parse();

        expect($result)->toBeInstanceOf(KeyframesNode::class)
            ->and($result->name)->toBe('slide')
            ->and($result->keyframes)->toHaveCount(2);
    });

    it('parses keyframes with vendor prefix', function () {
        $parser = ($this->createParser)('@-webkit-keyframes fadeIn { 0% { opacity: 0; } 100% { opacity: 1; } }');

        $result = $parser->parse();

        expect($result)->toBeInstanceOf(KeyframesNode::class)
            ->and($result->name)->toBe('fadeIn')
            ->and($result->keyframes)->toHaveCount(2);
    });

    it('parses keyframes with percentage selectors', function () {
        $parser = ($this->createParser)('@keyframes bounce { 0%, 20%, 50%, 80%, 100% { transform: translateY(0); } 40% { transform: translateY(-30px); } 60% { transform: translateY(-15px); } }');

        $result = $parser->parse();

        expect($result)->toBeInstanceOf(KeyframesNode::class)
            ->and($result->keyframes)->toHaveCount(3);
    });

    it('parses keyframes with keyword selectors', function () {
        $parser = ($this->createParser)('@keyframes fade { from { opacity: 0; } to { opacity: 1; } }');

        $result = $parser->parse();

        expect($result)->toBeInstanceOf(KeyframesNode::class)
            ->and($result->keyframes[0]['selectors'])->toContain('from')
            ->and($result->keyframes[1]['selectors'])->toContain('to');
    });

    it('parses keyframes with mixed selectors', function () {
        $parser = ($this->createParser)('@keyframes rotate { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }');

        $result = $parser->parse();

        expect($result)->toBeInstanceOf(KeyframesNode::class)
            ->and($result->keyframes[0]['selectors'])->toContain('from')
            ->and($result->keyframes[1]['selectors'])->toContain('to');
    });

    it('parses keyframes with multiple declarations per keyframe', function () {
        $parser = ($this->createParser)('@keyframes complex { 0% { opacity: 0; transform: scale(0.5); } 100% { opacity: 1; transform: scale(1); } }');

        $result = $parser->parse();

        expect($result)->toBeInstanceOf(KeyframesNode::class)
            ->and($result->keyframes[0]['declarations'])->toHaveCount(2)
            ->and($result->keyframes[1]['declarations'])->toHaveCount(2);
    });

    it('throws exception when opening brace is missing', function () {
        $parser = ($this->createParser)('@keyframes slide from { left: 0; } to { left: 100px; }');

        expect(fn() => $parser->parse())->toThrow(SyntaxException::class);
    });

    it('throws exception when closing brace is missing', function () {
        $parser = ($this->createParser)('@keyframes slide { from { left: 0; } to { left: 100px; }');

        expect(fn() => $parser->parse())->toThrow(SyntaxException::class);
    });

    it('handles keyframes with decimal percentages', function () {
        $parser = ($this->createParser)('@keyframes smooth { 0.5% { opacity: 0.1; } 99.5% { opacity: 0.9; } }');

        $result = $parser->parse();

        expect($result)->toBeInstanceOf(KeyframesNode::class)
            ->and($result->keyframes[0]['selectors'])->toContain('0.5%')
            ->and($result->keyframes[1]['selectors'])->toContain('99.5%');
    });

    it('handles keyframes with complex property values', function () {
        $parser = ($this->createParser)('@keyframes animate { 0% { transform: translateX(0) rotate(0deg); } 100% { transform: translateX(100px) rotate(360deg); } }');

        $result = $parser->parse();

        expect($result)->toBeInstanceOf(KeyframesNode::class)
            ->and($result->keyframes[0]['declarations'])->toHaveCount(1)
            ->and($result->keyframes[1]['declarations'])->toHaveCount(1);
    });

    it('validates keyframes parsing with reflection', function () {
        $parser = ($this->createParser)('@keyframes test { 0% { width: 0px; } 100% { width: 100px; } }');

        $accessor = new ReflectionAccessor($parser);
        $result   = $accessor->callMethod('parse');

        expect($result)->toBeInstanceOf(KeyframesNode::class)
            ->and($result->keyframes)->toHaveCount(2);
    });

    it('validates keyframe selectors parsing with reflection', function () {
        $parser = ($this->createParser)('@keyframes selectors { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 0; } }');

        $accessor = new ReflectionAccessor($parser);
        $result   = $accessor->callMethod('parse');

        expect($result)->toBeInstanceOf(KeyframesNode::class)
            ->and($result->keyframes)->toHaveCount(3);
    });

    it('validates keyframe declarations parsing with reflection', function () {
        $parser = ($this->createParser)('@keyframes props { 50% { color: red; background: blue; margin: 10px; } }');

        $accessor = new ReflectionAccessor($parser);
        $result   = $accessor->callMethod('parse');

        expect($result)->toBeInstanceOf(KeyframesNode::class)
            ->and($result->keyframes[0]['declarations'])->toHaveCount(3);
    });

    it('handles empty keyframes rule', function () {
        $parser = ($this->createParser)('@keyframes empty {}');

        $result = $parser->parse();

        expect($result)->toBeInstanceOf(KeyframesNode::class)
            ->and($result->keyframes)->toBe([]);
    });

    it('handles keyframes with single selector and declaration', function () {
        $parser = ($this->createParser)('@keyframes simple { 100% { display: block; } }');

        $result = $parser->parse();

        expect($result)->toBeInstanceOf(KeyframesNode::class)
            ->and($result->keyframes)->toHaveCount(1)
            ->and($result->keyframes[0]['selectors'])->toContain('100%')
            ->and($result->keyframes[0]['declarations'])->toHaveCount(1);
    });

    it('handles keyframes with whitespace variations', function () {
        $parser = ($this->createParser)('@keyframes   spaced   {   0%   {   opacity:   0;   }   100%   {   opacity:   1;   }   }');

        $result = $parser->parse();

        expect($result)->toBeInstanceOf(KeyframesNode::class)
            ->and($result->name)->toBe('spaced')
            ->and($result->keyframes)->toHaveCount(2);
    });
});
