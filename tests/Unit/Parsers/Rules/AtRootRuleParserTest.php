<?php

declare(strict_types=1);

use DartSass\Exceptions\SyntaxException;
use DartSass\Parsers\BlockParser;
use DartSass\Parsers\ExpressionParser;
use DartSass\Parsers\Nodes\NodeType;
use DartSass\Parsers\Rules\AtRootRuleParser;
use DartSass\Parsers\SelectorParser;
use DartSass\Parsers\Tokens\Lexer;

describe('AtRootRuleParser', function () {
    beforeEach(function () {
        $this->lexer = new Lexer();

        $this->createParser = function (string $content): AtRootRuleParser {
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

            $parseAtRuleClosure = Closure::bind(fn() => $blockParser->parseAtRule(), $blockParser, BlockParser::class);

            return new AtRootRuleParser(
                $stream,
                $parseAtRuleClosure,
                fn() => $blockParser->parseInclude(),
                fn() => $blockParser->parseVariable(),
                fn() => $blockParser->parseRule(),
                fn() => $blockParser->parseDeclaration()
            );
        };
    });

    describe('parse()', function () {
        it('parses basic @at-root rule', function () {
            $parser = ($this->createParser)('@at-root { .child { color: red; } }');
            $result = $parser->parse();

            expect($result->type)->toBe(NodeType::AT_ROOT)
                ->and($result->without)->toBeNull()
                ->and($result->with)->toBeNull()
                ->and($result->body['nested'])->toHaveCount(1);
        });

        it('parses @at-root without context', function () {
            $parser = ($this->createParser)('@at-root (without: media) { .child { color: red; } }');
            $result = $parser->parse();

            expect($result->without)->toBe('media')
                ->and($result->with)->toBeNull();
        });

        it('parses @at-root with context', function () {
            $parser = ($this->createParser)('@at-root (with: rule) { .child { color: red; } }');
            $result = $parser->parse();

            expect($result->without)->toBeNull()
                ->and($result->with)->toBe('rule');
        });

        it('parses @at-root with nested declarations', function () {
            $parser = ($this->createParser)('@at-root { .child { color: red; font-size: 14px; } }');
            $result = $parser->parse();

            expect($result->body['nested'])->toHaveCount(1)
                ->and($result->body['nested'][0]->declarations)->toHaveCount(2);
        });

        it('parses @at-root with multiple nested rules', function () {
            $parser = ($this->createParser)('@at-root { .one { color: red; } .two { color: blue; } }');
            $result = $parser->parse();

            expect($result->body['nested'])->toHaveCount(2);
        });

        it('preserves line number', function () {
            $parser = ($this->createParser)('@at-root { .child { color: red; } }');
            $result = $parser->parse();

            expect($result->line)->toBeGreaterThan(0);
        });

        it('handles empty @at-root body', function () {
            $parser = ($this->createParser)('@at-root {}');
            $result = $parser->parse();

            expect($result->body['declarations'])->toHaveCount(0)
                ->and($result->body['nested'])->toHaveCount(0);
        });

        it('parses @at-root with variables', function () {
            $parser = ($this->createParser)('@at-root { $primary: #ff0000; }');
            $result = $parser->parse();

            expect($result->body['nested'])->toHaveCount(1)
                ->and($result->body['nested'][0]->name)->toBe('$primary');
        });

        it('parses @at-root with @include', function () {
            $parser = ($this->createParser)('@at-root { @include mixin-name; }');
            $result = $parser->parse();

            expect($result->body['nested'])->toHaveCount(1)
                ->and($result->body['nested'][0]->name)->toBe('mixin-name');
        });

        it('throws SyntaxException for invalid at-rule', function () {
            $parser = ($this->createParser)('@invalid { .child { color: red; } }');

            expect(fn() => $parser->parse())->toThrow(SyntaxException::class);
        });
    });
});
