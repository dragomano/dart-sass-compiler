<?php

declare(strict_types=1);

use DartSass\Parsers\FunctionParser;
use DartSass\Parsers\Nodes\FunctionNode;
use DartSass\Parsers\Nodes\IdentifierNode;
use DartSass\Parsers\Nodes\MixinNode;
use DartSass\Parsers\Tokens\Lexer;

describe('FunctionParser', function () {
    beforeEach(function () {
        $this->lexer = new Lexer();

        $this->createParser = function (
            string $content,
            ?callable $parseBlock = null,
            ?callable $parseBinaryExpression = null
        ): FunctionParser {
            $stream = $this->lexer->tokenize($content);

            $parseBlock = $parseBlock ?? fn() => [
                'items'        => [],
                'declarations' => [],
                'nested'       => [],
            ];

            $parseBinaryExpression = $parseBinaryExpression ?? fn() => new IdentifierNode('default', 1);

            return new FunctionParser($stream, $parseBlock(...), $parseBinaryExpression(...));
        };
    });

    describe('parse()', function () {
        it('parses function declaration', function () {
            $parser = ($this->createParser)('@function test() {}');

            $result = $parser->parse();

            expect($result)->toBeInstanceOf(FunctionNode::class)
                ->and($result->name)->toBe('test');
        });

        it('parses function with no arguments', function () {
            $parser = ($this->createParser)('@function colors() {}');

            $result = $parser->parse();

            expect($result->name)->toBe('colors')
                ->and($result->args)->toHaveCount(0);
        });

        it('parses function with argument', function () {
            $parser = ($this->createParser)('@function test($arg) {}');

            $result = $parser->parse();

            expect($result->args)->toHaveCount(1)
                ->and($result->args[0]['name'])->toBe('$arg');
        });

        it('parses function with multiple arguments', function () {
            $parser = ($this->createParser)('@function test($a, $b, $c) {}');

            $result = $parser->parse();

            expect($result->args)->toHaveCount(3);
        });

        it('parses function with arbitrary argument', function () {
            $parser = ($this->createParser)('@function test($args...) {}');

            $result = $parser->parse();

            expect($result->args[0]['name'])->toBe('$args')
                ->and($result->args[0]['arbitrary'])->toBeTrue();
        });

        it('parses function with default value', function () {
            $parser = ($this->createParser)('@function test($color: red) {}');

            $result = $parser->parse();

            expect($result->args)->toHaveCount(2)
                ->and($result->args[0]['name'])->toBe('$color')
                ->and($result->args[0]['default'])->toBeInstanceOf(IdentifierNode::class)
                ->and($result->args[1]['name'])->toBe('red')
                ->and($result->args[1]['arbitrary'])->toBeFalse();
        });

        it('parses function with identifier argument', function () {
            $parser = ($this->createParser)('@function test(color) {}');

            $result = $parser->parse();

            expect($result->args)->toHaveCount(1)
                ->and($result->args[0]['name'])->toBe('color')
                ->and($result->args[0]['arbitrary'])->toBeFalse();
        });

        it('parses function with mixed arguments', function () {
            $parser = ($this->createParser)('@function test($a, color, $b: blue) {}');

            $result = $parser->parse();

            expect($result->args)->toHaveCount(4)
                ->and($result->args[0]['name'])->toBe('$a')
                ->and($result->args[1]['name'])->toBe('color')
                ->and($result->args[2]['name'])->toBe('$b')
                ->and($result->args[2]['default'])->toBeInstanceOf(IdentifierNode::class)
                ->and($result->args[3]['name'])->toBe('blue');
        });

        it('parses function name with hyphen', function () {
            $parser = ($this->createParser)('@function darken-color($color) {}');

            $result = $parser->parse();

            expect($result->name)->toBe('darken-color');
        });

        it('parses function name with underscore', function () {
            $parser = ($this->createParser)('@function get_color($name) {}');

            $result = $parser->parse();

            expect($result->name)->toBe('get_color');
        });
    });

    describe('parseFunction()', function () {
        it('returns FunctionNode instance', function () {
            $parser = ($this->createParser)('@function test() {}');

            $result = $parser->parseFunction();

            expect($result)->toBeInstanceOf(FunctionNode::class);
        });

        it('parses function body', function () {
            $parseBlock = fn() => [
                'items' => ['color: red'],
                'declarations' => [],
                'nested' => [],
            ];
            $parser = ($this->createParser)('@function test() { color: red; }', $parseBlock);

            $result = $parser->parseFunction();

            expect($result->body)->not->toBeEmpty();
        });
    });

    describe('parseMixin()', function () {
        it('parses mixin declaration', function () {
            $parser = ($this->createParser)('@mixin test {}');

            $result = $parser->parseMixin();

            expect($result)->toBeInstanceOf(MixinNode::class)
                ->and($result->name)->toBe('test');
        });

        it('parses mixin with no arguments', function () {
            $parser = ($this->createParser)('@mixin button-styles {}');

            $result = $parser->parseMixin();

            expect($result->name)->toBe('button-styles')
                ->and($result->args)->toHaveCount(0);
        });

        it('parses mixin with argument', function () {
            $parser = ($this->createParser)('@mixin test($color) {}');

            $result = $parser->parseMixin();

            expect($result->args)->toHaveKey('$color');
        });

        it('parses mixin with arbitrary argument', function () {
            $parser = ($this->createParser)('@mixin test($args...) {}');

            $result = $parser->parseMixin();

            expect($result->args)->toHaveKey('$args...');
        });

        it('parses mixin with multiple arguments', function () {
            $parser = ($this->createParser)('@mixin flex-center($justify, $align, $direction) {}');

            $result = $parser->parseMixin();

            expect($result->args)->toHaveCount(3);
        });

        it('returns MixinNode instance', function () {
            $parser = ($this->createParser)('@mixin test {}');

            $result = $parser->parseMixin();

            expect($result)->toBeInstanceOf(MixinNode::class);
        });

        it('parses mixin body', function () {
            $parseBlock = fn() => [
                'items' => [
                    new IdentifierNode('color', 1),
                ],
                'declarations' => [],
                'nested' => [],
            ];
            $parser = ($this->createParser)('@mixin test() { color: red; }', $parseBlock);

            $result = $parser->parseMixin();

            expect($result->body)->not->toBeEmpty();
        });
    });

    describe('parseNameAndInit()', function () {
        it('extracts function name from token', function () {
            $parser = ($this->createParser)('@function my-function() {}');

            $result = $parser->parse();

            expect($result->name)->toBe('my-function');
        });

        it('extracts mixin name from token', function () {
            $parser = ($this->createParser)('@mixin my-mixin {}');

            $result = $parser->parseMixin();

            expect($result->name)->toBe('my-mixin');
        });
    });

    describe('Error Handling', function () {
        it('throws exception for missing closing paren', function () {
            $parser = ($this->createParser)('@function test($arg {}');

            expect(fn() => $parser->parse())->toThrow(Exception::class);
        });

        it('throws exception for missing opening brace', function () {
            $parser = ($this->createParser)('@function test()');

            expect(fn() => $parser->parse())->toThrow(Exception::class);
        });

        it('throws exception for arbitrary argument not at end', function () {
            $parser = ($this->createParser)('@function test($args..., $extra) {}');

            expect(fn() => $parser->parse())->toThrow(Exception::class);
        });
    });

    describe('Complex Scenarios', function () {
        it('parses function with complex name', function () {
            $parser = ($this->createParser)('@function calculate-border-width($base, $multiplier) {}');

            $result = $parser->parse();

            expect($result->name)->toBe('calculate-border-width')
                ->and($result->args)->toHaveCount(2);
        });

        it('parses mixin with complex name', function () {
            $parser = ($this->createParser)('@mixin respond-to-breakpoint($breakpoint) {}');

            $result = $parser->parseMixin();

            expect($result->name)->toBe('respond-to-breakpoint');
        });

        it('parses function without body', function () {
            $parser = ($this->createParser)('@function empty-func() {}');

            $result = $parser->parse();

            expect($result->body)->toBeEmpty();
        });

        it('parses mixin without body', function () {
            $parser = ($this->createParser)('@mixin empty-mixin {}');

            $result = $parser->parseMixin();

            expect($result->body)->toBeEmpty();
        });
    });
});
