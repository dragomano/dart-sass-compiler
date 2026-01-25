<?php

declare(strict_types=1);

namespace DartSass\Compilers;

use DartSass\Evaluators\CalcFunctionEvaluator;
use DartSass\Evaluators\ExpressionEvaluator;
use DartSass\Evaluators\InterpolationEvaluator;
use DartSass\Evaluators\OperationEvaluator;
use DartSass\Evaluators\UserFunctionEvaluator;
use DartSass\Handlers\BuiltInModuleProvider;
use DartSass\Handlers\Builtins\ColorModuleHandler;
use DartSass\Handlers\Builtins\CssColorFunctionHandler;
use DartSass\Handlers\Builtins\CustomFunctionHandler;
use DartSass\Handlers\Builtins\FormatFunctionHandler;
use DartSass\Handlers\Builtins\IfFunctionHandler;
use DartSass\Handlers\Builtins\LinearGradientFunctionHandler;
use DartSass\Handlers\Builtins\ListModuleHandler;
use DartSass\Handlers\Builtins\MapModuleHandler;
use DartSass\Handlers\Builtins\MathModuleHandler;
use DartSass\Handlers\Builtins\MetaModuleHandler;
use DartSass\Handlers\Builtins\SelectorModuleHandler;
use DartSass\Handlers\Builtins\StringModuleHandler;
use DartSass\Handlers\Builtins\UrlFunctionHandler;
use DartSass\Handlers\ExtendHandler;
use DartSass\Handlers\FunctionHandler;
use DartSass\Handlers\FunctionRouter;
use DartSass\Handlers\MixinHandler;
use DartSass\Handlers\ModuleForwarder;
use DartSass\Handlers\ModuleHandler;
use DartSass\Handlers\ModuleLoader;
use DartSass\Handlers\ModuleRegistry;
use DartSass\Handlers\NestingHandler;
use DartSass\Handlers\VariableHandler;
use DartSass\Loaders\LoaderInterface;
use DartSass\Modules\ColorModule;
use DartSass\Modules\ListModule;
use DartSass\Modules\MapModule;
use DartSass\Modules\MathModule;
use DartSass\Modules\MetaModule;
use DartSass\Modules\SelectorModule;
use DartSass\Modules\StringModule;
use DartSass\Parsers\ParserFactory;
use DartSass\Utils\OutputOptimizer;
use DartSass\Utils\PositionTracker;
use DartSass\Utils\ResultFormatter;
use DartSass\Utils\SourceMapGenerator;
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

        $engine = new CompilerEngine($context);
        $context->mixinHandler->setCompilerEngine($engine);

        return $engine;
    }

    private function createContext(): CompilerContext
    {
        return new CompilerContext($this->options);
    }

    private function initializeCore(CompilerContext $context): void
    {
        $context->loader          = $this->loader;
        $context->parserFactory   = new ParserFactory();
        $context->resultFormatter = new ResultFormatter(new ValueFormatter());
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
        $customHandler  = new CustomFunctionHandler();
        $moduleRegistry = $this->createModuleRegistry($context, $customHandler);

        $this->registerMetaModule($moduleRegistry, $context);

        $context->functionHandler = new FunctionHandler(
            $context->moduleHandler,
            new FunctionRouter($moduleRegistry, $context->resultFormatter),
            $customHandler,
            new UserFunctionEvaluator(),
            fn($expr): mixed => $context->engine->evaluateExpression($expr)
        );
    }

    private function createModuleRegistry(
        CompilerContext $context,
        CustomFunctionHandler $customHandler
    ): ModuleRegistry {
        $registry = new ModuleRegistry();

        $this->registerCoreHandlers($registry, $context);
        $this->registerModuleHandlers($registry, $context);
        $this->registerCustomHandlers($registry, $customHandler);

        return $registry;
    }

    private function registerCoreHandlers(ModuleRegistry $registry, CompilerContext $context): void
    {
        $registry->register(new IfFunctionHandler(fn($expr): mixed => $context->engine->evaluateExpression($expr)));
        $registry->register(new UrlFunctionHandler());
        $registry->register(new FormatFunctionHandler($context->resultFormatter));
        $registry->register(new LinearGradientFunctionHandler($context->resultFormatter));
    }

    private function registerModuleHandlers(ModuleRegistry $registry, CompilerContext $context): void
    {
        $cssColorHandler = new CssColorFunctionHandler();

        $registry->register($cssColorHandler);
        $registry->register(new ColorModuleHandler(new ColorModule(), $cssColorHandler));
        $registry->register(new StringModuleHandler(new StringModule()));
        $registry->register(new ListModuleHandler(new ListModule()));
        $registry->register(new MapModuleHandler(new MapModule()));
        $registry->register(new MathModuleHandler(new MathModule(), $context->resultFormatter));
        $registry->register(new SelectorModuleHandler(new SelectorModule()));
    }

    private function registerCustomHandlers(
        ModuleRegistry $registry,
        CustomFunctionHandler $customFunctionHandler
    ): void {
        $registry->register($customFunctionHandler);

        $customFunctionHandler->setRegistry($registry);
    }

    private function registerMetaModule(ModuleRegistry $registry, CompilerContext $context): void
    {
        $metaModule = new MetaModule(
            $registry,
            $context,
            fn($expr): mixed => $context->engine->evaluateExpression($expr)
        );

        $registry->register(new MetaModuleHandler($metaModule));
    }

    private function initializeUtils(CompilerContext $context): void
    {
        $context->outputOptimizer    = new OutputOptimizer($context->options['style']);
        $context->sourceMapGenerator = new SourceMapGenerator();
    }

    private function initializeEvaluators(CompilerContext $context): void
    {
        $context->interpolationEvaluator = new InterpolationEvaluator($context->resultFormatter, $context->parserFactory);
        $context->operationEvaluator     = new OperationEvaluator($context);
        $context->calcEvaluator          = new CalcFunctionEvaluator($context->resultFormatter);
        $context->expressionEvaluator    = new ExpressionEvaluator($context);
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
        $context->declarationCompiler = new DeclarationCompiler($context->resultFormatter, $context->positionTracker);
    }

    private function initializeSpecializedCompilers(CompilerContext $context): void
    {
        $context->mixinCompiler  = new MixinCompiler($context);
        $context->atRuleCompiler = new AtRuleCompiler($context);
        $context->moduleCompiler = new ModuleCompiler($context);
    }
}
