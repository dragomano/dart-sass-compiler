<?php

declare(strict_types=1);

use DartSass\Parsers\Nodes\AtRuleNode;
use DartSass\Parsers\Nodes\IdentifierNode;
use DartSass\Parsers\Nodes\OperationNode;
use DartSass\Parsers\Nodes\VariableNode;
use DartSass\Utils\LazyValue;
use DartSass\Utils\ResultFormatter;
use DartSass\Utils\ValueFormatter;
use DartSass\Values\SassList;
use Tests\ReflectionAccessor;

beforeEach(function () {
    $this->valueFormatter     = new ValueFormatter();
    $this->resultFormatter    = new ResultFormatter($this->valueFormatter);
    $this->reflectionAccessor = new ReflectionAccessor($this->resultFormatter);
});

describe('ResultFormatter', function () {
    describe('format', function () {
        describe('AST Node handling', function () {
            it('formats OperationNode with addition', function () {
                $left      = new VariableNode('width', 1);
                $right     = new IdentifierNode('20px', 1);
                $operation = new OperationNode($left, '+', $right, 1);

                $result = $this->resultFormatter->format($operation);

                expect($result)->toBe('calc($width + 20px)');
            });

            it('formats OperationNode with subtraction', function () {
                $left      = new IdentifierNode('100%', 1);
                $right     = new IdentifierNode('20px', 1);
                $operation = new OperationNode($left, '-', $right, 1);

                $result = $this->resultFormatter->format($operation);

                expect($result)->toBe('calc(100% - 20px)');
            });

            it('formats OperationNode with multiplication', function () {
                $left      = new IdentifierNode('2', 1);
                $right     = new VariableNode('factor', 1);
                $operation = new OperationNode($left, '*', $right, 1);

                $result = $this->resultFormatter->format($operation);

                expect($result)->toBe('calc(2 * $factor)');
            });

            it('formats OperationNode with division', function () {
                $left      = new VariableNode('total', 1);
                $right     = new IdentifierNode('4', 1);
                $operation = new OperationNode($left, '/', $right, 1);

                $result = $this->resultFormatter->format($operation);

                expect($result)->toBe('calc($total / 4)');
            });

            it('formats nested OperationNode', function () {
                $innerLeft      = new IdentifierNode('10', 1);
                $innerRight     = new IdentifierNode('5', 1);
                $innerOperation = new OperationNode($innerLeft, '+', $innerRight, 1);

                $outerLeft      = new VariableNode('base', 1);
                $outerRight     = $innerOperation;
                $outerOperation = new OperationNode($outerLeft, '*', $outerRight, 1);

                $result = $this->resultFormatter->format($outerOperation);

                expect($result)->toBe('calc($base * calc(10 + 5))');
            });

            it('formats VariableNode with simple name', function () {
                $variable = new VariableNode('color', 1);

                $result = $this->resultFormatter->format($variable);

                expect($result)->toBe('$color');
            });

            it('formats VariableNode with hyphenated name', function () {
                $variable = new VariableNode('main-color', 1);

                $result = $this->resultFormatter->format($variable);

                expect($result)->toBe('$main-color');
            });

            it('formats VariableNode with underscore', function () {
                $variable = new VariableNode('font_size', 1);

                $result = $this->resultFormatter->format($variable);

                expect($result)->toBe('$font_size');
            });

            it('formats IdentifierNode with simple value', function () {
                $identifier = new IdentifierNode('block', 1);

                $result = $this->resultFormatter->format($identifier);

                expect($result)->toBe('block');
            });

            it('formats IdentifierNode with special characters', function () {
                $identifier = new IdentifierNode('flex-start', 1);

                $result = $this->resultFormatter->format($identifier);

                expect($result)->toBe('flex-start');
            });

            it('formats IdentifierNode with numbers', function () {
                $identifier = new IdentifierNode('grid-column-2', 1);

                $result = $this->resultFormatter->format($identifier);

                expect($result)->toBe('grid-column-2');
            });
        });

        describe('ValueFormatter delegation', function () {
            it('formats numeric value', function () {
                $result = $this->resultFormatter->format(42);

                expect($result)->toBe('42');
            });

            it('formats float value', function () {
                $result = $this->resultFormatter->format(3.14159);

                expect($result)->toBe('3.14159');
            });

            it('formats zero', function () {
                $result = $this->resultFormatter->format(0);

                expect($result)->toBe('0');
            });

            it('formats negative number', function () {
                $result = $this->resultFormatter->format(-10);

                expect($result)->toBe('-10');
            });

            it('formats decimal less than 1', function () {
                $result = $this->resultFormatter->format(0.5);

                expect($result)->toBe('.5');
            });

            it('formats boolean true', function () {
                $result = $this->resultFormatter->format(true);

                expect($result)->toBe('true');
            });

            it('formats boolean false', function () {
                $result = $this->resultFormatter->format(false);

                expect($result)->toBe('false');
            });

            it('formats string value', function () {
                $result = $this->resultFormatter->format('hello world');

                expect($result)->toBe('hello world');
            });

            it('formats empty string', function () {
                $result = $this->resultFormatter->format('');

                expect($result)->toBe('');
            });

            it('formats array with value and unit', function () {
                $result = $this->resultFormatter->format(['value' => 10, 'unit' => 'px']);

                expect($result)->toBe('10px');
            });

            it('formats array with quoted string', function () {
                $result = $this->resultFormatter->format(['value' => '"hello"']);

                expect($result)->toBe('"hello"');
            });

            it('formats plain array', function () {
                $result = $this->resultFormatter->format([1, 2, 3]);

                expect($result)->toBe('1, 2, 3');
            });

            it('formats null value', function () {
                $result = $this->resultFormatter->format(null);

                expect($result)->toBe('');
            });

            it('formats LazyValue with numeric result', function () {
                $lazyValue = new LazyValue(fn() => 42);

                $result = $this->resultFormatter->format($lazyValue);

                expect($result)->toBe('42');
            });

            it('formats LazyValue with string result', function () {
                $lazyValue = new LazyValue(fn() => 'computed-value');

                $result = $this->resultFormatter->format($lazyValue);

                expect($result)->toBe('computed-value');
            });

            it('formats SassList with comma separator', function () {
                $sassList = new SassList(['red', 'blue', 'green'], 'comma');

                $result = $this->resultFormatter->format($sassList);

                expect($result)->toBe('red, blue, green');
            });

            it('formats SassList with space separator', function () {
                $sassList = new SassList(['10px', 'solid', 'red'], 'space');

                $result = $this->resultFormatter->format($sassList);

                expect($result)->toBe('10px solid red');
            });

            it('formats SassList with slash separator', function () {
                $sassList = new SassList(['16', '9'], 'slash');

                $result = $this->resultFormatter->format($sassList);

                expect($result)->toBe('16 / 9');
            });
        });

        describe('Edge cases', function () {
            it('formats OperationNode with zero operands', function () {
                $left      = new IdentifierNode('0', 1);
                $right     = new IdentifierNode('0', 1);
                $operation = new OperationNode($left, '+', $right, 1);

                $result = $this->resultFormatter->format($operation);

                expect($result)->toBe('calc(0 + 0)');
            });

            it('formats VariableNode with empty name', function () {
                $variable = new VariableNode('', 1);

                $result = $this->resultFormatter->format($variable);

                expect($result)->toBe('$');
            });

            it('formats IdentifierNode with empty value', function () {
                $identifier = new IdentifierNode('', 1);

                $result = $this->resultFormatter->format($identifier);

                expect($result)->toBe('');
            });

            it('formats very long number', function () {
                $result = $this->resultFormatter->format(1234567890.123456);

                expect($result)->toBe('1234567890.1235');
            });

            it('formats number with many decimal places', function () {
                $result = $this->resultFormatter->format(0.123456789012345);

                expect($result)->toBe('.12345678901234');
            });

            it('formats array with mixed types', function () {
                $result = $this->resultFormatter->format([1, 'two', true, false]);

                expect($result)->toBe('1, two, true, false');
            });

            it('formats array with empty strings', function () {
                $result = $this->resultFormatter->format(['one', '', 'three']);

                expect($result)->toBe('one, three');
            });

            it('formats nested array structure', function () {
                $result = $this->resultFormatter->format([
                    ['value' => 10, 'unit' => 'px'],
                    ['value' => 20, 'unit' => 'em'],
                ]);

                expect($result)->toBe('10px, 20em');
            });

            it('handles special characters in variable names', function () {
                $variable = new VariableNode('$test', 1);

                $result = $this->resultFormatter->format($variable);

                expect($result)->toBe('$$test');
            });

            it('handles unicode in identifier values', function () {
                $identifier = new IdentifierNode('café', 1);

                $result = $this->resultFormatter->format($identifier);

                expect($result)->toBe('café');
            });
        });
    });

    describe('formatAstNode', function () {
        it('delegates OperationNode formatting correctly', function () {
            $left      = new VariableNode('a', 1);
            $right     = new VariableNode('b', 1);
            $operation = new OperationNode($left, '*', $right, 1);

            $result = $this->reflectionAccessor->callMethod('formatAstNode', [$operation]);

            expect($result)->toBe('calc($a * $b)');
        });

        it('delegates VariableNode formatting correctly', function () {
            $variable = new VariableNode('test-var', 1);

            $result = $this->reflectionAccessor->callMethod('formatAstNode', [$variable]);

            expect($result)->toBe('$test-var');
        });

        it('delegates IdentifierNode formatting correctly', function () {
            $identifier = new IdentifierNode('test-value', 1);

            $result = $this->reflectionAccessor->callMethod('formatAstNode', [$identifier]);

            expect($result)->toBe('test-value');
        });

        it('formats AtRuleNode with fallback', function () {
            $atRule = new AtRuleNode('media', 'screen', null, 1);

            $result = $this->reflectionAccessor->callMethod('formatAstNode', [$atRule]);

            expect($result)->toBe('[at-rule]');
        });
    });
});
