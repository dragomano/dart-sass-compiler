<?php

declare(strict_types=1);

use DartSass\Compilers\FlowControlCompiler;
use DartSass\Exceptions\CompilationException;
use DartSass\Handlers\VariableHandler;
use DartSass\Parsers\Nodes\ConditionNode;
use DartSass\Parsers\Nodes\EachNode;
use DartSass\Parsers\Nodes\IdentifierNode;
use DartSass\Parsers\Nodes\IfNode;
use DartSass\Parsers\Nodes\WhileNode;
use Tests\ReflectionAccessor;

describe('FlowControlCompiler', function () {
    beforeEach(function () {
        $this->handler  = mock(VariableHandler::class);
        $this->compiler = new FlowControlCompiler($this->handler);
        $this->accessor = new ReflectionAccessor($this->compiler);
    });

    describe('compileIf method', function () {
        it('returns empty string when condition is falsy and no else block exists', function () {
            $condition = new ConditionNode(new IdentifierNode('true', 1), 1);

            $body = [];
            $node = new IfNode($condition, $body);

            $expression = fn($cond) => false; // falsy
            $compileAst = fn($nodes, $prefix, $level) => 'compiled';

            $result = $this->accessor->callMethod('compileIf', [$node, '', 0, $expression, $compileAst]);

            expect($result)->toBe('');
        });

        it('compiles array declarations in if body', function () {
            $condition = new ConditionNode(new IdentifierNode('true', 1), 1);

            $body = [['width', '100px'], ['height', '200px']];
            $node = new IfNode($condition, $body);

            $expression = fn($cond) => true; // truthy
            $compileAst = fn($nodes, $prefix, $level) => 'compiled';

            $result = $this->accessor->callMethod('compileIf', [$node, '', 0, $expression, $compileAst]);

            expect($result)->toBe("  width: 100px;\n  height: 200px;\n");
        });
    });

    describe('compileEach method', function () {
        it('wraps non-array $list into array', function () {
            $variables = ['$item'];
            $condition = new ConditionNode(new IdentifierNode('true', 1), 1);

            $body = [];
            $node = new EachNode($variables, $condition, $body, 0);

            $this->handler->shouldReceive('enterScope')->once();
            $this->handler->shouldReceive('define')->with('$item', 'value')->once();
            $this->handler->shouldReceive('exitScope')->once();

            $expression = fn($cond) => 'value'; // non-array
            $compileAst = fn($nodes, $prefix, $level) => 'compiled';

            $result = $this->accessor->callMethod('compileEach', [$node, '', 0, $expression, $compileAst]);

            expect($result)->toBe('compiled');
        });
    });

    describe('compileWhile method', function () {
        it('throws CompilationException when iteration exceeds 1000', function () {
            $condition = new ConditionNode(new IdentifierNode('true', 1), 1);

            $body = [];
            $node = new WhileNode($condition, $body, 0);

            $this->handler->shouldReceive('enterScope')->once();
            $this->handler->shouldReceive('exitScope')->once();

            $expression = fn($cond) => true; // always truthy
            $compileAst = fn($nodes, $prefix, $level) => '';

            expect(fn() => $this->accessor->callMethod('compileWhile', [$node, '', 0, $expression, $compileAst]))
                ->toThrow(CompilationException::class, 'Maximum @while iterations exceeded (1000)');
        });
    });
});
