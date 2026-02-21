<?php

declare(strict_types=1);

use DartSass\Compilers\Strategies\SupportsRuleStrategy;
use DartSass\Parsers\Nodes\NodeType;
use DartSass\Parsers\Nodes\SupportsNode;

describe('SupportsRuleStrategy', function () {
    beforeEach(function () {
        $this->strategy = new SupportsRuleStrategy();
    });

    describe('canHandle()', function () {
        it('returns true for SUPPORTS node type', function () {
            expect($this->strategy->canHandle(NodeType::SUPPORTS))->toBeTrue();
        });

        it('returns false for other node types', function () {
            expect($this->strategy->canHandle(NodeType::MEDIA))->toBeFalse()
                ->and($this->strategy->canHandle(NodeType::RULE))->toBeFalse()
                ->and($this->strategy->canHandle(NodeType::AT_ROOT))->toBeFalse();
        });
    });

    describe('compile()', function () {
        it('throws InvalidArgumentException when required parameters are missing', function () {
            $node = new SupportsNode('(display: grid)', [
                'declarations' => [],
                'nested'       => [],
            ], 1);

            expect(fn() => $this->strategy->compile($node, '', 0))
                ->toThrow(
                    InvalidArgumentException::class,
                    'Missing required parameters for @supports rule compilation'
                );
        });

        it('compiles basic @supports rule', function () {
            $node = new SupportsNode('(display: grid)', [
                'declarations' => [['color', 'red', 1]],
                'nested'       => [],
            ], 1);

            $evaluateExpression     = fn($q) => $q;
            $compileDeclarations    = fn($decls, $selector, $level) => "  color: red;\n";
            $compileAst             = fn($nested, $selector, $level) => '';
            $evaluateInterpolations = fn($q) => $q;
            $formatValue            = fn($q) => $q;

            $result = $this->strategy->compile(
                $node,
                '.container',
                0,
                $evaluateExpression,
                $compileDeclarations,
                $compileAst,
                $evaluateInterpolations,
                $formatValue
            );

            expect($result)->toContain('@supports (display: grid)')
                ->and($result)->toContain('color: red');
        });

        it('compiles @supports rule with nested rules', function () {
            $nestedNode = new SupportsNode('(display: flex)', [
                'declarations' => [],
                'nested'       => [],
            ], 2);

            $node = new SupportsNode('(display: grid)', [
                'declarations' => [],
                'nested'       => [$nestedNode],
            ], 1);

            $evaluateExpression     = fn($q) => $q;
            $compileDeclarations    = fn($decls, $selector, $level) => '';
            $compileAst             = fn($nested, $selector, $level) => "  @supports (display: flex) {\n  }\n";
            $evaluateInterpolations = fn($q) => $q;
            $formatValue            = fn($q) => $q;

            $result = $this->strategy->compile(
                $node,
                '.container',
                0,
                $evaluateExpression,
                $compileDeclarations,
                $compileAst,
                $evaluateInterpolations,
                $formatValue
            );

            expect($result)->toContain('@supports (display: grid)')
                ->and($result)->toContain('@supports (display: flex)');
        });

        it('applies interpolation evaluation to query', function () {
            $node = new SupportsNode('($feature)', [
                'declarations' => [],
                'nested'       => [],
            ], 1);

            $evaluateExpression     = fn($q) => $q;
            $compileDeclarations    = fn($decls, $selector, $level) => '';
            $compileAst             = fn($nested, $selector, $level) => '';
            $evaluateInterpolations = fn($q) => str_replace('$feature', 'grid', $q);
            $formatValue            = fn($q) => $q;

            $result = $this->strategy->compile(
                $node,
                '',
                0,
                $evaluateExpression,
                $compileDeclarations,
                $compileAst,
                $evaluateInterpolations,
                $formatValue
            );

            expect($result)->toContain('@supports (grid)');
        });

        it('applies expression evaluation to query', function () {
            $node = new SupportsNode('($feature + 3)', [
                'declarations' => [],
                'nested'       => [],
            ], 1);

            $evaluateExpression     = fn($q) => str_replace('$feature + 3', 'feature23', $q);
            $compileDeclarations    = fn($decls, $selector, $level) => '';
            $compileAst             = fn($nested, $selector, $level) => '';
            $evaluateInterpolations = fn($q) => $q;
            $formatValue            = fn($q) => $q;

            $result = $this->strategy->compile(
                $node,
                '',
                0,
                $evaluateExpression,
                $compileDeclarations,
                $compileAst,
                $evaluateInterpolations,
                $formatValue
            );

            expect($result)->toContain('@supports (feature23)');
        });

        it('applies formatting to query', function () {
            $node = new SupportsNode('(display: grid)', [
                'declarations' => [],
                'nested'       => [],
            ], 1);

            $evaluateExpression     = fn($q) => $q;
            $compileDeclarations    = fn($decls, $selector, $level) => '';
            $compileAst             = fn($nested, $selector, $level) => '';
            $evaluateInterpolations = fn($q) => $q;
            $formatValue            = fn($q) => strtoupper($q);

            $result = $this->strategy->compile(
                $node,
                '',
                0,
                $evaluateExpression,
                $compileDeclarations,
                $compileAst,
                $evaluateInterpolations,
                $formatValue
            );

            expect($result)->toContain('@supports (DISPLAY: GRID)');
        });

        it('compiles with correct indentation at nesting level 0', function () {
            $node = new SupportsNode('(display: grid)', [
                'declarations' => [],
                'nested'       => [],
            ], 1);

            $evaluateExpression     = fn($q) => $q;
            $compileDeclarations    = fn($decls, $selector, $level) => '';
            $compileAst             = fn($nested, $selector, $level) => '';
            $evaluateInterpolations = fn($q) => $q;
            $formatValue            = fn($q) => $q;

            $result = $this->strategy->compile(
                $node,
                '',
                0,
                $evaluateExpression,
                $compileDeclarations,
                $compileAst,
                $evaluateInterpolations,
                $formatValue
            );

            expect($result)->toStartWith('@supports');
        });

        it('compiles with correct indentation at nesting level 1', function () {
            $node = new SupportsNode('(display: grid)', [
                'declarations' => [],
                'nested'       => [],
            ], 1);

            $evaluateExpression     = fn($q) => $q;
            $compileDeclarations    = fn($decls, $selector, $level) => '';
            $compileAst             = fn($nested, $selector, $level) => '';
            $evaluateInterpolations = fn($q) => $q;
            $formatValue            = fn($q) => $q;

            $result = $this->strategy->compile(
                $node,
                '',
                1,
                $evaluateExpression,
                $compileDeclarations,
                $compileAst,
                $evaluateInterpolations,
                $formatValue
            );

            expect($result)->toStartWith('  @supports');
        });

        it('compiles declarations with parent selector', function () {
            $node = new SupportsNode('(display: grid)', [
                'declarations' => [['color', 'red', 1]],
                'nested'       => [],
            ], 1);

            $evaluateExpression     = fn($q) => $q;
            $compileDeclarations    = fn($decls, $selector, $level) => "    color: red;\n";
            $compileAst             = fn($nested, $selector, $level) => '';
            $evaluateInterpolations = fn($q) => $q;
            $formatValue            = fn($q) => $q;

            $result = $this->strategy->compile(
                $node,
                '.container',
                0,
                $evaluateExpression,
                $compileDeclarations,
                $compileAst,
                $evaluateInterpolations,
                $formatValue
            );

            expect($result)->toContain('.container {')
                ->and($result)->toContain('color: red');
        });

        it('handles empty body gracefully', function () {
            $node = new SupportsNode('(display: grid)', [
                'declarations' => [],
                'nested'       => [],
            ], 1);

            $evaluateExpression     = fn($q) => $q;
            $compileDeclarations    = fn($decls, $selector, $level) => '';
            $compileAst             = fn($nested, $selector, $level) => '';
            $evaluateInterpolations = fn($q) => $q;
            $formatValue            = fn($q) => $q;

            $result = $this->strategy->compile(
                $node,
                '',
                0,
                $evaluateExpression,
                $compileDeclarations,
                $compileAst,
                $evaluateInterpolations,
                $formatValue
            );

            expect($result)->toBe("@supports (display: grid) {\n\n}\n");
        });

        it('compiles declarations without parent selector', function () {
            $node = new SupportsNode('(display: grid)', [
                'declarations' => [['color', 'red', 1]],
                'nested'       => [],
            ], 1);

            $evaluateExpression     = fn($q) => $q;
            $compileDeclarations    = fn($decls, $selector, $level) => "  color: red;\n";
            $compileAst             = fn($nested, $selector, $level) => '';
            $evaluateInterpolations = fn($q) => $q;
            $formatValue            = fn($q) => $q;

            $result = $this->strategy->compile(
                $node,
                '',
                0,
                $evaluateExpression,
                $compileDeclarations,
                $compileAst,
                $evaluateInterpolations,
                $formatValue
            );

            expect($result)->toContain('color: red')
                ->and($result)->toContain('@supports (display: grid)');
        });
    });
});
