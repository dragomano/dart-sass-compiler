<?php

declare(strict_types=1);

namespace DartSass\Compilers;

use DartSass\Evaluators\CalcFunctionEvaluator;
use DartSass\Evaluators\ExpressionEvaluator;
use DartSass\Evaluators\InterpolationEvaluator;
use DartSass\Evaluators\OperationEvaluator;
use DartSass\Evaluators\UserFunctionEvaluator;
use DartSass\Handlers\BuiltInModuleProvider;
use DartSass\Handlers\ColorModuleHandler;
use DartSass\Handlers\CssFunctionHandler;
use DartSass\Handlers\CustomFunctionHandler;
use DartSass\Handlers\ExtendHandler;
use DartSass\Handlers\FormatFunctionHandler;
use DartSass\Handlers\FunctionHandler;
use DartSass\Handlers\FunctionRouter;
use DartSass\Handlers\IfFunctionHandler;
use DartSass\Handlers\ListModuleHandler;
use DartSass\Handlers\MathModuleHandler;
use DartSass\Handlers\MixinHandler;
use DartSass\Handlers\ModuleForwarder;
use DartSass\Handlers\ModuleHandler;
use DartSass\Handlers\ModuleLoader;
use DartSass\Handlers\ModuleRegistry;
use DartSass\Handlers\NestingHandler;
use DartSass\Handlers\StringModuleHandler;
use DartSass\Handlers\UrlFunctionHandler;
use DartSass\Handlers\VariableHandler;
use DartSass\Loaders\LoaderInterface;
use DartSass\Modules\ColorModule;
use DartSass\Modules\ListModule;
use DartSass\Modules\MathModule;
use DartSass\Modules\StringModule;
use DartSass\Parsers\ParserFactory;
use DartSass\Utils\OutputOptimizer;
use DartSass\Utils\PositionTracker;
use DartSass\Utils\ResultFormatter;
use DartSass\Utils\SourceMapGenerator;
use DartSass\Utils\StateManager;
use DartSass\Utils\UnitValidator;
use DartSass\Utils\ValueFormatter;

readonly class CompilerBuilder
{
    public function __construct(private array $options, private LoaderInterface $loader) {}

    public function build(): CompilerEngineInterface
    {
        $context = $this->createContext();

        $this->initializeCore($context);
        $this->initializeHandlers($context);
        $this->initializeUtils($context);
        $this->initializeEvaluators($context);
        $this->initializeCompilers($context);
        $this->initializeState($context);

        return new CompilerEngine($context);
    }

    private function createContext(): CompilerContext
    {
        return new CompilerContext($this->options);
    }

    private function initializeCore(CompilerContext $context): void
    {
        $context->loader          = $this->loader;
        $context->parserFactory   = new ParserFactory();
        $context->valueFormatter  = new ValueFormatter();
        $context->positionTracker = new PositionTracker();
    }

    private function initializeHandlers(CompilerContext $context): void
    {
        $this->initializeVariableAndMixinHandlers($context);
        $this->initializeModuleHandlers($context);
        $this->initializeFunctionHandlers($context);
    }

    private function initializeVariableAndMixinHandlers(CompilerContext $context): void
    {
        $context->variableHandler = new VariableHandler();
        $context->mixinHandler    = new MixinHandler();
        $context->nestingHandler  = new NestingHandler();
        $context->extendHandler   = new ExtendHandler();
    }

    private function initializeModuleHandlers(CompilerContext $context): void
    {
        $moduleLoader           = new ModuleLoader($context->loader, $context->parserFactory);
        $builtInProvider        = new BuiltInModuleProvider();
        $moduleForwarder        = new ModuleForwarder($moduleLoader);
        $context->moduleHandler = new ModuleHandler($moduleLoader, $moduleForwarder, $builtInProvider);
    }

    private function initializeFunctionHandlers(CompilerContext $context): void
    {
        $customFunctionHandler = new CustomFunctionHandler();
        $resultFormatter       = new ResultFormatter($context->valueFormatter);
        $moduleRegistry        = $this->createModuleRegistry($context, $customFunctionHandler, $resultFormatter);
        $functionRouter        = new FunctionRouter($moduleRegistry, $resultFormatter);
        $userFunctionEvaluator = new UserFunctionEvaluator();

        $context->functionHandler = new FunctionHandler(
            $context->moduleHandler,
            $functionRouter,
            $customFunctionHandler,
            $userFunctionEvaluator,
            fn($expr): mixed => $context->engine?->evaluateExpression($expr)
        );
    }

    private function createModuleRegistry(
        CompilerContext $context,
        CustomFunctionHandler $customFunctionHandler,
        ResultFormatter $resultFormatter
    ): ModuleRegistry {
        $moduleRegistry = new ModuleRegistry();

        $moduleRegistry->register(
            new IfFunctionHandler(
                fn($expr): mixed => $context->engine?->evaluateExpression($expr)
            )
        );
        $moduleRegistry->register(new UrlFunctionHandler());
        $moduleRegistry->register(new FormatFunctionHandler($context->valueFormatter));
        $moduleRegistry->register(new ColorModuleHandler(new ColorModule()));
        $moduleRegistry->register(new StringModuleHandler(new StringModule()));
        $moduleRegistry->register(new ListModuleHandler(new ListModule()));
        $moduleRegistry->register(new MathModuleHandler(
            new MathModule($context->valueFormatter),
            new UnitValidator(),
            $context->valueFormatter
        ));
        $moduleRegistry->register(new CssFunctionHandler($resultFormatter));
        $moduleRegistry->register($customFunctionHandler);

        $customFunctionHandler->setRegistry($moduleRegistry);

        return $moduleRegistry;
    }

    private function initializeUtils(CompilerContext $context): void
    {
        $context->outputOptimizer    = new OutputOptimizer($context->options['style']);
        $context->sourceMapGenerator = new SourceMapGenerator();
    }

    private function initializeEvaluators(CompilerContext $context): void
    {
        $this->initializeSimpleEvaluators($context);
        $this->initializeExpressionEvaluator($context);
    }

    private function initializeSimpleEvaluators(CompilerContext $context): void
    {
        $context->interpolationEvaluator = new InterpolationEvaluator($context->valueFormatter);
        $context->operationEvaluator     = new OperationEvaluator($context->valueFormatter);
    }

    private function initializeExpressionEvaluator(CompilerContext $context): void
    {
        $context->expressionEvaluator = new ExpressionEvaluator(
            $context->variableHandler,
            $context->functionHandler,
            $context->moduleHandler,
            $context->valueFormatter,
            new CalcFunctionEvaluator($context->valueFormatter),
            $context->interpolationEvaluator
        );

        $context->expressionEvaluator->setEvaluateCallback(
            fn($expr): mixed => $context->engine?->evaluateExpression($expr)
        );
    }

    private function initializeCompilers(CompilerContext $context): void
    {
        $this->initializeCoreCompilers($context);
        $this->initializeSpecializedCompilers($context);
    }

    private function initializeCoreCompilers(CompilerContext $context): void
    {
        $context->ruleCompiler        = new RuleCompiler();
        $context->flowControlCompiler = new FlowControlCompiler($context->variableHandler);
        $context->declarationCompiler = new DeclarationCompiler($context->valueFormatter, $context->positionTracker);
    }

    private function initializeSpecializedCompilers(CompilerContext $context): void
    {
        $context->mixinCompiler  = new MixinCompiler($context->mixinHandler, $context->moduleHandler);
        $context->atRuleCompiler = new AtRuleCompiler($context->ruleCompiler, $context->positionTracker);
        $context->moduleCompiler = new ModuleCompiler(
            $context->moduleHandler,
            $context->variableHandler,
            $context->mixinHandler
        );
    }

    private function initializeState(CompilerContext $context): void
    {
        $context->stateManager = new StateManager($context);
    }
}
