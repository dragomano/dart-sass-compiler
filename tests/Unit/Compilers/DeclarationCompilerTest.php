<?php

declare(strict_types=1);

use DartSass\Compilers\DeclarationCompiler;
use DartSass\Parsers\Nodes\CommentNode;
use DartSass\Parsers\Nodes\IdentifierNode;
use DartSass\Parsers\Nodes\RuleNode;
use DartSass\Utils\PositionTracker;
use DartSass\Utils\ResultFormatterInterface;

describe('DeclarationCompiler', function () {
    beforeEach(function () {
        $this->resultFormatter     = mock(ResultFormatterInterface::class);
        $this->positionTracker     = mock(PositionTracker::class);
        $this->declarationCompiler = new DeclarationCompiler($this->resultFormatter, $this->positionTracker);
    });

    describe('compile method', function () {
        it('calls compileAst when declaration is an AstNode', function () {
            $selector = new IdentifierNode('body', 1);
            $astNode  = new RuleNode($selector, [], [], 1);

            $declarations   = [$astNode];
            $nestingLevel   = 0;
            $parentSelector = '';

            $compileAstCalled = false;
            $compileAst = function ($nodes, $selector, $level) use (
                &$compileAstCalled,
                $astNode,
                $parentSelector,
                $nestingLevel
            ) {
                $compileAstCalled = true;
                expect($nodes)->toEqual([$astNode])
                    ->and($selector)->toBe($parentSelector)
                    ->and($level)->toBe($nestingLevel);

                return 'compiled css';
            };

            $expression = fn() => 'value';

            $result = $this->declarationCompiler->compile(
                $declarations,
                $parentSelector,
                $nestingLevel,
                $compileAst,
                $expression,
                fn(string $value): string => $value
            );

            expect($compileAstCalled)->toBeTrue()
                ->and($result)->toBe('compiled css');
        });

        it('skips declaration when evaluated value is null', function () {
            $declaration    = ['property' => 'value'];
            $declarations   = [$declaration];
            $nestingLevel   = 0;
            $parentSelector = '';

            $this->positionTracker
                ->shouldReceive('getCurrentPosition')
                ->once()
                ->andReturn(['line' => 1, 'column' => 0]);

            $compileAst = fn() => 'should not be called';
            $expression = fn() => null;

            $result = $this->declarationCompiler->compile(
                $declarations,
                $parentSelector,
                $nestingLevel,
                $compileAst,
                $expression,
                fn(string $value): string => $value
            );

            expect($result)->toBe('');
        });

        it('adds !important when value has important flag', function () {
            $value = new IdentifierNode('red', 1);
            $value->important = true;

            $declaration    = ['color' => $value];
            $declarations   = [$declaration];
            $nestingLevel   = 0;
            $parentSelector = '';

            $this->resultFormatter
                ->shouldReceive('format')
                ->once()
                ->with($value)
                ->andReturn('red');

            $this->positionTracker
                ->shouldReceive('getCurrentPosition')
                ->once()
                ->andReturn(['line' => 1, 'column' => 0]);

            $this->positionTracker
                ->shouldReceive('updatePosition')
                ->once()
                ->with("color: red !important;\n");

            $compileAst = fn() => 'should not be called';
            $expression = fn($val) => $val;

            $result = $this->declarationCompiler->compile(
                $declarations,
                $parentSelector,
                $nestingLevel,
                $compileAst,
                $expression,
                fn(string $value): string => $value
            );

            expect($result)->toBe("color: red !important;\n");
        });

        it('removes single-line comments (starting with //)', function () {
            $commentNode = new CommentNode('// comment');

            $declarations   = [$commentNode];
            $nestingLevel   = 0;
            $parentSelector = '';

            $this->positionTracker
                ->shouldReceive('updatePosition')
                ->never();

            $compileAst = fn() => 'should not be called';
            $expression = fn() => 'value';

            $result = $this->declarationCompiler->compile(
                $declarations,
                $parentSelector,
                $nestingLevel,
                $compileAst,
                $expression,
                fn(string $value): string => $value
            );

            expect($result)->toBe('');
        });
    });
});
