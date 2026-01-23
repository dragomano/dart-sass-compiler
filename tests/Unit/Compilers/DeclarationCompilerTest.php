<?php

declare(strict_types=1);

use DartSass\Compilers\CompilerContext;
use DartSass\Compilers\DeclarationCompiler;
use DartSass\Parsers\Nodes\IdentifierNode;
use DartSass\Parsers\Nodes\RuleNode;
use DartSass\Utils\PositionTracker;
use DartSass\Utils\ResultFormatterInterface;

describe('DeclarationCompiler', function () {
    beforeEach(function () {
        $this->resultFormatter     = mock(ResultFormatterInterface::class);
        $this->positionTracker     = mock(PositionTracker::class);
        $this->declarationCompiler = new DeclarationCompiler($this->resultFormatter, $this->positionTracker);
        $this->context             = mock(CompilerContext::class);
        $this->context->options    = ['sourceMap' => false];
        $this->context->mappings   = [];
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
                $nestingLevel,
                $parentSelector,
                $this->context,
                $compileAst,
                $expression
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
                $nestingLevel,
                $parentSelector,
                $this->context,
                $compileAst,
                $expression
            );

            expect($result)->toBe('');
        });
    });
});
