<?php

declare(strict_types=1);

use DartSass\Evaluators\InterpolationEvaluator;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\ParserFactory;
use DartSass\Parsers\ParserInterface;
use DartSass\Parsers\Syntax;
use DartSass\Utils\ResultFormatterInterface;
use Tests\ReflectionAccessor;

describe('InterpolationEvaluator', function () {
    beforeEach(function () {
        $this->formatter     = mock(ResultFormatterInterface::class);
        $this->parserFactory = mock(ParserFactory::class);
        $this->evaluator     = new InterpolationEvaluator($this->formatter, $this->parserFactory);
        $this->accessor      = new ReflectionAccessor($this->evaluator);
    });

    describe('processInlineVariables()', function () {
        it('processes inline variable without nested interpolation', function () {
            $string = 'value is $var';

            $expression = function ($var) {
                if ($var === '$var') {
                    return 'replaced';
                }

                return $var;
            };

            $this->formatter->allows()->format('replaced')->andReturns('formatted');

            $result = $this->accessor->callMethod('processInlineVariables', [$string, $expression]);

            expect($result)->toBe('value is formatted');
        });

        it('processes inline variable with nested interpolation', function () {
            $string = '$var';

            $expression = function ($arg) {
                if ($arg === '$var') {
                    return 'value with #{1+1}';
                }

                // For AST of 1+1, return 'processed'
                return 'processed';
            };

            $parser = mock(ParserInterface::class);
            $parser->allows()->parseExpression()->andReturn(mock(AstNode::class));

            $this->parserFactory->allows()->create('1+1', Syntax::SCSS)->andReturn($parser);
            $this->formatter->allows()->format('value with processed')->andReturns('formatted');

            $result = $this->accessor->callMethod('processInlineVariables', [$string, $expression]);

            expect($result)->toBe('formatted');
        });

        it('processes inline variable with non-string value', function () {
            $string = '$num';

            $expression = function ($var) {
                if ($var === '$num') {
                    return 42;
                }

                return $var;
            };

            $this->formatter->allows()->format(42)->andReturns('42px');

            $result = $this->accessor->callMethod('processInlineVariables', [$string, $expression]);

            expect($result)->toBe('42px');
        });

        it('handles exception in evaluateExpression', function () {
            $string = '$bad';

            $expression = function ($var) {
                if ($var === '$bad') {
                    throw new Exception('Bad variable');
                }

                return $var;
            };

            $result = $this->accessor->callMethod('processInlineVariables', [$string, $expression]);

            expect($result)->toBe('$bad');
        });
    });

    describe('processHashInterpolations()', function () {
        it('processes simple hash interpolation', function () {
            $string = '#{1+1}';

            $expression = function ($arg) {
                if ($arg instanceof AstNode) {
                    return 'processed';
                }

                return $arg;
            };

            $parser = mock(ParserInterface::class);
            $parser->expects()->parseExpression()->andReturn(mock(AstNode::class));

            $this->parserFactory->expects()->create('1+1', Syntax::SCSS)->andReturn($parser);

            $result = $this->accessor->callMethod('processHashInterpolations', [$string, $expression]);

            expect($result)->toBe('processed');
        });

        it('handles exception in evaluateExpression', function () {
            $string = '#{bad expression}';

            $expression = function ($arg) {
                if ($arg instanceof AstNode) {
                    throw new Exception('Bad expression');
                }

                return $arg;
            };

            $result = $this->accessor->callMethod('processHashInterpolations', [$string, $expression]);

            expect($result)->toBe('bad expression');
        });

        it('processes nested interpolations', function () {
            $string = '#{outer #{inner}}';

            $expression = function ($arg) {
                if ($arg instanceof AstNode) {
                    // Return distinct values to verify which AST was processed
                    static $callCount = 0;

                    $callCount++;

                    if ($callCount === 1) {
                        // First call: processing inner interpolation AST
                        return 'inner-ast-result';
                    } else {
                        // Second call: processing outer expression AST
                        return 'outer-ast-result';
                    }
                }

                return $arg;
            };

            $innerParser = mock(ParserInterface::class);
            $innerAst = mock(AstNode::class);
            $innerParser->allows()->parseExpression()->andReturn($innerAst);
            $this->parserFactory->allows()->create('inner', Syntax::SCSS)->andReturn($innerParser);

            $outerParser = mock(ParserInterface::class);
            $outerAst = mock(AstNode::class);
            $outerParser->allows()->parseExpression()->andReturn($outerAst);
            $this->parserFactory->allows()->create('outer inner-ast-result', Syntax::SCSS)->andReturn($outerParser);

            $result = $this->evaluator->evaluate($string, $expression);

            expect($result)->toBe('outer inner-ast-result');
        });
    });
});
