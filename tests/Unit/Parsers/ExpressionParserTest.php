<?php

declare(strict_types=1);

use DartSass\Exceptions\SyntaxException;
use DartSass\Parsers\ExpressionParser;
use DartSass\Parsers\Nodes\FunctionNode;
use DartSass\Parsers\Nodes\HexColorNode;
use DartSass\Parsers\Nodes\IdentifierNode;
use DartSass\Parsers\Nodes\InterpolationNode;
use DartSass\Parsers\Nodes\ListNode;
use DartSass\Parsers\Nodes\NodeType;
use DartSass\Parsers\Nodes\NumberNode;
use DartSass\Parsers\Nodes\OperationNode;
use DartSass\Parsers\Nodes\OperatorNode;
use DartSass\Parsers\Nodes\StringNode;
use DartSass\Parsers\Nodes\VariableNode;
use DartSass\Parsers\Tokens\Lexer;
use DartSass\Parsers\Tokens\TokenStream;
use Tests\ReflectionAccessor;

describe('ExpressionParser', function () {
    beforeEach(function () {
        $this->lexer = new Lexer();

        $this->createParser = function (string $content): ExpressionParser {
            $stream = $this->lexer->tokenize($content);

            return new ExpressionParser($stream);
        };
    });

    describe('parse()', function () {
        it('parses identifier', function () {
            $parser = ($this->createParser)('red');

            $result = $parser->parse();

            expect($result->value)->toBe('red');
        });

        it('parses number', function () {
            $parser = ($this->createParser)('42');

            $result = $parser->parse();

            expect($result->value)->toBe(42.0);
        });

        it('parses negative number', function () {
            $parser = ($this->createParser)('-10');

            $result = $parser->parse();

            expect($result->value)->toBe(-10.0);
        });

        it('parses decimal number', function () {
            $parser = ($this->createParser)('3.14');

            $result = $parser->parse();

            expect($result->value)->toBe(3.14);
        });

        it('parses string with double quotes', function () {
            $parser = ($this->createParser)('"hello"');

            $result = $parser->parse();

            expect($result->value)->toBe('hello');
        });

        it('parses string with single quotes', function () {
            $parser = ($this->createParser)("'world'");

            $result = $parser->parse();

            expect($result->value)->toBe('world');
        });

        it('parses variable', function () {
            $parser = ($this->createParser)('$color');

            $result = $parser->parse();

            expect($result->type)->toBe(NodeType::VARIABLE)
                ->and($result->name)->toBe('$color');
        });

        it('parses hex color', function () {
            $parser = ($this->createParser)('#ff0000');

            $result = $parser->parse();

            expect($result->type)->toBe(NodeType::HEX_COLOR);
        });

        it('parses list with comma separator', function () {
            $parser = ($this->createParser)('red, blue, green;');

            $result = $parser->parse();

            expect($result->type)->toBe(NodeType::LIST)
                ->and($result->separator)->toBe('comma');
        });

        it('returns left when identifier is null', function () {
            $parser = ($this->createParser)('$var null');

            $result = $parser->parse();

            expect($result->type)->toBe(NodeType::VARIABLE);
        });

        it('breaks list parsing on brace open', function () {
            $parser = ($this->createParser)('$var, $other { color: red }');

            $result = $parser->parse();

            expect($result->type)->toBe(NodeType::LIST)
                ->and($result->separator)->toBe('comma')
                ->and($result->values)->toHaveCount(2);
        });

        it('returns variable when followed by colon', function () {
            $parser = ($this->createParser)('$var: value;');

            $result = $parser->parse();

            expect($result->type)->toBe(NodeType::VARIABLE);
        });

        it('breaks space separated list on block end token after whitespace', function () {
            $parser = ($this->createParser)('$a $b ;');

            $result = $parser->parse();

            expect($result->type)->toBe(NodeType::LIST)
                ->and($result->separator)->toBe('space')
                ->and($result->values)->toHaveCount(2);
        });

        it('returns left when block end after skipWhitespace in else', function () {
            $parser = ($this->createParser)('$a  ;');

            $result = $parser->parse();

            expect($result->type)->toBe(NodeType::VARIABLE);
        });

        it('breaks argument list on paren close after whitespace', function () {
            $parser = ($this->createParser)('  )');

            $result = $parser->parseArgumentList();

            expect($result)->toHaveCount(0);
        });

        it('parses list with space separator', function () {
            $parser = ($this->createParser)('10px 20px 30px');

            $result = $parser->parse();

            expect($result->type)->toBe(NodeType::LIST)
                ->and($result->separator)->toBe('space');
        });

        it('parses parenthesized expression', function () {
            $parser = ($this->createParser)('(1 + 2)');

            $result = $parser->parse();

            expect($result->type)->toBe(NodeType::OPERATION);
        });
    });

    describe('parseBinaryExpression()', function () {
        it('parses addition', function () {
            $parser = ($this->createParser)('10 + 20');

            $result = $parser->parse();

            expect($result->type)->toBe(NodeType::OPERATION);
        });

        it('parses subtraction', function () {
            $parser = ($this->createParser)('100 - 50');

            $result = $parser->parse();

            expect($result->type)->toBe(NodeType::OPERATION);
        });

        it('parses multiplication', function () {
            $parser = ($this->createParser)('5 * 3');

            $result = $parser->parse();

            expect($result->type)->toBe(NodeType::OPERATION);
        });

        it('parses division', function () {
            $parser = ($this->createParser)('100 / 4');

            $result = $parser->parse();

            expect($result->type)->toBe(NodeType::OPERATION);
        });

        it('parses modulo', function () {
            $parser = ($this->createParser)('10 % 3');

            $result = $parser->parse();

            expect($result->type)->toBe(NodeType::OPERATION);
        });

        it('parses equality comparison', function () {
            $parser = ($this->createParser)('$a == $b');

            $result = $parser->parse();

            expect($result->type)->toBe(NodeType::OPERATION);
        });

        it('parses inequality comparison', function () {
            $parser = ($this->createParser)('$a != $b');

            $result = $parser->parse();

            expect($result->type)->toBe(NodeType::OPERATION);
        });

        it('parses less than comparison', function () {
            $parser = ($this->createParser)('$a < $b');

            $result = $parser->parse();

            expect($result->type)->toBe(NodeType::OPERATION);
        });

        it('parses greater than comparison', function () {
            $parser = ($this->createParser)('$a > $b');

            $result = $parser->parse();

            expect($result->type)->toBe(NodeType::OPERATION);
        });
    });

    describe('parsePrimaryExpression()', function () {
        it('parses number', function () {
            $parser = ($this->createParser)('42');

            $result = $parser->parsePrimaryExpression();

            expect($result)->toBeInstanceOf(NumberNode::class);
        });

        it('parses identifier', function () {
            $parser = ($this->createParser)('red');

            $result = $parser->parsePrimaryExpression();

            expect($result)->toBeInstanceOf(IdentifierNode::class);
        });

        it('parses variable', function () {
            $parser = ($this->createParser)('$color');

            $result = $parser->parsePrimaryExpression();

            expect($result)->toBeInstanceOf(VariableNode::class);
        });

        it('parses string', function () {
            $parser = ($this->createParser)('"hello"');

            $result = $parser->parsePrimaryExpression();

            expect($result)->toBeInstanceOf(StringNode::class);
        });

        it('parses hex color', function () {
            $parser = ($this->createParser)('#ff0000');

            $result = $parser->parsePrimaryExpression();

            expect($result)->toBeInstanceOf(HexColorNode::class);
        });

        it('parses function call', function () {
            $parser = ($this->createParser)('rgb(255, 0, 0)');

            $result = $parser->parsePrimaryExpression();

            expect($result)->toBeInstanceOf(FunctionNode::class)
                ->and($result->name)->toBe('rgb');
        });

        it('parses url function', function () {
            $parser = ($this->createParser)('url(image.png)');

            $result = $parser->parsePrimaryExpression();

            expect($result)->toBeInstanceOf(FunctionNode::class)
                ->and($result->name)->toBe('url');
        });

        it('parses parenthesized expression', function () {
            $parser = ($this->createParser)('(1 + 2)');

            $result = $parser->parsePrimaryExpression();

            expect($result)->toBeInstanceOf(OperationNode::class);
        });

        it('parses interpolation', function () {
            $parser = ($this->createParser)('#{$var}');

            $result = $parser->parsePrimaryExpression();

            expect($result)->toBeInstanceOf(InterpolationNode::class);
        });

        it('parses attribute selector', function () {
            $parser = ($this->createParser)('[attr=value]');

            $result = $parser->parsePrimaryExpression();

            expect($result)->toBeInstanceOf(ListNode::class);
        });

        it('covers ternary operator for numeric values without units', function () {
            $parser = ($this->createParser)('[width="100"]');

            $result = $parser->parsePrimaryExpression();

            expect($result)->toBeInstanceOf(ListNode::class)
                ->and($result->bracketed)->toBeTrue();
        });

        it('parses operator', function () {
            $parser = ($this->createParser)('+');

            $result = $parser->parsePrimaryExpression();

            expect($result)->toBeInstanceOf(OperatorNode::class);
        });

        it('parses asterisk', function () {
            $parser = ($this->createParser)('*');

            $result = $parser->parsePrimaryExpression();

            expect($result)->toBeInstanceOf(OperatorNode::class);
        });

        it('parses colon', function () {
            $parser = ($this->createParser)(':');

            $result = $parser->parsePrimaryExpression();

            expect($result)->toBeInstanceOf(OperatorNode::class);
        });

        it('parses semicolon', function () {
            $parser = ($this->createParser)(';');

            $result = $parser->parsePrimaryExpression();

            expect($result)->toBeInstanceOf(OperatorNode::class);
        });

        it('parses important modifier', function () {
            $parser = ($this->createParser)('!important');

            $result = $parser->parsePrimaryExpression();

            expect($result)->toBeInstanceOf(IdentifierNode::class)
                ->and($result->value)->toBe('!important');
        });

        it('returns string representation when operation is not division', function () {
            $parser = ($this->createParser)('10px + 20px');

            $result = $parser->parse();

            expect($result->type)->toBe(NodeType::OPERATION)
                ->and($result->operator)->toBe('+');
        });
    });

    describe('parseArgumentList()', function () {
        it('parses single argument', function () {
            $parser = ($this->createParser)('arg1)');

            $result = $parser->parseArgumentList();

            expect($result)->toHaveCount(1);
        });

        it('parses multiple arguments', function () {
            $parser = ($this->createParser)('arg1, arg2, arg3)');

            $result = $parser->parseArgumentList();

            expect($result)->toHaveCount(3);
        });

        it('parses empty argument list', function () {
            $parser = ($this->createParser)(')');

            $result = $parser->parseArgumentList();

            expect($result)->toHaveCount(0);
        });

        it('parses named argument', function () {
            $parser = ($this->createParser)('$name: value)');

            $result = $parser->parseArgumentList();

            expect($result)->toHaveKey('$name');
        });

        it('validates spread operator position constraint', function () {
            $parser = ($this->createParser)('mix($colors..., red)');
            expect(fn() => $parser->parse())->toThrow(
                SyntaxException::class,
                'Spread operator (...) must be the last argument'
            );
        });
    });

    describe('Unary Operators', function () {
        it('parses not operator', function () {
            $parser = ($this->createParser)('not true');

            $result = $parser->parse();

            expect($result->type)->toBe(NodeType::UNARY);
        });
    });

    describe('Property Access', function () {
        it('parses property access', function () {
            $parser = ($this->createParser)('$colors.primary');

            $result = $parser->parse();

            expect($result->type)->toBe(NodeType::PROPERTY_ACCESS);
        });
    });

    describe('Function Calls', function () {
        it('parses function with no arguments', function () {
            $parser = ($this->createParser)('rgb()');

            $result = $parser->parse();

            expect($result->type)->toBe(NodeType::FUNCTION);
        });

        it('parses function with arguments', function () {
            $parser = ($this->createParser)('rgba(255, 0, 0, 0.5)');

            $result = $parser->parse();

            expect($result->type)->toBe(NodeType::FUNCTION)
                ->and($result->args)->toHaveCount(4);
        });

        it('parses calc function', function () {
            $parser = ($this->createParser)('calc(100% - 20px)');

            $result = $parser->parse();

            expect($result->type)->toBe(NodeType::FUNCTION);
        });
    });

    describe('CSS Values', function () {
        it('parses pixel value', function () {
            $parser = ($this->createParser)('10px');

            $result = $parser->parse();

            expect($result->value)->toBe(10.0)
                ->and($result->unit)->toBe('px');
        });

        it('parses percentage value', function () {
            $parser = ($this->createParser)('50%');

            $result = $parser->parse();

            expect($result->value)->toBe(50.0)
                ->and($result->unit)->toBe('%');
        });

        it('parses em value', function () {
            $parser = ($this->createParser)('1.5em');

            $result = $parser->parse();

            expect($result->value)->toBe(1.5)
                ->and($result->unit)->toBe('em');
        });

        it('parses rem value', function () {
            $parser = ($this->createParser)('2rem');

            $result = $parser->parse();

            expect($result->value)->toBe(2.0)
                ->and($result->unit)->toBe('rem');
        });
    });

    describe('Complex Expressions', function () {
        it('parses nested operations', function () {
            $parser = ($this->createParser)('1 + 2 * 3');

            $result = $parser->parse();

            expect($result->type)->toBe(NodeType::OPERATION);
        });

        it('parses operations with parentheses', function () {
            $parser = ($this->createParser)('(1 + 2) * 3');

            $result = $parser->parse();

            expect($result->type)->toBe(NodeType::OPERATION);
        });

        it('parses mixed list', function () {
            $parser = ($this->createParser)('10px solid #ccc');

            $result = $parser->parse();

            expect($result->type)->toBe(NodeType::LIST);
        });

        it('processes nested map structures with list values', function () {
            $parser = ($this->createParser)('map(key: (1, 2, 3))');

            $accessor = new ReflectionAccessor($parser);

            $result = $accessor->callMethod('tryParseMap');

            expect($result)->toBeNull();
        });

        it('validates map parsing with colon requirement', function () {
            $parser = ($this->createParser)('map(invalid-key no-colon)');

            $accessor = new ReflectionAccessor($parser);

            $result = $accessor->callMethod('tryParseMap');

            expect($result)->toBeNull();
        });

        it('returns null when no current token available', function () {
            $parser = new ExpressionParser(new TokenStream([]));

            $accessor = new ReflectionAccessor($parser);

            $result = $accessor->callMethod('parseMapValue');
            expect($result)->toBeNull();
        });

        it('handles default parsing branch with token rollback', function () {
            $parser = ($this->createParser)('test-value');

            $accessor = new ReflectionAccessor($parser);
            $accessor->callMethod('setTokenIndex', [100]);

            $result = $accessor->callMethod('parseMapValue');

            expect($result)->toBeNull();
        });

        it('executes numeric value parsing without units in attribute selector', function () {
            $tokens = $this->lexer->tokenize('[50]');

            $parser = new ExpressionParser($tokens);
            $accessor = new ReflectionAccessor($parser);

            $token = $tokens->current();
            expect($token->type)->toBe('attribute_selector');

            $result = $accessor->callMethod('parseAttributeSelector', [$token]);

            expect($result)->toBeInstanceOf(ListNode::class)
                ->and($result->values[0])->toBe(50.0);
        });

        it('executes list type checking logic in default branch', function () {
            $tokens = $this->lexer->tokenize('(a, b, c)');
            $parser = new ExpressionParser($tokens);

            $accessor = new ReflectionAccessor($parser);
            $accessor->callMethod('setTokenIndex', [0]);

            $result = $accessor->callMethod('parseMapValue');

            expect($result)->toBeInstanceOf(ListNode::class);
        });
    });
});
