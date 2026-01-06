<?php

declare(strict_types=1);

use DartSass\Compilers\FlowControlCompiler;
use DartSass\Exceptions\CompilationException;
use DartSass\Handlers\VariableHandler;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\EachNode;
use DartSass\Parsers\Nodes\IfNode;
use DartSass\Parsers\Nodes\WhileNode;
use Tests\ReflectionAccessor;

describe('FlowControlCompiler', function () {
    beforeEach(function () {
        $this->variableHandler = mock(VariableHandler::class);
        $this->compiler        = new FlowControlCompiler($this->variableHandler);
        $this->accessor        = new ReflectionAccessor($this->compiler);
    });

    describe('compileIf method', function () {
        it('returns empty string when condition is falsy and no else block exists', function () {
            $condition = new AstNode('condition', []);
            $body      = [];
            $node      = new IfNode($condition, $body);

            $evaluateExpression = fn($cond) => false; // falsy
            $compileAst         = fn($nodes, $prefix, $level) => 'compiled';

            $result = $this->accessor->callMethod('compileIf', [$node, 0, $evaluateExpression, $compileAst]);

            expect($result)->toBe('');
        });
    });

    describe('compileEach method', function () {
        it('wraps non-array $list into array', function () {
            $variables = ['$item'];
            $condition = new AstNode('condition', []);
            $body      = [];
            $node      = new EachNode($variables, $condition, $body, 0);

            $this->variableHandler->shouldReceive('enterScope')->once();
            $this->variableHandler->shouldReceive('define')->with('$item', 'value')->once();
            $this->variableHandler->shouldReceive('exitScope')->once();

            $evaluateExpression = fn($cond) => 'value'; // non-array
            $compileAst         = fn($nodes, $prefix, $level) => 'compiled';

            $result = $this->accessor->callMethod('compileEach', [$node, 0, $evaluateExpression, $compileAst]);

            expect($result)->toBe('compiled');
        });
    });

    describe('compileWhile method', function () {
        it('throws CompilationException when iteration exceeds 1000', function () {
            $condition = new AstNode('condition', []);
            $body      = [];
            $node      = new WhileNode($condition, $body, 0);

            $this->variableHandler->shouldReceive('enterScope')->once();
            $this->variableHandler->shouldReceive('exitScope')->once();

            $evaluateExpression = fn($cond) => true; // always truthy
            $compileAst         = fn($nodes, $prefix, $level) => '';

            expect(fn() => $this->accessor->callMethod('compileWhile', [$node, 0, $evaluateExpression, $compileAst]))
                ->toThrow(CompilationException::class, 'Maximum @while iterations exceeded (1000)');
        });
    });
});
