<?php

declare(strict_types=1);

use DartSass\Compilers\CompilerContext;
use DartSass\Evaluators\ExpressionEvaluator;
use DartSass\Evaluators\InterpolationEvaluator;
use DartSass\Exceptions\CompilationException;
use DartSass\Handlers\FunctionHandler;
use DartSass\Handlers\ModuleHandler;
use DartSass\Handlers\VariableHandler;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\FunctionNode;
use DartSass\Parsers\Nodes\IdentifierNode;
use DartSass\Parsers\Nodes\ListNode;
use DartSass\Parsers\Nodes\NumberNode;
use DartSass\Parsers\Nodes\OperationNode;
use DartSass\Parsers\Nodes\OperatorNode;
use DartSass\Parsers\Nodes\PropertyAccessNode;
use DartSass\Parsers\Nodes\SelectorNode;
use DartSass\Parsers\Nodes\StringNode;
use DartSass\Parsers\Nodes\UnaryNode;
use DartSass\Parsers\Nodes\VariableNode;
use DartSass\Values\SassList;
use Tests\ReflectionAccessor;

describe('ExpressionEvaluator', function () {
    beforeEach(function () {
        $this->context   = mock(CompilerContext::class);
        $this->evaluator = new ExpressionEvaluator($this->context);
        $this->accessor  = new ReflectionAccessor($this->evaluator);

        $this->context->expressionEvaluator = $this->evaluator;
        $this->context->interpolationEvaluator = mock(InterpolationEvaluator::class);
        $this->context->interpolationEvaluator->shouldReceive('evaluate')->andReturnUsing(fn($value) => $value);
    });

    describe('supports()', function () {
        it('returns true for AstNode', function () {
            $node = mock(AstNode::class);

            expect($this->evaluator->supports($node))->toBeTrue();
        });

        it('returns true for string', function () {
            expect($this->evaluator->supports('test'))->toBeTrue();
        });

        it('returns true for numeric', function () {
            expect($this->evaluator->supports(42))->toBeTrue()
                ->and($this->evaluator->supports(3.14))->toBeTrue();
        });

        it('returns true for array', function () {
            expect($this->evaluator->supports([]))->toBeTrue();
        });

        it('returns true for null', function () {
            expect($this->evaluator->supports(null))->toBeTrue();
        });

        it('returns true for boolean', function () {
            expect($this->evaluator->supports(true))->toBeTrue()
                ->and($this->evaluator->supports(false))->toBeTrue();
        });

        it('returns false for other types', function () {
            expect($this->evaluator->supports(new stdClass()))->toBeFalse();
        });
    });

    describe('evaluate()', function () {
        it('handles VariableNode property and calls getProperty', function () {
            $propertyNode  = new VariableNode('$prop');
            $namespaceNode = new IdentifierNode('module');

            $moduleHandler = mock(ModuleHandler::class);
            $moduleHandler->shouldReceive('getProperty')
                ->with('module', '$prop', Mockery::type('callable'))
                ->andReturn('value');

            $this->context->moduleHandler = $moduleHandler;

            $node = new PropertyAccessNode($namespaceNode, $propertyNode);

            $result = $this->evaluator->evaluate($node);

            expect($result)->toBe('value');
        });

        it('handles other property node and calls getProperty', function () {
            $propertyNode  = new IdentifierNode('$prop');
            $namespaceNode = new IdentifierNode('module');

            $moduleHandler = mock(ModuleHandler::class);
            $moduleHandler->shouldReceive('getProperty')
                ->with('module', '$prop', Mockery::type('callable'))
                ->andReturn('value');

            $this->context->moduleHandler = $moduleHandler;

            $node = new PropertyAccessNode($namespaceNode, $propertyNode);

            $result = $this->evaluator->evaluate($node);

            expect($result)->toBe('value');
        });

        it('returns namespace when namespace is string not starting with $', function () {
            $propertyNode  = new IdentifierNode('prop');
            $namespaceNode = new IdentifierNode('namespace');

            $node = new PropertyAccessNode($namespaceNode, $propertyNode);

            $result = $this->evaluator->evaluate($node);

            expect($result)->toBe('namespace');
        });

        it('handles unary + with SassNumber without unit', function () {
            $operand = new NumberNode(5.0);

            $node = new UnaryNode('+', $operand);

            $result = $this->evaluator->evaluate($node);

            expect($result)->toBe(5.0);
        });

        it('handles unary + with SassNumber with unit', function () {
            $operand = new NumberNode(5.0, 'px');

            $node = new UnaryNode('+', $operand);

            $result = $this->evaluator->evaluate($node);

            expect($result)->toEqual(['value' => 5.0, 'unit' => 'px']);
        });

        it('handles unary not with SassNumber', function () {
            $operand = new NumberNode(5.0);

            $node = new UnaryNode('not', $operand);

            $result = $this->evaluator->evaluate($node);

            expect($result)->toBeFalse();
        });

        it('handles unary not with other operand', function () {
            $operand = new StringNode('string');

            $node = new UnaryNode('not', $operand);

            $result = $this->evaluator->evaluate($node);

            expect($result)->toBeFalse();
        });

        it('throws exception when namespace is string starting with $', function () {
            $propertyNode  = new IdentifierNode('prop');
            $namespaceNode = new IdentifierNode('$namespace');

            $variableHandler = mock(VariableHandler::class);
            $variableHandler->shouldReceive('get')
                ->with('$namespace')
                ->andReturn('$namespace');

            $this->context->variableHandler = $variableHandler;

            $node = new PropertyAccessNode($namespaceNode, $propertyNode);

            expect(fn() => $this->evaluator->evaluate($node))
                ->toThrow(CompilationException::class);
        });

        it('throws exception when namespace is not string', function () {
            $propertyNode  = new IdentifierNode('prop');
            $namespaceNode = new NumberNode(123.0);

            $node = new PropertyAccessNode($namespaceNode, $propertyNode);

            expect(fn() => $this->evaluator->evaluate($node))
                ->toThrow(CompilationException::class);
        });

        it('handles SelectorNode by returning its value', function () {
            $node = new SelectorNode('.my-class');

            $result = $this->evaluator->evaluate($node);

            expect($result)->toBe('.my-class');
        });

        it('handles OperatorNode by returning its value', function () {
            $node = new OperatorNode('+');

            $result = $this->evaluator->evaluate($node);

            expect($result)->toBe('+');
        });
    });

    describe('evaluateFunctionExpression()', function () {
        it('calls evaluateFunctionWithSlashSeparator when hasSlashSeparator returns true', function () {
            $functionHandler = mock(FunctionHandler::class);
            $functionHandler->shouldReceive('call')
                ->with('hsl', [120, ['value' => 50.0, 'unit' => '%'], ['value' => 50.0, 'unit' => '%'], 0.5])
                ->andReturn('hsl(120 50% 50% / 0.5)');

            $this->context->functionHandler = $functionHandler;

            $leftNode = new NumberNode(50);
            $leftNode->unit = '%';
            $rightNode = new NumberNode(0.5);

            $operationNode = new OperationNode($leftNode, '/', $rightNode, 0);

            $args = [120, '50%', $operationNode];

            $expr = new FunctionNode('hsl', $args);

            $result = $this->accessor->callMethod('evaluateFunctionExpression', [$expr]);

            expect($result)->toBe('hsl(120 50% 50% / 0.5)');
        });
    });

    describe('expandSpreadArguments()', function () {
        it('handles empty array', function () {
            $result = $this->accessor->callMethod('expandSpreadArguments', [[]]);

            expect($result)->toBe([]);
        });

        it('handles regular args without spread', function () {
            $args = ['arg1', 'arg2'];

            $result = $this->accessor->callMethod('expandSpreadArguments', [$args]);

            expect($result)->toBe(['arg1', 'arg2']);
        });

        it('handles single spread', function () {
            $spreadValue = new SassList(['a', 'b', 'c'], 'comma');

            $args = [['type' => 'spread', 'value' => $spreadValue]];

            $result = $this->accessor->callMethod('expandSpreadArguments', [$args]);

            expect($result)->toBe(['a', 'b', 'c']);
        });

        it('handles multiple spread', function () {
            $spread1 = new SassList(['a'], 'comma');
            $spread2 = new SassList(['b', 'c'], 'comma');

            $args = [
                ['type' => 'spread', 'value' => $spread1],
                ['type' => 'spread', 'value' => $spread2],
            ];

            $result = $this->accessor->callMethod('expandSpreadArguments', [$args]);

            expect($result)->toBe(['a', 'b', 'c']);
        });

        it('handles mixed args and spread', function () {
            $spreadValue = new SassList(['x', 'y'], 'comma');

            $args = ['arg1', ['type' => 'spread', 'value' => $spreadValue], 'arg2'];

            $result = $this->accessor->callMethod('expandSpreadArguments', [$args]);

            expect($result)->toBe(['arg1', 'x', 'y', 'arg2']);
        });
    });

    describe('appendSpreadValues()', function () {
        it('handles SassList', function () {
            $sassList = new SassList(['item1', 'item2'], 'comma');
            $processedArgs = ['existing'];

            $result = $this->accessor->callMethod('appendSpreadValues', [$processedArgs, $sassList]);

            expect($result)->toBe(['existing', 'item1', 'item2']);
        });

        it('handles ListNode', function () {
            $listNode = new ListNode(['node1', 'node2']);
            $processedArgs = ['existing'];

            $result = $this->accessor->callMethod('appendSpreadValues', [$processedArgs, $listNode]);

            expect($result)->toBe(['existing', 'node1', 'node2']);
        });

        it('handles array', function () {
            $array = ['elem1', 'elem2'];
            $processedArgs = ['existing'];

            $result = $this->accessor->callMethod('appendSpreadValues', [$processedArgs, $array]);

            expect($result)->toBe(['existing', 'elem1', 'elem2']);
        });

        it('handles non-array value', function () {
            $value = 'single';
            $processedArgs = ['existing'];

            $result = $this->accessor->callMethod('appendSpreadValues', [$processedArgs, $value]);

            expect($result)->toBe(['existing', 'single']);
        });
    });

    describe('evaluateVariableString()', function () {
        it('successfully accesses property with namespace', function () {
            $moduleHandler = mock(ModuleHandler::class);
            $moduleHandler->shouldReceive('getProperty')
                ->with('$color', '$red', Mockery::type('callable'))
                ->andReturn('#ff0000');

            $this->context->moduleHandler = $moduleHandler;

            $result = $this->accessor->callMethod('evaluateVariableString', ['$color.red']);

            expect($result)->toBe('#ff0000');
        });

        it('falls back to variable when module property fails', function () {
            $moduleHandler = mock(ModuleHandler::class);
            $moduleHandler->shouldReceive('getProperty')
                ->with('$color', '$red', Mockery::type('callable'))
                ->andThrow(new CompilationException('Property not found'));

            $variableHandler = mock(VariableHandler::class);
            $variableHandler->shouldReceive('get')
                ->with('$color.red')
                ->andReturn('#ff0000');

            $this->context->moduleHandler  = $moduleHandler;
            $this->context->variableHandler = $variableHandler;

            $result = $this->accessor->callMethod('evaluateVariableString', ['$color.red']);

            expect($result)->toBe('#ff0000');
        });

        it('throws exception for undefined property', function () {
            $moduleHandler = mock(ModuleHandler::class);
            $moduleHandler->shouldReceive('getProperty')
                ->with('$color', '$red', Mockery::type('callable'))
                ->andThrow(new CompilationException('Property not found'));

            $variableHandler = mock(VariableHandler::class);
            $variableHandler->shouldReceive('get')
                ->with('$color.red')
                ->andThrow(new CompilationException('Variable not found'));

            $this->context->moduleHandler  = $moduleHandler;
            $this->context->variableHandler = $variableHandler;

            expect(fn() => $this->accessor->callMethod('evaluateVariableString', ['$color.red']))
                ->toThrow(CompilationException::class, 'Undefined property: $red in module $color');
        });

        it('handles regular variable without dot', function () {
            $variableHandler = mock(VariableHandler::class);
            $variableHandler->shouldReceive('get')
                ->with('$myVar')
                ->andReturn('value');

            $this->context->variableHandler = $variableHandler;

            $result = $this->accessor->callMethod('evaluateVariableString', ['$myVar']);

            expect($result)->toBe('value');
        });
    });

    describe('tryParseNumericString()', function () {
        it('parses integer without unit', function () {
            $result = $this->accessor->callMethod('tryParseNumericString', ['123']);

            expect($result)->toBe(123.0);
        });

        it('parses decimal without unit', function () {
            $result = $this->accessor->callMethod('tryParseNumericString', ['123.45']);

            expect($result)->toBe(123.45);
        });

        it('parses zero without unit', function () {
            $result = $this->accessor->callMethod('tryParseNumericString', ['0']);

            expect($result)->toBe(0.0);
        });

        it('parses decimal starting with zero without unit', function () {
            $result = $this->accessor->callMethod('tryParseNumericString', ['0.5']);

            expect($result)->toBe(0.5);
        });

        it('parses number with px unit', function () {
            $result = $this->accessor->callMethod('tryParseNumericString', ['123px']);

            expect($result)->toEqual(['value' => 123.0, 'unit' => 'px']);
        });

        it('parses number with em unit', function () {
            $result = $this->accessor->callMethod('tryParseNumericString', ['1.5em']);

            expect($result)->toEqual(['value' => 1.5, 'unit' => 'em']);
        });

        it('parses number with rem unit', function () {
            $result = $this->accessor->callMethod('tryParseNumericString', ['2rem']);

            expect($result)->toEqual(['value' => 2.0, 'unit' => 'rem']);
        });

        it('parses number with % unit', function () {
            $result = $this->accessor->callMethod('tryParseNumericString', ['100%']);

            expect($result)->toEqual(['value' => 100.0, 'unit' => '%']);
        });

        it('parses number with space before unit', function () {
            $result = $this->accessor->callMethod('tryParseNumericString', ['123 px']);

            expect($result)->toEqual(['value' => 123.0, 'unit' => 'px']);
        });

        it('returns string unchanged for non-numeric string', function () {
            $result = $this->accessor->callMethod('tryParseNumericString', ['abc']);

            expect($result)->toBe('abc');
        });

        it('returns string unchanged for string starting with number but containing letters', function () {
            $result = $this->accessor->callMethod('tryParseNumericString', ['123abc']);

            expect($result)->toBe('123abc');
        });

        it('returns string unchanged for string with words', function () {
            $result = $this->accessor->callMethod('tryParseNumericString', ['hello world']);

            expect($result)->toBe('hello world');
        });

        it('returns string unchanged for empty string', function () {
            $result = $this->accessor->callMethod('tryParseNumericString', ['']);

            expect($result)->toBe('');
        });

        it('returns string unchanged for string with only spaces', function () {
            $result = $this->accessor->callMethod('tryParseNumericString', ['   ']);

            expect($result)->toBe('   ');
        });
    });

    describe('evaluateIdentifierExpression()', function () {
        it('evaluates "true" to boolean true', function () {
            $node = new IdentifierNode('true');

            $result = $this->accessor->callMethod('evaluateIdentifierExpression', [$node]);

            expect($result)->toBeTrue();
        });

        it('evaluates "false" to boolean false', function () {
            $node = new IdentifierNode('false');

            $result = $this->accessor->callMethod('evaluateIdentifierExpression', [$node]);

            expect($result)->toBeFalse();
        });

        it('evaluates "null" to null', function () {
            $node = new IdentifierNode('null');

            $result = $this->accessor->callMethod('evaluateIdentifierExpression', [$node]);

            expect($result)->toBeNull();
        });

        it('evaluates other string to itself', function () {
            $node = new IdentifierNode('someValue');

            $result = $this->accessor->callMethod('evaluateIdentifierExpression', [$node]);

            expect($result)->toBe('someValue');
        });
    });

    describe('convertKeyToString()', function () {
        it('converts numeric key to string', function () {
            $result = $this->accessor->callMethod('convertKeyToString', [123]);

            expect($result)->toBe('123');
        });

        it('converts float key to string', function () {
            $result = $this->accessor->callMethod('convertKeyToString', [45.67]);

            expect($result)->toBe('45.67');
        });

        it('returns string for string key', function () {
            $result = $this->accessor->callMethod('convertKeyToString', ['string']);

            expect($result)->toBe('string');
        });

        it('trims quotes from string key', function () {
            $result = $this->accessor->callMethod('convertKeyToString', ['"quoted"']);

            expect($result)->toBe('quoted');
        });

        it('returns null for boolean key', function () {
            $result = $this->accessor->callMethod('convertKeyToString', [true]);

            expect($result)->toBeNull();
        });

        it('returns null for null key', function () {
            $result = $this->accessor->callMethod('convertKeyToString', [null]);

            expect($result)->toBeNull();
        });
    });

    describe('evaluateFunctionWithSlashSeparator()', function () {
        it('evaluates function with slash separator correctly', function () {
            $functionHandler = mock(FunctionHandler::class);
            $functionHandler->shouldReceive('call')
                ->with('hsl', [120, ['value' => 50.0, 'unit' => '%'], ['value' => 50.0, 'unit' => '%'], 0.5])
                ->andReturn('hsl(120 50% 50% / 0.5)');

            $this->context->functionHandler = $functionHandler;

            $leftNode = new NumberNode(50);
            $leftNode->unit = '%';
            $rightNode = new NumberNode(0.5);

            $operationNode = new OperationNode($leftNode, '/', $rightNode, 0);

            $args = [120, '50%', $operationNode];

            $result = $this->accessor->callMethod('evaluateFunctionWithSlashSeparator', ['hsl', $args]);

            expect($result)->toBe('hsl(120 50% 50% / 0.5)');
        });
    });
});
