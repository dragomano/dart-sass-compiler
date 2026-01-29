<?php

declare(strict_types=1);

namespace DartSass\Compilers;

use DartSass\Evaluators\CalcFunctionEvaluator;
use DartSass\Evaluators\ExpressionEvaluator;
use DartSass\Evaluators\InterpolationEvaluator;
use DartSass\Evaluators\OperationEvaluator;
use DartSass\Handlers\ExtendHandler;
use DartSass\Handlers\FunctionHandler;
use DartSass\Handlers\MixinHandler;
use DartSass\Handlers\ModuleHandler;
use DartSass\Handlers\NestingHandler;
use DartSass\Handlers\VariableHandler;
use DartSass\Loaders\LoaderInterface;
use DartSass\Parsers\ParserFactory;
use DartSass\Utils\OutputOptimizer;
use DartSass\Utils\PositionTracker;
use DartSass\Utils\ResultFormatterInterface;
use DartSass\Utils\SourceMapGenerator;

class CompilerContext
{
    public array $mappings = [];

    public Environment $environment;

    public LoaderInterface $loader;

    public ParserFactory $parserFactory;

    public ResultFormatterInterface $resultFormatter;

    public PositionTracker $positionTracker;

    public VariableHandler $variableHandler;

    public MixinHandler $mixinHandler;

    public NestingHandler $nestingHandler;

    public ExtendHandler $extendHandler;

    public ModuleHandler $moduleHandler;

    public FunctionHandler $functionHandler;

    public OutputOptimizer $outputOptimizer;

    public SourceMapGenerator $sourceMapGenerator;

    public InterpolationEvaluator $interpolationEvaluator;

    public OperationEvaluator $operationEvaluator;

    public CalcFunctionEvaluator $calcEvaluator;

    public ExpressionEvaluator $expressionEvaluator;

    public RuleCompiler $ruleCompiler;

    public FlowControlCompiler $flowControlCompiler;

    public DeclarationCompiler $declarationCompiler;

    public MixinCompiler $mixinCompiler;

    public AtRuleCompiler $atRuleCompiler;

    public ModuleCompiler $moduleCompiler;

    public CompilerEngineInterface $engine;

    public function __construct(public array $options) {}
}
