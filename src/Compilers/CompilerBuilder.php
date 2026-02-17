<?php

declare(strict_types=1);

namespace DartSass\Compilers;

use Closure;
use DartSass\Evaluators\ExpressionEvaluator;
use DartSass\Evaluators\InterpolationEvaluator;
use DartSass\Evaluators\OperationEvaluator;
use DartSass\Evaluators\UserFunctionEvaluator;
use DartSass\Exceptions\CompilationException;
use DartSass\Handlers\BuiltInModuleProvider;
use DartSass\Handlers\Builtins\ColorModuleHandler;
use DartSass\Handlers\Builtins\CssColorFunctionHandler;
use DartSass\Handlers\Builtins\CustomFunctionHandler;
use DartSass\Handlers\Builtins\IfFunctionHandler;
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
use DartSass\Parsers\Syntax;
use DartSass\Utils\LoggerInterface;
use DartSass\Utils\OutputOptimizer;
use DartSass\Utils\PositionTracker;
use DartSass\Utils\ResultFormatter;
use DartSass\Utils\SourceMapGenerator;
use DartSass\Utils\StderrLogger;
use DartSass\Utils\ValueFormatter;

readonly class CompilerBuilder
{
    public function __construct(
        private array $options,
        private LoaderInterface $loader,
        private ?LoggerInterface $logger = null
    ) {}

    public function build(): CompilerEngineInterface
    {
        $parserFactory   = new ParserFactory();
        $resultFormatter = new ResultFormatter(new ValueFormatter());
        $positionTracker = new PositionTracker();

        $environment     = new Environment();
        $variableHandler = new VariableHandler($environment);
        $mixinHandler    = new MixinHandler($environment, $variableHandler);
        $nestingHandler  = new NestingHandler();
        $extendHandler   = new ExtendHandler();

        $moduleLoader  = new ModuleLoader($this->loader, $parserFactory);
        $moduleHandler = new ModuleHandler(
            $moduleLoader,
            new ModuleForwarder($moduleLoader),
            new BuiltInModuleProvider()
        );

        $outputOptimizer    = new OutputOptimizer($this->options['style']);
        $sourceMapGenerator = new SourceMapGenerator();

        $runtime = new class () {
            public ?CompilerEngineInterface $engine = null;
        };

        $evaluateExpression = function ($expr) use ($runtime): mixed {
            if (! $runtime->engine instanceof CompilerEngineInterface) {
                throw new CompilationException('Compiler engine is not available');
            }

            return $runtime->engine->evaluateExpression($expr);
        };

        $moduleRegistry = $this->createModuleRegistry($resultFormatter, $evaluateExpression);

        $functionHandler = new FunctionHandler(
            $environment,
            $moduleHandler,
            new FunctionRouter($moduleRegistry, $resultFormatter),
            $this->createCustomHandler($moduleRegistry),
            new UserFunctionEvaluator($environment),
            $evaluateExpression
        );

        $metaModule = new MetaModule(
            $moduleRegistry,
            $mixinHandler,
            $variableHandler,
            $moduleHandler,
            $functionHandler,
            fn() => $runtime->engine?->getOptions() ?? $this->options,
            function (string $content, Syntax $syntax) use ($runtime): string {
                if (! $runtime->engine instanceof CompilerEngineInterface) {
                    throw new CompilationException('Compiler engine is not available');
                }

                return $runtime->engine->compileString($content, $syntax);
            },
            $evaluateExpression
        );
        $moduleRegistry->register(new MetaModuleHandler($metaModule));

        $interpolationEvaluator = new InterpolationEvaluator(
            $parserFactory,
            $resultFormatter
        );

        $expressionEvaluator = new ExpressionEvaluator(
            $variableHandler,
            $moduleHandler,
            $functionHandler,
            $interpolationEvaluator,
            $resultFormatter,
            $evaluateExpression
        );

        $operationEvaluator = new OperationEvaluator(
            $resultFormatter,
            $expressionEvaluator->evaluate(...)
        );

        $ruleCompiler        = new RuleCompiler();
        $flowControlCompiler = new FlowControlCompiler($variableHandler, $environment);
        $declarationCompiler = new DeclarationCompiler($resultFormatter, $positionTracker);
        $mixinCompiler       = new MixinCompiler($mixinHandler, $moduleHandler);
        $moduleCompiler      = new ModuleCompiler(
            $environment,
            $moduleHandler,
            $variableHandler,
            $mixinHandler
        );

        $engine = new CompilerEngine(
            $this->options,
            $this->loader,
            $parserFactory,
            $positionTracker,
            $extendHandler,
            $outputOptimizer,
            $sourceMapGenerator,
            $resultFormatter,
            $interpolationEvaluator,
            $operationEvaluator,
            $expressionEvaluator,
            $ruleCompiler,
            $flowControlCompiler,
            $declarationCompiler,
            $mixinCompiler,
            $moduleCompiler,
            $moduleHandler,
            $mixinHandler,
            $nestingHandler,
            $variableHandler,
            $functionHandler,
            $environment,
            $this->logger ?? new StderrLogger()
        );

        $runtime->engine = $engine;

        return $engine;
    }

    private function createModuleRegistry(
        ResultFormatter $resultFormatter,
        Closure $evaluateExpression
    ): ModuleRegistry {
        $registry = new ModuleRegistry();

        $this->registerCoreHandlers($registry, $resultFormatter, $evaluateExpression);
        $this->registerModuleHandlers($registry, $resultFormatter);

        return $registry;
    }

    private function registerCoreHandlers(
        ModuleRegistry $registry,
        ResultFormatter $resultFormatter,
        Closure $evaluateExpression
    ): void {
        $registry->register(new IfFunctionHandler($evaluateExpression));
        $registry->register(new UrlFunctionHandler($resultFormatter));
    }

    private function registerModuleHandlers(ModuleRegistry $registry, ResultFormatter $resultFormatter): void
    {
        $cssColorHandler = new CssColorFunctionHandler();

        $registry->register($cssColorHandler);
        $registry->register(new ColorModuleHandler(new ColorModule(), $cssColorHandler));
        $registry->register(new StringModuleHandler(new StringModule()));
        $registry->register(new ListModuleHandler(new ListModule()));
        $registry->register(new MapModuleHandler(new MapModule()));
        $registry->register(new MathModuleHandler(new MathModule(), $resultFormatter));
        $registry->register(new SelectorModuleHandler(new SelectorModule()));
    }

    private function createCustomHandler(ModuleRegistry $registry): CustomFunctionHandler
    {
        $customHandler = new CustomFunctionHandler();
        $customHandler->setRegistry($registry);
        $registry->register($customHandler);

        return $customHandler;
    }
}
