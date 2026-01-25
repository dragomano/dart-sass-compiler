<?php

declare(strict_types=1);

use DartSass\Evaluators\UserFunctionEvaluator;
use DartSass\Handlers\VariableHandler;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\EachNode;
use DartSass\Parsers\Nodes\ForNode;
use DartSass\Parsers\Nodes\ListNode;
use DartSass\Parsers\Nodes\NodeType;
use DartSass\Parsers\Nodes\NumberNode;
use DartSass\Parsers\Nodes\OperationNode;
use DartSass\Parsers\Nodes\StringNode;
use DartSass\Parsers\Nodes\VariableDeclarationNode;
use DartSass\Parsers\Nodes\VariableNode;
use DartSass\Values\SassList;
use Tests\ReflectionAccessor;

describe('UserFunctionEvaluator', function () {
    beforeEach(function () {
        $this->variableHandler = mock(VariableHandler::class);
        $this->evaluator       = new UserFunctionEvaluator();
        $this->accessor        = new ReflectionAccessor($this->evaluator);
    });

    describe('evaluate()', function () {
        it('processes arguments with new structure', function () {
            $func = [
                'args'    => [
                    ['name' => 'arg1', 'arbitrary' => false],
                    ['name' => 'arg2', 'arbitrary' => false, 'default' => 'defaultValue'],
                ],
                'body'    => [
                    (object) ['type' => NodeType::RETURN, 'value' => 'result'],
                ],
                'handler' => $this->variableHandler,
            ];

            $args  = ['value1', 'value2'];
            $expression = fn($expr) => $expr;

            $this->variableHandler->expects()->enterScope()->once();
            $this->variableHandler->expects()->define('arg1', 'value1')->once();
            $this->variableHandler->expects()->define('arg2', 'value2')->once();
            $this->variableHandler->expects()->exitScope()->once();

            $result = $this->evaluator->evaluate($func, $args, $expression);

            expect($result)->toBe('result');
        });

        it('uses default value when argument not provided', function () {
            $func = [
                'args'    => [
                    ['name' => 'arg1', 'arbitrary' => false],
                    ['name' => 'arg2', 'arbitrary' => false, 'default' => 'defaultValue'],
                ],
                'body'    => [
                    (object) ['type' => NodeType::RETURN, 'value' => 'result'],
                ],
                'handler' => $this->variableHandler,
            ];

            $args = ['value1'];
            $expression = fn($expr) => $expr;

            $this->variableHandler->expects()->enterScope()->once();
            $this->variableHandler->expects()->define('arg1', 'value1')->once();
            $this->variableHandler->expects()->define('arg2', 'defaultValue')->once();
            $this->variableHandler->expects()->exitScope()->once();

            $result = $this->evaluator->evaluate($func, $args, $expression);

            expect($result)->toBe('result');
        });

        it('handles arbitrary arguments', function () {
            $func = [
                'args'    => [
                    ['name' => 'arg1', 'arbitrary' => false],
                    ['name' => 'rest', 'arbitrary' => true],
                ],
                'body'    => [
                    (object) ['type' => NodeType::RETURN, 'value' => 'result'],
                ],
                'handler' => $this->variableHandler,
            ];

            $args = ['value1', 'value2', 'value3'];
            $expression = fn($expr) => $expr;

            $this->variableHandler->expects()->enterScope()->once();
            $this->variableHandler->expects()->define('arg1', 'value1')->once();
            $this->variableHandler->expects()->define('rest', Mockery::type(ListNode::class))->once();
            $this->variableHandler->expects()->exitScope()->once();

            $result = $this->evaluator->evaluate($func, $args, $expression);

            expect($result)->toBe('result');
        });

        it('processes arguments with old array structure', function () {
            $func = [
                'args'    => ['arg1', 'arg2'],
                'body'    => [
                    (object) ['type' => NodeType::RETURN, 'value' => 'result'],
                ],
                'handler' => $this->variableHandler,
            ];

            $args = ['value1', 'value2'];
            $expression = fn($expr) => $expr;

            $this->variableHandler->expects()->enterScope()->once();
            $this->variableHandler->expects()->define('arg1', 'value1')->once();
            $this->variableHandler->expects()->define('arg2', 'value2')->once();
            $this->variableHandler->expects()->exitScope()->once();

            $result = $this->evaluator->evaluate($func, $args, $expression);

            expect($result)->toBe('result');
        });

        it('processes arguments with old associative structure', function () {
            $funcArgs = ['arg1' => 'default1', 'arg2' => 'default2'];

            $func = [
                'args'    => $funcArgs,
                'body'    => [
                    (object) ['type' => NodeType::RETURN, 'value' => 'result'],
                ],
                'handler' => $this->variableHandler,
            ];

            $args = ['value1'];
            $expression = fn($expr) => $expr === 'default2' ? 'evaluatedDefault2' : $expr;

            $this->variableHandler->expects()->enterScope()->once();
            $this->variableHandler->expects()->define('arg1', 'value1')->once();
            $this->variableHandler->expects()->define('arg2', 'evaluatedDefault2')->once();
            $this->variableHandler->expects()->exitScope()->once();

            $result = $this->evaluator->evaluate($func, $args, $expression);

            expect($result)->toBe('result');
        });

        it('executes variable statement', function () {
            $func = [
                'args'    => [],
                'body'    => [
                    (object) ['type' => NodeType::VARIABLE, 'name' => 'var1', 'value' => 'value1', 'global' => false, 'default' => false],
                    (object) ['type' => NodeType::RETURN, 'value' => 'result'],
                ],
                'handler' => $this->variableHandler,
            ];

            $args = [];
            $expression = fn($expr) => $expr === 'value1' ? 'evaluatedValue1' : $expr;

            $this->variableHandler->expects()->enterScope()->once();
            $this->variableHandler->expects()->define('var1', 'evaluatedValue1', false, false)->once();
            $this->variableHandler->expects()->exitScope()->once();

            $result = $this->evaluator->evaluate($func, $args, $expression);

            expect($result)->toBe('result');
        });

        it('executes for loop', function () {
            $func = [
                'args'    => [],
                'body'    => [
                    new ForNode(
                        'i',
                        new NumberNode(1.0),
                        new NumberNode(3.0),
                        true,
                        [new VariableDeclarationNode('temp', new StringNode('loop', 0))],
                        0
                    ),
                ],
                'handler' => $this->variableHandler,
            ];

            $args = [];
            $expression = fn($expr) => is_numeric($expr) ? (int) $expr : ($expr instanceof NumberNode ? (int) $expr->value : ($expr instanceof StringNode ? $expr->value : $expr));

            $this->variableHandler->expects()->enterScope()->once();
            $this->variableHandler->expects()->define('i', 1)->once();
            $this->variableHandler->expects()->define('temp', 'loop', false, false)->once();
            $this->variableHandler->expects()->define('i', 2)->once();
            $this->variableHandler->expects()->define('temp', 'loop', false, false)->once();
            $this->variableHandler->expects()->define('i', 3)->once();
            $this->variableHandler->expects()->define('temp', 'loop', false, false)->once();
            $this->variableHandler->expects()->exitScope()->once();

            $result = $this->evaluator->evaluate($func, $args, $expression);

            expect($result)->toBeNull();
        });

        it('executes for loop in reverse', function () {
            $func = [
                'args'    => [],
                'body'    => [
                    new ForNode(
                        'i',
                        new NumberNode(3.0),
                        new NumberNode(1.0),
                        true,
                        [new VariableDeclarationNode('temp', new StringNode('reverse', 0))],
                        0
                    ),
                ],
                'handler' => $this->variableHandler,
            ];

            $args = [];
            $expression = fn($expr) => is_numeric($expr) ? (int) $expr : ($expr instanceof NumberNode ? (int) $expr->value : ($expr instanceof StringNode ? $expr->value : $expr));

            $this->variableHandler->expects()->enterScope()->once();
            $this->variableHandler->expects()->define('i', 3)->once();
            $this->variableHandler->expects()->define('temp', 'reverse', false, false)->once();
            $this->variableHandler->expects()->define('i', 2)->once();
            $this->variableHandler->expects()->define('temp', 'reverse', false, false)->once();
            $this->variableHandler->expects()->define('i', 1)->once();
            $this->variableHandler->expects()->define('temp', 'reverse', false, false)->once();
            $this->variableHandler->expects()->exitScope()->once();

            $result = $this->evaluator->evaluate($func, $args, $expression);

            expect($result)->toBeNull();
        });

        it('executes each loop with single variable', function () {
            $sassList = new SassList(['a', 'b', 'c'], 'comma');
            $conditionMock = mock(AstNode::class);

            $func = [
                'args'    => [],
                'body'    => [
                    new EachNode(
                        ['item'],
                        $conditionMock,
                        [new VariableDeclarationNode('temp', new StringNode('each', 0))],
                        0
                    ),
                ],
                'handler' => $this->variableHandler,
            ];

            $args = [];
            $expression = fn($expr) => $expr === $conditionMock ? $sassList : ($expr instanceof StringNode ? $expr->value : $expr);

            $this->variableHandler->expects()->enterScope()->once();
            $this->variableHandler->expects()->define('item', 'a')->once();
            $this->variableHandler->expects()->define('temp', 'each', false, false)->once();
            $this->variableHandler->expects()->define('item', 'b')->once();
            $this->variableHandler->expects()->define('temp', 'each', false, false)->once();
            $this->variableHandler->expects()->define('item', 'c')->once();
            $this->variableHandler->expects()->define('temp', 'each', false, false)->once();
            $this->variableHandler->expects()->exitScope()->once();

            $result = $this->evaluator->evaluate($func, $args, $expression);

            expect($result)->toBeNull();
        });

        it('executes each loop with multiple variables', function () {
            $listNode = new ListNode([new ListNode(['x', 'y']), new ListNode(['a', 'b'])]);
            $conditionMock = mock(AstNode::class);

            $func = [
                'args'    => [],
                'body'    => [
                    new EachNode(
                        ['var1', 'var2'],
                        $conditionMock,
                        [new VariableDeclarationNode('temp', new StringNode('each', 0))],
                        0
                    ),
                ],
                'handler' => $this->variableHandler,
            ];

            $args = [];
            $expression = fn($expr) => $expr === $conditionMock ? $listNode : ($expr instanceof StringNode ? $expr->value : $expr);

            $this->variableHandler->expects()->enterScope()->once();
            $this->variableHandler->expects()->define('var1', 'x')->once();
            $this->variableHandler->expects()->define('var2', 'y')->once();
            $this->variableHandler->expects()->define('temp', 'each', false, false)->once();
            $this->variableHandler->expects()->define('var1', 'a')->once();
            $this->variableHandler->expects()->define('var2', 'b')->once();
            $this->variableHandler->expects()->define('temp', 'each', false, false)->once();
            $this->variableHandler->expects()->exitScope()->once();

            $result = $this->evaluator->evaluate($func, $args, $expression);

            expect($result)->toBeNull();
        });

        it('executes each loop with array condition', function () {
            $conditionMock = mock(AstNode::class);

            $func = [
                'args'    => [],
                'body'    => [
                    new EachNode(
                        ['item'],
                        $conditionMock,
                        [new VariableDeclarationNode('temp', new StringNode('array_each', 0))],
                        0
                    ),
                ],
                'handler' => $this->variableHandler,
            ];

            $args = [];
            $arrayCondition = ['a', 'b', 'c'];
            $expression = fn($expr) => $expr === $conditionMock ? $arrayCondition : ($expr instanceof StringNode ? $expr->value : $expr);

            $this->variableHandler->expects()->enterScope()->once();
            $this->variableHandler->expects()->define('item', 'a')->once();
            $this->variableHandler->expects()->define('temp', 'array_each', false, false)->once();
            $this->variableHandler->expects()->define('item', 'b')->once();
            $this->variableHandler->expects()->define('temp', 'array_each', false, false)->once();
            $this->variableHandler->expects()->define('item', 'c')->once();
            $this->variableHandler->expects()->define('temp', 'array_each', false, false)->once();
            $this->variableHandler->expects()->exitScope()->once();

            $result = $this->evaluator->evaluate($func, $args, $expression);

            expect($result)->toBeNull();
        });

        it('executes each loop with scalar condition', function () {
            $conditionMock = mock(AstNode::class);

            $func = [
                'args'    => [],
                'body'    => [
                    new EachNode(
                        ['item'],
                        $conditionMock,
                        [new VariableDeclarationNode('temp', new StringNode('scalar_each', 0))],
                        0
                    ),
                ],
                'handler' => $this->variableHandler,
            ];

            $args = [];
            $scalarCondition = 'single_item';
            $expression = fn($expr) => $expr === $conditionMock ? $scalarCondition : ($expr instanceof StringNode ? $expr->value : $expr);

            $this->variableHandler->expects()->enterScope()->once();
            $this->variableHandler->expects()->define('item', 'single_item')->once();
            $this->variableHandler->expects()->define('temp', 'scalar_each', false, false)->once();
            $this->variableHandler->expects()->exitScope()->once();

            $result = $this->evaluator->evaluate($func, $args, $expression);

            expect($result)->toBeNull();
        });

        it('handles return statement with evaluated value', function () {
            $func = [
                'args'    => [],
                'body'    => [
                    (object) ['type' => NodeType::RETURN, 'value' => 'returnValue'],
                ],
                'handler' => $this->variableHandler,
            ];

            $args = [];
            $expression = fn($expr) => $expr === 'returnValue' ? 'evaluatedReturn' : $expr;

            $this->variableHandler->expects()->enterScope()->once();
            $this->variableHandler->expects()->exitScope()->once();

            $result = $this->evaluator->evaluate($func, $args, $expression);

            expect($result)->toBe('evaluatedReturn');
        });

        it('handles multiplication operation in return', function () {
            $multiplicationNode = new OperationNode(
                new VariableNode('dummy', 0),
                '*',
                new NumberNode(2.0),
                0
            );

            $func = [
                'args'    => [],
                'body'    => [
                    (object) ['type' => NodeType::RETURN, 'value' => $multiplicationNode],
                ],
                'handler' => $this->variableHandler,
            ];

            $args = [['value' => 5, 'unit' => 'px']];
            $expression = fn($expr) => $expr;

            $this->variableHandler->expects()->enterScope()->once();
            $this->variableHandler->expects()->exitScope()->once();

            $result = $this->evaluator->evaluate($func, $args, $expression);

            expect($result)->toBe(['value' => 10.0, 'unit' => 'px']);
        });

        it('handles multiplication operation with simple numeric value', function () {
            $multiplicationNode = new OperationNode(
                new VariableNode('dummy', 0),
                '*',
                new NumberNode(3.0),
                0
            );

            $func = [
                'args'    => [],
                'body'    => [
                    (object) ['type' => NodeType::RETURN, 'value' => $multiplicationNode],
                ],
                'handler' => $this->variableHandler,
            ];

            $args = [5];
            $expression = fn($expr) => $expr;

            $this->variableHandler->expects()->enterScope()->once();
            $this->variableHandler->expects()->exitScope()->once();

            $result = $this->evaluator->evaluate($func, $args, $expression);

            expect($result)->toBe(15.0);
        });

        it('returns null if no return statement', function () {
            $func = [
                'args'    => [],
                'body'    => [
                    (object) ['type' => NodeType::VARIABLE, 'name' => 'var1', 'value' => 'value1', 'global' => false, 'default' => false],
                ],
                'handler' => $this->variableHandler,
            ];

            $args = [];
            $expression = fn($expr) => $expr;

            $this->variableHandler->expects()->enterScope()->once();
            $this->variableHandler->expects()->define('var1', 'value1', false, false)->once();
            $this->variableHandler->expects()->exitScope()->once();

            $result = $this->evaluator->evaluate($func, $args, $expression);

            expect($result)->toBeNull();
        });
    });
});
