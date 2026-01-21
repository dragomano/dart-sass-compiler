<?php

declare(strict_types=1);

use DartSass\Compilers\CompilerContext;
use DartSass\Compilers\CompilerEngineInterface;
use DartSass\Compilers\DeclarationCompiler;
use DartSass\Compilers\Nodes\RuleNodeCompiler;
use DartSass\Evaluators\InterpolationEvaluator;
use DartSass\Handlers\ExtendHandler;
use DartSass\Handlers\NestingHandler;
use DartSass\Handlers\VariableHandler;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\RuleNode;
use DartSass\Parsers\Nodes\StringNode;
use DartSass\Parsers\ParserFactory;
use DartSass\Utils\PositionTracker;
use DartSass\Utils\ResultFormatterInterface;
use Tests\ReflectionAccessor;

describe('RuleNodeCompiler', function () {
    beforeEach(function () {
        $this->compiler = new RuleNodeCompiler();
        $this->accessor = new ReflectionAccessor($this->compiler);
    });

    it('returns null when string is null in evaluateInterpolationsInString', function () {
        $context = mock(CompilerContext::class);

        $result = $this->accessor->callMethod('evaluateInterpolationsInString', [null, $context]);

        expect($result)->toBeNull();
    });

    it('sets selectorString to null when selector is not SelectorNode and calls resolveSelector with null', function () {
        $node = new RuleNode(
            mock(AstNode::class), // dummy selector
            [], // declarations
            [], // nested
            0, // line
        );
        $node->properties['selector'] = 'not a SelectorNode'; // Non-SelectorNode value

        $nestingHandler = mock(NestingHandler::class);
        $nestingHandler->shouldReceive('resolveSelector')
            ->once()
            ->with(null, '')
            ->andReturn('resolved-selector');

        $variableHandler = mock(VariableHandler::class);
        $variableHandler->shouldReceive('enterScope')->once();
        $variableHandler->shouldReceive('exitScope')->once();

        $resultFormatter        = mock(ResultFormatterInterface::class);
        $positionTrackerForDecl = mock(PositionTracker::class);
        $declarationCompiler    = new DeclarationCompiler($resultFormatter, $positionTrackerForDecl);

        $positionTracker = mock(PositionTracker::class);
        $positionTracker->shouldReceive('updatePosition')->andReturn();

        $extendHandler = mock(ExtendHandler::class);
        $extendHandler->shouldReceive('addDefinedSelector')->once();

        $resultFormatter        = mock(ResultFormatterInterface::class);
        $parserFactory          = mock(ParserFactory::class);
        $interpolationEvaluator = new InterpolationEvaluator($resultFormatter, $parserFactory);

        $context = mock(CompilerContext::class);
        $context->nestingHandler         = $nestingHandler;
        $context->variableHandler        = $variableHandler;
        $context->declarationCompiler    = $declarationCompiler;
        $context->positionTracker        = $positionTracker;
        $context->extendHandler          = $extendHandler;
        $context->interpolationEvaluator = $interpolationEvaluator;
        $context->options                = ['sourceMap' => false];
        $context->mappings               = [];

        // Mock engine with necessary methods
        $context->engine = mock(CompilerEngineInterface::class);
        $context->engine->shouldReceive('getIndent')->andReturn('');

        $result = $this->accessor->callMethod('compileNode', [$node, $context]);

        expect($result)->toBeString();
    });

    it('returns empty string when node is not instance of RuleNode', function () {
        $node    = new StringNode('test', 0);
        $context = mock(CompilerContext::class);

        $result = $this->compiler->compile($node, $context);

        expect($result)->toBe('');
    });
});
