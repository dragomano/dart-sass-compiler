<?php

declare(strict_types=1);

namespace DartSass\Compilers;

use Closure;
use DartSass\Compilers\Nodes\ColorNodeCompiler;
use DartSass\Compilers\Nodes\DebugNodeCompiler;
use DartSass\Compilers\Nodes\ErrorNodeCompiler;
use DartSass\Compilers\Nodes\ForwardNodeCompiler;
use DartSass\Compilers\Nodes\FunctionNodeCompiler;
use DartSass\Compilers\Nodes\MixinNodeCompiler;
use DartSass\Compilers\Nodes\RuleNodeCompiler;
use DartSass\Compilers\Nodes\UseNodeCompiler;
use DartSass\Compilers\Nodes\VariableNodeCompiler;
use DartSass\Compilers\Nodes\WarnNodeCompiler;
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
use DartSass\Parsers\Nodes\NodeType;
use DartSass\Parsers\Nodes\OperationNode;
use DartSass\Parsers\ParserFactory;
use DartSass\Parsers\Syntax;
use DartSass\Utils\LoggerInterface;
use DartSass\Utils\PositionTracker;
use DartSass\Utils\ResultFormatter;
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
        $core = $this->createCoreDependencies();

        $callbacks = $this->createRuntimeCallbacks(
            $core['runtime'],
            $core['resultFormatter']
        );

        $moduleRegistry = $this->createModuleRegistry(
            $core['resultFormatter'],
            $callbacks['evaluateExpression']
        );

        $functionHandler = $this->createFunctionHandler(
            $core['environment'],
            $core['moduleHandler'],
            $moduleRegistry,
            $core['resultFormatter'],
            $callbacks['evaluateExpression']
        );

        $this->registerMetaModule(
            $moduleRegistry,
            $core['mixinHandler'],
            $core['variableHandler'],
            $core['moduleHandler'],
            $functionHandler,
            $core['runtime'],
            $callbacks['evaluateExpression']
        );

        $this->initializeEvaluators(
            $callbacks['state'],
            $core['parserFactory'],
            $core['resultFormatter'],
            $core['variableHandler'],
            $core['moduleHandler'],
            $functionHandler,
            $callbacks['evaluateExpression']
        );

        $moduleCallbacks = $this->createModuleCallbacks(
            $core['environment'],
            $core['moduleHandler'],
            $core['variableHandler'],
            $core['mixinHandler'],
            $callbacks['evaluateExpression'],
            $callbacks['compileAst']
        );

        $nodeCompilerRegistry = $this->createNodeCompilerRegistry(
            $core,
            $callbacks,
            $moduleCallbacks,
            $functionHandler
        );

        $engine = $this->createEngine($core, $nodeCompilerRegistry, $functionHandler);

        $this->bindRuntimeToEngine($core['runtime'], $engine);

        $this->wireMixinCallbacks(
            $core['mixinHandler'],
            $callbacks['compileDeclarations'],
            $callbacks['compileAst']
        );

        return $engine;
    }

    private function createCoreDependencies(): array
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

        $runtime = new RuntimeContext();

        return [
            'parserFactory'   => $parserFactory,
            'resultFormatter' => $resultFormatter,
            'positionTracker' => $positionTracker,
            'environment'     => $environment,
            'variableHandler' => $variableHandler,
            'mixinHandler'    => $mixinHandler,
            'nestingHandler'  => $nestingHandler,
            'extendHandler'   => $extendHandler,
            'moduleHandler'   => $moduleHandler,
            'logger'          => $this->logger ?? new StderrLogger(),
            'runtime'         => $runtime,
        ];
    }

    private function createRuntimeCallbacks(RuntimeContext $runtime, ResultFormatter $resultFormatter): array
    {
        $state = new EvaluatorState();

        $evaluateExpression = function ($expr) use ($state): mixed {
            if (
                ! $state->operationEvaluator instanceof OperationEvaluator
                || ! $state->expressionEvaluator instanceof ExpressionEvaluator
            ) {
                throw new CompilationException('Expression evaluators are not available');
            }

            if ($expr instanceof OperationNode) {
                return $state->operationEvaluator->evaluate($expr);
            }

            return $state->expressionEvaluator->evaluate($expr);
        };

        $compileAst = function (
            array $ast,
            string $parentSelector = '',
            int $nestingLevel = 0
        ) use ($runtime): string {
            if (! $runtime->compileAst instanceof Closure) {
                throw new CompilationException('Compiler engine is not available');
            }

            return ($runtime->compileAst)($ast, $parentSelector, $nestingLevel);
        };

        $compileDeclarations = function (
            array $declarations,
            string $parentSelector = '',
            int $nestingLevel = 0
        ) use ($runtime): string {
            if (! $runtime->compileDeclarations instanceof Closure) {
                throw new CompilationException('Compiler engine is not available');
            }

            return ($runtime->compileDeclarations)($declarations, $parentSelector, $nestingLevel);
        };

        $evaluateInterpolation = function (string $value) use ($state, $evaluateExpression): string {
            if (! $state->interpolationEvaluator instanceof InterpolationEvaluator) {
                throw new CompilationException('Interpolation evaluator is not available');
            }

            return $state->interpolationEvaluator->evaluate($value, $evaluateExpression);
        };

        $getOptions = fn(): array => $runtime->engine?->getOptions() ?? $this->options;
        $addMapping = function (array $mapping) use ($runtime): void {
            if (! $runtime->addMapping instanceof Closure) {
                throw new CompilationException('Compiler engine is not available');
            }

            ($runtime->addMapping)($mapping);
        };

        return [
            'state'                 => $state,
            'evaluateExpression'    => $evaluateExpression,
            'compileAst'            => $compileAst,
            'compileDeclarations'   => $compileDeclarations,
            'evaluateInterpolation' => $evaluateInterpolation,
            'formatValue'           => $resultFormatter->format(...),
            'getOptions'            => $getOptions,
            'addMapping'            => $addMapping,
        ];
    }

    private function createFunctionHandler(
        Environment $environment,
        ModuleHandler $moduleHandler,
        ModuleRegistry $moduleRegistry,
        ResultFormatter $resultFormatter,
        Closure $evaluateExpression
    ): FunctionHandler {
        return new FunctionHandler(
            $environment,
            $moduleHandler,
            new FunctionRouter($moduleRegistry, $resultFormatter),
            $this->createCustomHandler($moduleRegistry),
            new UserFunctionEvaluator($environment),
            $evaluateExpression
        );
    }

    private function registerMetaModule(
        ModuleRegistry $moduleRegistry,
        MixinHandler $mixinHandler,
        VariableHandler $variableHandler,
        ModuleHandler $moduleHandler,
        FunctionHandler $functionHandler,
        RuntimeContext $runtime,
        Closure $evaluateExpression
    ): void {
        $metaModule = new MetaModule(
            $moduleRegistry,
            $mixinHandler,
            $variableHandler,
            $moduleHandler,
            $functionHandler,
            fn(): array => $runtime->engine?->getOptions() ?? $this->options,
            function (string $content, Syntax $syntax) use ($runtime): string {
                if (! $runtime->engine instanceof CompilerEngine) {
                    throw new CompilationException('Compiler engine is not available');
                }

                return $runtime->engine->compileString($content, $syntax);
            },
            $evaluateExpression
        );

        $moduleRegistry->register(new MetaModuleHandler($metaModule));
    }

    private function initializeEvaluators(
        EvaluatorState $state,
        ParserFactory $parserFactory,
        ResultFormatter $resultFormatter,
        VariableHandler $variableHandler,
        ModuleHandler $moduleHandler,
        FunctionHandler $functionHandler,
        Closure $evaluateExpression
    ): void {
        $state->interpolationEvaluator = new InterpolationEvaluator($parserFactory, $resultFormatter);

        $state->expressionEvaluator = new ExpressionEvaluator(
            $variableHandler,
            $moduleHandler,
            $functionHandler,
            $state->interpolationEvaluator,
            $resultFormatter,
            $evaluateExpression
        );

        $state->operationEvaluator = new OperationEvaluator(
            $resultFormatter,
            $state->expressionEvaluator->evaluate(...)
        );
    }

    private function createModuleCallbacks(
        Environment $environment,
        ModuleHandler $moduleHandler,
        VariableHandler $variableHandler,
        MixinHandler $mixinHandler,
        Closure $evaluateExpression,
        Closure $compileAst
    ): array {
        $moduleCompiler = new ModuleCompiler(
            $environment,
            $moduleHandler,
            $variableHandler,
            $mixinHandler
        );

        return [
            'registerModuleMixins' => $moduleCompiler->registerModuleMixins(...),
            'compileModule' => fn(
                array $result,
                string $actualNamespace,
                ?string $namespace,
                int $nestingLevel
            ): string => $moduleCompiler->compile(
                $result,
                $actualNamespace,
                $namespace,
                $nestingLevel,
                $evaluateExpression,
                $compileAst
            ),
        ];
    }

    private function createNodeCompilerRegistry(
        array $core,
        array $callbacks,
        array $moduleCallbacks,
        FunctionHandler $functionHandler
    ): NodeCompilerRegistry {
        $registry = new NodeCompilerRegistry();

        $registry->register(NodeType::COLOR, new ColorNodeCompiler());

        $registry->register(
            NodeType::DEBUG,
            new DebugNodeCompiler(
                $core['logger'],
                $callbacks['evaluateExpression'],
                $callbacks['formatValue'],
                $callbacks['getOptions']
            )
        );

        $registry->register(
            NodeType::ERROR,
            new ErrorNodeCompiler(
                $core['logger'],
                $callbacks['evaluateExpression'],
                $callbacks['formatValue'],
                $callbacks['getOptions']
            )
        );

        $registry->register(
            NodeType::FORWARD,
            new ForwardNodeCompiler(
                $core['moduleHandler'],
                $core['variableHandler'],
                $callbacks['evaluateExpression']
            )
        );

        $registry->register(NodeType::FUNCTION, new FunctionNodeCompiler($functionHandler));

        $registry->register(NodeType::MIXIN, new MixinNodeCompiler($core['mixinHandler']));

        $registry->register(
            NodeType::RULE,
            new RuleNodeCompiler(
                $core['nestingHandler'],
                $core['extendHandler'],
                $callbacks['evaluateInterpolation'],
                $core['environment']->enterScope(...),
                $core['environment']->exitScope(...),
                $callbacks['compileAst'],
                $callbacks['compileDeclarations'],
                $callbacks['getOptions'],
                $core['positionTracker']->getCurrentPosition(...),
                $callbacks['addMapping'],
                $core['positionTracker']->updatePosition(...),
                $callbacks['formatValue']
            )
        );

        $registry->register(
            NodeType::USE,
            new UseNodeCompiler(
                $core['moduleHandler'],
                $core['variableHandler'],
                $core['mixinHandler'],
                $callbacks['evaluateExpression'],
                $moduleCallbacks['registerModuleMixins'],
                $moduleCallbacks['compileModule']
            )
        );

        $registry->register(
            NodeType::VARIABLE,
            new VariableNodeCompiler($core['variableHandler'], $callbacks['evaluateExpression'])
        );

        $registry->register(
            NodeType::WARN,
            new WarnNodeCompiler(
                $core['logger'],
                $callbacks['evaluateExpression'],
                $callbacks['formatValue'],
                $callbacks['getOptions']
            )
        );

        return $registry;
    }

    private function createEngine(
        array $core,
        NodeCompilerRegistry $nodeCompilerRegistry,
        FunctionHandler $functionHandler
    ): CompilerEngine {
        return new CompilerEngine(
            $this->options,
            $this->loader,
            $core['parserFactory'],
            $core['environment'],
            $core['positionTracker'],
            $core['extendHandler'],
            $nodeCompilerRegistry,
            $core['moduleHandler'],
            $core['mixinHandler'],
            $core['variableHandler'],
            $functionHandler
        );
    }

    private function bindRuntimeToEngine(RuntimeContext $runtime, CompilerEngine $engine): void
    {
        $runtime->engine = $engine;

        $runtime->compileAst = Closure::bind(
            fn(array $ast, string $parentSelector = '', int $nestingLevel = 0): string
                => $engine->compileAst($ast, $parentSelector, $nestingLevel),
            null,
            CompilerEngine::class
        );

        $runtime->compileDeclarations = Closure::bind(
            fn(array $declarations, string $parentSelector = '', int $nestingLevel = 0): string
                => $engine->compileDeclarations($declarations, $parentSelector, $nestingLevel),
            null,
            CompilerEngine::class
        );

        $runtime->addMapping = Closure::bind(
            function (array $mapping) use ($engine): void {
                $engine->addMapping($mapping);
            },
            null,
            CompilerEngine::class
        );
    }

    private function wireMixinCallbacks(
        MixinHandler $mixinHandler,
        Closure $compileDeclarations,
        Closure $compileAst
    ): void {
        $mixinHandler->setCompilerCallbacks(
            $compileDeclarations,
            $compileAst,
            function (string $content, string $selector, int $nestingLevel): string {
                $indent  = str_repeat('  ', $nestingLevel);
                $content = rtrim($content, "\n");

                return "$indent$selector {\n$content\n$indent}\n";
            }
        );
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
