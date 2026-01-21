<?php

declare(strict_types=1);

use DartSass\Evaluators\CalcFunctionEvaluator;
use DartSass\Exceptions\CompilationException;
use DartSass\Parsers\Nodes\NumberNode;
use DartSass\Parsers\Nodes\OperationNode;
use DartSass\Utils\ResultFormatterInterface;
use DartSass\Values\SassNumber;

describe('CalcFunctionEvaluator', function () {
    beforeEach(function () {
        $this->formatter = mock(ResultFormatterInterface::class);
        $this->evaluator = new CalcFunctionEvaluator($this->formatter);
    });

    describe('evaluate()', function () {
        it('returns value for SassNumber without unit', function () {
            $number = new SassNumber(10);
            $result = $this->evaluator->evaluate([$number], fn($node) => $node);

            expect($result)->toBe(10.0);
        });

        it('returns object for SassNumber with unit', function () {
            $number = new SassNumber(10, 'px');
            $result = $this->evaluator->evaluate([$number], fn($node) => $node);

            expect($result)->toBe($number);
        });

        it('returns string for calc string', function () {
            $string = 'calc(10px + 5px)';
            $result = $this->evaluator->evaluate([$string], fn($node) => $node);

            expect($result)->toBe($string);
        });

        it('computes division operation', function () {
            $left      = new NumberNode(10);
            $right     = new NumberNode(2);
            $operation = new OperationNode($left, '/', $right, 1);
            $result    = $this->evaluator->evaluate([$operation], function ($node) {
                if ($node instanceof NumberNode) {
                    return new SassNumber($node->value, $node->unit);
                }

                return $node;
            });

            expect($result)->toBe(5.0);
        });

        it('throws exception for unknown operator', function () {
            $left      = new NumberNode(10);
            $right     = new NumberNode(2);
            $operation = new OperationNode($left, '%', $right, 1);

            expect(fn() => $this->evaluator->evaluate([$operation], function ($node) {
                if ($node instanceof NumberNode) {
                    return new SassNumber($node->value, $node->unit);
                }

                return $node;
            }))->toThrow(CompilationException::class, 'Unknown operator: %');
        });
    });
});
