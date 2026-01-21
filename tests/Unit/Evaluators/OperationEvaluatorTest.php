<?php

declare(strict_types=1);

use DartSass\Compilers\CompilerContext;
use DartSass\Evaluators\ExpressionEvaluator;
use DartSass\Evaluators\OperationEvaluator;
use DartSass\Exceptions\CompilationException;
use DartSass\Parsers\Nodes\NumberNode;
use DartSass\Parsers\Nodes\OperationNode;
use DartSass\Utils\LazyEvaluatable;
use DartSass\Utils\LazyValue;
use DartSass\Utils\ResultFormatterInterface;
use DartSass\Values\SassNumber;
use Tests\ReflectionAccessor;

describe('OperationEvaluator', function () {
    beforeEach(function () {
        $this->context                      = mock(CompilerContext::class);
        $this->expressionEvaluator          = mock(ExpressionEvaluator::class);
        $this->resultFormatter              = mock(ResultFormatterInterface::class);
        $this->context->expressionEvaluator = $this->expressionEvaluator;
        $this->context->resultFormatter     = $this->resultFormatter;
        $this->evaluator                    = new OperationEvaluator($this->context);
        $this->accessor                     = new ReflectionAccessor($this->evaluator);
    });

    describe('supports()', function () {
        it('returns true for OperationNode', function () {
            $node = new OperationNode(new NumberNode(1), '+', new NumberNode(2), 1);

            expect($this->evaluator->supports($node))->toBeTrue();
        });

        it('returns false for non-OperationNode', function () {
            $node = new NumberNode(42);

            expect($this->evaluator->supports($node))->toBeFalse()
                ->and($this->evaluator->supports('string'))->toBeFalse()
                ->and($this->evaluator->supports(42))->toBeFalse()
                ->and($this->evaluator->supports(null))->toBeFalse();

        });
    });

    describe('evaluate()', function () {
        it('evaluates OperationNode correctly', function () {
            $leftNode  = new NumberNode(1);
            $rightNode = new NumberNode(2);
            $operation = new OperationNode($leftNode, '+', $rightNode, 1);

            $this->expressionEvaluator->expects()->evaluate($leftNode)->andReturn(1);
            $this->expressionEvaluator->expects()->evaluate($rightNode)->andReturn(2);

            $result = $this->evaluator->evaluate($operation);

            expect($result)->toBeInstanceOf(SassNumber::class);
        });

        it('throws exception for invalid expression', function () {
            expect(fn() => $this->evaluator->evaluate('invalid'))
                ->toThrow(CompilationException::class, 'Invalid arguments for OperationEvaluator::evaluate()');
        });
    });

    describe('evaluateOperation()', function () {
        it('handles addition of numbers', function () {
            $result = $this->evaluator->evaluateOperation(1, '+', 2);

            expect($result)->toBeInstanceOf(SassNumber::class)
                ->and($result->getValue())->toBe(3.0);
        });

        it('handles subtraction', function () {
            $result = $this->evaluator->evaluateOperation(5, '-', 3);

            expect($result)->toBeInstanceOf(SassNumber::class)
                ->and($result->getValue())->toBe(2.0);
        });

        it('handles multiplication', function () {
            $result = $this->evaluator->evaluateOperation(4, '*', 2);

            expect($result)->toBeInstanceOf(SassNumber::class)
                ->and($result->getValue())->toBe(8.0);
        });

        it('handles division', function () {
            $result = $this->evaluator->evaluateOperation(10, '/', 2);

            expect($result)->toBeInstanceOf(SassNumber::class)
                ->and($result->getValue())->toBe(5.0);
        });

        it('handles string concatenation', function () {
            $result = $this->evaluator->evaluateOperation('hello', '+', 'world');

            expect($result)->toBe('helloworld');
        });

        it('handles string and number concatenation', function () {
            $result = $this->evaluator->evaluateOperation('width: ', '+', 100);

            expect($result)->toBe('width: 100');
        });

        it('handles number and string concatenation', function () {
            $result = $this->evaluator->evaluateOperation(100, '+', 'px');

            expect($result)->toBe('100px');
        });

        it('handles structured value and string concatenation', function () {
            $structured = ['value' => 50, 'unit' => 'px'];

            $result = $this->evaluator->evaluateOperation($structured, '+', ' !important');

            expect($result)->toBe('50px !important');
        });

        it('handles string and structured value concatenation', function () {
            $structured = ['value' => 50, 'unit' => 'px'];

            $result = $this->evaluator->evaluateOperation('prefix-', '+', $structured);

            expect($result)->toBe('prefix-50px');
        });

        it('handles structured value addition as calc', function () {
            $left   = ['value' => 10, 'unit' => 'px'];
            $right  = ['some' => 'array'];
            $result = $this->evaluator->evaluateOperation($left, '+', $right);

            expect($result)->toContain('calc');
        });

        it('handles comparison operators equal', function () {
            $result = $this->evaluator->evaluateOperation(1, '==', 1);

            expect($result)->toBeTrue();
        });

        it('handles comparison operators not equal', function () {
            $result = $this->evaluator->evaluateOperation('a', '!=', 'b');

            expect($result)->toBeTrue();
        });

        it('handles less than', function () {
            $result = $this->evaluator->evaluateOperation(3, '<', 5);

            expect($result)->toBeTrue();
        });

        it('handles greater than or equal', function () {
            $result = $this->evaluator->evaluateOperation(10, '>=', 10);

            expect($result)->toBeTrue();
        });

        it('handles logical and', function () {
            $result = $this->evaluator->evaluateOperation(true, 'and', false);

            expect($result)->toBeFalse();
        });

        it('handles logical or', function () {
            $result = $this->evaluator->evaluateOperation(false, 'or', true);

            expect($result)->toBeTrue();
        });

        it('handles LazyValue resolution', function () {
            $lazy = mock(LazyValue::class);
            $lazy->shouldReceive('getValue')->andReturn(42);

            $result = $this->evaluator->evaluateOperation($lazy, '+', 8);

            expect($result)->toBeInstanceOf(SassNumber::class)
                ->and($result->getValue())->toBe(50.0);
        });

        it('handles LazyEvaluatable resolution', function () {
            $lazyEval = mock(LazyEvaluatable::class);
            $lazyEval->shouldReceive('evaluate')->andReturn('lazy result');

            $result = $this->evaluator->evaluateOperation($lazyEval, '/', 'divider');

            expect($result)->toBe('lazy result / divider');
        });

        it('handles division of strings', function () {
            $result = $this->evaluator->evaluateOperation('calc(100px)', '/', '2');

            expect($result)->toBe('calc(100px) / 2');
        });

        it('handles modulo operation', function () {
            $result = $this->evaluator->evaluateOperation(10, '%', 3);

            expect($result)->toBeInstanceOf(SassNumber::class)
                ->and($result->getValue())->toBe(1.0);
        });

        it('throws exception for undefined multiplication', function () {
            $this->resultFormatter
                ->allows()
                ->format(Mockery::any())
                ->andReturnUsing(fn($value) => (string) $value);

            expect(fn() => $this->evaluator->evaluateOperation('text', '*', 'other'))
                ->toThrow(CompilationException::class, 'Undefined operation "text * other".');
        });

        it('handles other operators as calc', function () {
            $result = $this->evaluator->evaluateOperation([], '**', 2);

            expect($result)->toBeString()->and($result)->toContain('calc');
        });

        it('handles string division concatenation', function () {
            $result = $this->evaluator->evaluateOperation('simple', '/', 'complex(calc)');

            expect($result)->toBe('simple / complex(calc)');
        });

        it('handles complex string division concatenation', function () {
            $result = $this->evaluator->evaluateOperation('complex(calc)', '/', 'simple');

            expect($result)->toBe('complex(calc) / simple');
        });

        it('handles numeric string addition as numbers', function () {
            $result = $this->evaluator->evaluateOperation('1', '+', '2');

            expect($result)->toBeInstanceOf(SassNumber::class)
                ->and($result->getValue())->toBe(3.0);
        });
    });
});
