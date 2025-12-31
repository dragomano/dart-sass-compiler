<?php

declare(strict_types=1);

namespace DartSass;

use DartSass\Compilers\AtRuleCompiler;
use DartSass\Compilers\DeclarationCompiler;
use DartSass\Compilers\FlowControlCompiler;
use DartSass\Compilers\MixinCompiler;
use DartSass\Compilers\ModuleCompiler;
use DartSass\Compilers\RuleCompiler;
use DartSass\Evaluators\CalcFunctionEvaluator;
use DartSass\Evaluators\ExpressionEvaluator;
use DartSass\Evaluators\InterpolationEvaluator;
use DartSass\Evaluators\OperationEvaluator;
use DartSass\Exceptions\CompilationException;
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
use DartSass\Handlers\ModuleHandler;
use DartSass\Handlers\ModuleRegistry;
use DartSass\Handlers\NestingHandler;
use DartSass\Handlers\UrlFunctionHandler;
use DartSass\Handlers\VariableHandler;
use DartSass\Loaders\FileLoader;
use DartSass\Loaders\LoaderInterface;
use DartSass\Modules\ColorModule;
use DartSass\Modules\ListModule;
use DartSass\Modules\MathModule;
use DartSass\Parsers\Nodes\AtRuleNode;
use DartSass\Parsers\Nodes\ForwardNode;
use DartSass\Parsers\Nodes\FunctionNode;
use DartSass\Parsers\Nodes\IncludeNode;
use DartSass\Parsers\Nodes\MixinNode;
use DartSass\Parsers\Nodes\OperationNode;
use DartSass\Parsers\Nodes\RuleNode;
use DartSass\Parsers\Nodes\SelectorNode;
use DartSass\Parsers\Nodes\UseNode;
use DartSass\Parsers\Nodes\VariableDeclarationNode;
use DartSass\Parsers\ParserFactory;
use DartSass\Parsers\Syntax;
use DartSass\Utils\OutputOptimizer;
use DartSass\Utils\PositionTracker;
use DartSass\Utils\ResultFormatter;
use DartSass\Utils\SourceMapGenerator;
use DartSass\Utils\StateManager;
use DartSass\Utils\UnitValidator;
use DartSass\Utils\ValueFormatter;

use function array_merge;
use function basename;
use function explode;
use function file_put_contents;
use function in_array;
use function is_array;
use function preg_match;
use function rtrim;
use function str_contains;
use function str_repeat;
use function str_starts_with;
use function substr_count;
use function trim;

class Compiler
{
    public VariableHandler $variableHandler;

    private readonly MixinHandler $mixinHandler;

    private readonly FlowControlCompiler $controlFlowCompiler;

    private readonly DeclarationCompiler $declarationCompiler;

    private readonly MixinCompiler $mixinCompiler;

    private readonly AtRuleCompiler $atRuleCompiler;

    private readonly ExpressionEvaluator $expressionEvaluator;

    private readonly ExtendHandler $extendHandler;

    private readonly FunctionHandler $functionHandler;

    private readonly InterpolationEvaluator $interpolationEvaluator;

    private readonly ModuleCompiler $moduleCompiler;

    private readonly ModuleHandler $moduleHandler;

    private readonly NestingHandler $nestingHandler;

    private readonly OperationEvaluator $operationEvaluator;

    private readonly OutputOptimizer $outputOptimizer;

    private readonly ParserFactory $parserFactory;

    private readonly PositionTracker $positionTracker;

    private readonly RuleCompiler $ruleCompiler;

    private readonly SourceMapGenerator $sourceMapGenerator;

    private readonly StateManager $stateManager;

    private readonly ValueFormatter $valueFormatter;

    private array $mappings = [];

    private array $indentCache = [];

    public function __construct(private array $options = [], private ?LoaderInterface $loader = null)
    {
        $this->initializeOptions();
        $this->initializeCore();
        $this->initializeHandlers();
        $this->initializeUtils();
        $this->initializeEvaluators();
        $this->initializeCompilers();
        $this->initializeState();
    }

    public function getMappings(): array
    {
        return $this->mappings;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function compileString(string $string, ?Syntax $syntax = null): string
    {
        $syntax ??= Syntax::SCSS;

        $this->mappings = [];

        $this->positionTracker->setSourceCode($string);

        $parser = $this->parserFactory->create($string, $syntax);

        $ast = $parser->parse();

        $this->variableHandler->enterScope();

        $compiled = $this->compileAst($ast);

        $this->variableHandler->exitScope();

        $compiled = $this->extendHandler->applyExtends($compiled);

        if ($this->options['sourceMap'] && $this->options['sourceMapFilename']) {
            $sourceMapOptions = [];

            if ($this->options['includeSources']) {
                $sourceMapOptions['sourceContent']  = $string;
                $sourceMapOptions['includeSources'] = true;
            }

            $sourceMap = $this->sourceMapGenerator->generate(
                $this->mappings,
                $this->options['sourceFile'],
                $this->options['outputFile'],
                $sourceMapOptions
            );

            file_put_contents($this->options['sourceMapFilename'], $sourceMap);

            $compiled .= "\n/*# sourceMappingURL=" . $this->options['sourceMapFilename'] . ' */';
        }

        return $this->outputOptimizer->optimize($compiled);
    }

    public function compileFile(string $filePath): string
    {
        $originalOptions = $this->getOptions();

        $this->options['sourceFile'] = basename($filePath);

        try {
            $content = $this->loader->load($filePath);

            return $this->compileString($content, Syntax::fromPath($filePath));
        } finally {
            $this->options = $originalOptions;
        }
    }

    public function compileInIsolatedContext(string $string, ?Syntax $syntax = null): string
    {
        $this->pushState();

        try {
            return $this->compileString($string, $syntax);
        } finally {
            $this->popState();
        }
    }

    public function evaluateExpression(mixed $expr): mixed
    {
        if ($expr instanceof OperationNode) {
            $left     = $this->expressionEvaluator->evaluate($expr->properties['left']);
            $right    = $this->expressionEvaluator->evaluate($expr->properties['right']);
            $operator = $expr->properties['operator'];

            return $this->operationEvaluator->evaluate($left, $operator, $right);
        }

        return $this->expressionEvaluator->evaluate($expr);
    }

    public function addFunction(string $name, callable $callback): void
    {
        $this->functionHandler->addCustom($name, $callback);
    }

    public function pushState(): void
    {
        $this->stateManager->push($this->mappings, $this->options);
    }

    public function popState(): void
    {
        $state = $this->stateManager->pop();

        $this->mappings = $state['mappings'];
        $this->options  = $state['options'];
    }

    public function compileDeclarations(array $declarations, int $nestingLevel, string $parentSelector = ''): string
    {
        return $this->declarationCompiler->compile(
            $declarations,
            $nestingLevel,
            $parentSelector,
            $this->options,
            $this->mappings,
            $this->compileAst(...),
            $this->evaluateExpression(...)
        );
    }

    public function formatRule(string $selector, string $content, int $nestingLevel): string
    {
        $indent  = $this->getIndent($nestingLevel);
        $content = rtrim($content, "\n");

        return "$indent$selector {\n$content\n$indent}\n";
    }

    public function compileAst(array $ast, string $parentSelector = '', int $nestingLevel = 0): string
    {
        $css = '';

        foreach ($ast as $node) {
            if (is_array($node)) {
                $css .= $this->compileDeclarations([$node], $nestingLevel, $parentSelector);

                continue;
            }

            if ($node->type === 'at-rule' && ($node->name ?? '') === '@extend') {
                $targetSelector = trim((string) $this->evaluateExpression($node->value ?? ''));
                $this->extendHandler->registerExtend($parentSelector, $targetSelector);

                continue;
            }

            if ($node->type === 'at-rule' && ($node->properties['name'] ?? '') === '@import') {
                $path = $node->properties['value'] ?? '';
                $path = $this->evaluateInterpolationsInString($path);

                if (str_starts_with($path, 'url(') || str_contains($path, ' ')) {
                    $css .= "@import $path;\n";
                } else {
                    $this->compileImportNode($node);
                }

                continue;
            }

            switch ($node->type) {
                case 'variable':
                    $this->compileVariableNode($node);

                    break;

                case 'mixin':
                    $this->compileMixinNode($node);

                    break;

                case 'rule':
                    $css .= $this->compileRuleNode($node, $parentSelector, $nestingLevel);

                    break;

                case 'use':
                    $this->compileUseNode($node, $nestingLevel, $css);

                    break;

                case 'forward':
                    $this->compileForwardNode($node);

                    break;

                case 'function':
                    $this->compileFunctionNode($node);

                    break;

                case 'if':
                case 'each':
                case 'for':
                case 'while':
                    $css .=  $this->controlFlowCompiler->compile(
                        $node,
                        $nestingLevel,
                        $this->evaluateExpression(...),
                        $this->compileAst(...)
                    );

                    break;

                case 'media':
                case 'container':
                case 'keyframes':
                case 'at-rule':
                    $css .= $this->atRuleCompiler->compile(
                        $node,
                        $nestingLevel,
                        $parentSelector,
                        $this->evaluateExpression(...),
                        $this->compileDeclarations(...),
                        $this->compileAst(...),
                        $this->evaluateInterpolationsInString(...)
                    );

                    break;

                case 'include':
                    $css .= $this->compileIncludeNode($node, $parentSelector, $nestingLevel);

                    break;

                default:
                    throw new CompilationException("Unknown AST node type: $node->type");
            }
        }

        return $css;
    }

    private function initializeOptions(): void
    {
        $this->options = array_merge(
            [
                'style'             => 'expanded',
                'sourceMap'         => false,
                'includeSources'    => false,
                'loadPaths'         => [],
                'sourceFile'        => 'input.scss',
                'sourceMapFilename' => 'output.css.map',
                'outputFile'        => 'output.css',
            ],
            $this->options,
        );
    }

    private function initializeCore(): void
    {
        $this->loader ??= new FileLoader($this->options['loadPaths']);

        $this->parserFactory   = new ParserFactory();
        $this->valueFormatter  = new ValueFormatter();
        $this->positionTracker = new PositionTracker();
    }

    private function initializeHandlers(): void
    {
        $this->variableHandler = new VariableHandler();
        $this->mixinHandler    = new MixinHandler();
        $this->nestingHandler  = new NestingHandler();
        $this->extendHandler   = new ExtendHandler();
        $this->moduleHandler   = new ModuleHandler($this->loader, $this->parserFactory);

        $builtinFunctionHandler = new IfFunctionHandler($this->evaluateExpression(...));
        $urlFunctionHandler     = new UrlFunctionHandler();
        $formatFunctionHandler  = new FormatFunctionHandler($this->valueFormatter);
        $colorModuleHandler     = new ColorModuleHandler(new ColorModule());
        $listModuleHandler      = new ListModuleHandler(new ListModule());
        $customFunctionHandler  = new CustomFunctionHandler();

        $mathModuleHandler = new MathModuleHandler(
            new MathModule($this->valueFormatter),
            new UnitValidator(),
            $this->valueFormatter
        );

        $resultFormatter    = new ResultFormatter($this->valueFormatter);
        $cssFunctionHandler = new CssFunctionHandler($resultFormatter);

        $moduleRegistry = new ModuleRegistry();
        $moduleRegistry->register($builtinFunctionHandler);
        $moduleRegistry->register($cssFunctionHandler);
        $moduleRegistry->register($urlFunctionHandler);
        $moduleRegistry->register($formatFunctionHandler);
        $moduleRegistry->register($colorModuleHandler);
        $moduleRegistry->register($listModuleHandler);
        $moduleRegistry->register($mathModuleHandler);
        $moduleRegistry->register($customFunctionHandler);

        $customFunctionHandler->setRegistry($moduleRegistry);

        $functionRouter = new FunctionRouter($moduleRegistry, $resultFormatter);

        $this->functionHandler = new FunctionHandler(
            $this->moduleHandler,
            $functionRouter,
            $customFunctionHandler,
            $this->evaluateExpression(...)
        );
    }

    private function initializeUtils(): void
    {
        $this->outputOptimizer    = new OutputOptimizer($this->options['style']);
        $this->sourceMapGenerator = new SourceMapGenerator();
    }

    private function initializeEvaluators(): void
    {
        $this->interpolationEvaluator = new InterpolationEvaluator($this->valueFormatter);
        $this->operationEvaluator     = new OperationEvaluator($this->valueFormatter);

        $calcEvaluator = new CalcFunctionEvaluator($this->valueFormatter);

        $this->expressionEvaluator = new ExpressionEvaluator(
            $this->variableHandler,
            $this->functionHandler,
            $this->moduleHandler,
            $this->valueFormatter,
            $calcEvaluator,
            $this->interpolationEvaluator
        );

        $this->expressionEvaluator->setEvaluateCallback($this->evaluateExpression(...));
    }

    private function initializeCompilers(): void
    {
        $this->ruleCompiler = new RuleCompiler($this->valueFormatter);

        $this->controlFlowCompiler = new FlowControlCompiler($this->variableHandler);

        $this->declarationCompiler = new DeclarationCompiler(
            $this->valueFormatter,
            $this->positionTracker
        );

        $this->mixinCompiler = new MixinCompiler(
            $this->mixinHandler,
            $this->moduleHandler
        );

        $this->atRuleCompiler = new AtRuleCompiler(
            $this->ruleCompiler,
            $this->positionTracker
        );

        $this->moduleCompiler = new ModuleCompiler(
            $this->moduleHandler,
            $this->variableHandler,
            $this->mixinHandler
        );
    }

    private function initializeState(): void
    {
        $this->stateManager = new StateManager(
            $this->variableHandler,
            $this->mixinHandler,
            $this->functionHandler,
            $this->moduleHandler,
            $this->extendHandler,
            $this->positionTracker
        );
    }

    private function compileVariableNode(VariableDeclarationNode $node): void
    {
        $valueNode = $node->value;

        $value = match ($valueNode->type) {
            'number'             => $this->expressionEvaluator->evaluateNumberExpression($valueNode),
            'string'             => $this->expressionEvaluator->evaluateStringExpression($valueNode),
            'hex_color', 'color' => $valueNode->properties['value'],
            'identifier'         => $this->expressionEvaluator->evaluateIdentifierExpression($valueNode),
            default              => $this->evaluateExpression($valueNode),
        };

        $this->variableHandler->define(
            $node->name,
            $value,
            $node->global ?? false,
            $node->default ?? false,
        );
    }

    private function compileMixinNode(MixinNode $node): void
    {
        $this->mixinHandler->define(
            $node->name,
            $node->args ?? [],
            $node->body ?? [],
        );
    }

    private function compileIncludeNode(IncludeNode $node, string $parentSelector, int $nestingLevel): string
    {
        return $this->mixinCompiler->compile(
            $node,
            $this,
            $parentSelector,
            $nestingLevel,
            $this->evaluateExpression(...)
        );
    }

    private function compileRuleNode(RuleNode $node, string $parentSelector, int $nestingLevel): string
    {
        $selectorString = $node->selector instanceof SelectorNode ? $node->selector->value : null;
        $selectorString = $this->evaluateInterpolationsInString($selectorString);

        $selector = $this->nestingHandler->resolveSelector($selectorString, $parentSelector);

        $this->variableHandler->enterScope();

        $includesCss    = '';
        $controlFlowCss = '';
        $otherNestedCss = '';

        foreach ($node->properties['nested'] ?? [] as $nestedItem) {
            if ($nestedItem->type === 'include') {
                $itemCss = $this->compileIncludeNode($nestedItem, $selector, $nestingLevel + 1);
            } elseif ($nestedItem->type === 'media') {
                $itemCss = $this->ruleCompiler->bubbleMediaQuery(
                    $nestedItem,
                    $selector,
                    $nestingLevel,
                    $this->compileIncludeNode(...),
                    $this->compileDeclarations(...),
                    $this->compileAst(...),
                    $this->getIndent(...)
                );
            } else {
                $itemCss = $this->compileAst([$nestedItem], $selector, $nestingLevel);
            }

            $trimmedCss = trim($itemCss);

            if ($nestedItem->type === 'include' && ! str_starts_with($trimmedCss, '@')) {
                $lines            = explode("\n", rtrim($itemCss));
                $declarationsPart = '';
                $nestedPart       = '';
                $braceLevel       = 0;
                $inNestedRule     = false;

                foreach ($lines as $line) {
                    $trimmedLine = trim($line);
                    $openBraces  = substr_count($line, '{');
                    $closeBraces = substr_count($line, '}');

                    if (
                        ! $inNestedRule
                        && (
                            preg_match('/^[a-zA-Z.#-]/', $trimmedLine)
                            || str_starts_with($trimmedLine, '@')
                        ) && $openBraces > 0
                    ) {
                        $inNestedRule = true;

                        $nestedPart .= $line . "\n";
                        $braceLevel += $openBraces - $closeBraces;
                    } elseif ($inNestedRule) {
                        $nestedPart .= $line . "\n";
                        $braceLevel += $openBraces - $closeBraces;

                        if ($braceLevel <= 0) {
                            $inNestedRule = false;
                            $braceLevel   = 0;
                        }
                    } else {
                        $declarationsPart .= $line . "\n";
                    }
                }

                $includesCss .= $declarationsPart;
                $otherNestedCss .= $nestedPart;
            } elseif (in_array($nestedItem->type, ['if', 'each', 'for', 'while'], true)) {
                $controlFlowCss .= $itemCss;
            } else {
                $otherNestedCss .= $itemCss;
            }
        }

        $generatedPosition = $this->positionTracker->getCurrentPosition();

        if ($this->options['sourceMap']) {
            $this->mappings[] = [
                'generated'   => $generatedPosition,
                'original'    => ['line' => $node->line ?? 0, 'column' => $node->column ?? 0],
                'sourceIndex' => 0,
            ];
        }

        $combinedRuleCss = $includesCss . $this->compileDeclarations(
            $node->declarations ?? [],
            $nestingLevel + 1,
            $selector
        );

        $combinedRuleCss .= $controlFlowCss;

        $css = '';
        if (trim($combinedRuleCss) !== '') {
            $indent = $this->getIndent($nestingLevel);
            $css .= $indent . $selector . " {\n";
            $this->positionTracker->updatePosition($indent . $selector . " {\n");
            $css .= $combinedRuleCss;
            $this->positionTracker->updatePosition($combinedRuleCss);
            $css .= $indent . "}\n";
            $this->positionTracker->updatePosition($indent . "}\n");
        }

        $css .= $otherNestedCss;
        $this->positionTracker->updatePosition($otherNestedCss);
        $this->extendHandler->addDefinedSelector($selector);

        $this->variableHandler->exitScope();

        return $css;
    }

    private function compileUseNode(UseNode $node, int $nestingLevel, string &$css): void
    {
        $path = $node->properties['path'];
        $namespace = $node->properties['namespace'] ?? null;

        if (! $this->moduleHandler->isModuleLoaded($path)) {
            $result = $this->moduleHandler->loadModule($path, $namespace);
            $actualNamespace = $result['namespace'];

            $this->moduleCompiler->registerModuleMixins($actualNamespace);

            $css .= $this->moduleCompiler->compile(
                $result,
                $actualNamespace,
                $namespace,
                $nestingLevel,
                $this->evaluateExpression(...),
                $this->compileAst(...)
            );
        }
    }

    private function compileForwardNode(ForwardNode $node): void
    {
        $path      = $node->path;
        $namespace = $node->namespace ?? null;
        $config    = $node->config ?? [];
        $hide      = $node->hide ?? [];
        $show      = $node->show ?? [];

        $properties = $this->moduleHandler->forwardModule(
            $path,
            fn($expr): mixed => $this->evaluateExpression($expr),
            $namespace,
            $config,
            $hide,
            $show
        );

        foreach ($properties['variables'] as $varName => $varValue) {
            $this->variableHandler->define($varName, $varValue, true);
        }
    }

    private function compileImportNode(AtRuleNode $node): void
    {
        $path = $node->properties['value'] ?? '';

        if (! $this->moduleHandler->isModuleLoaded($path)) {
            $result = $this->moduleHandler->forwardModule($path, $this->evaluateExpression(...));

            foreach ($result['variables'] as $varName => $varValue) {
                $this->variableHandler->define($varName, $varValue, true);
            }
        }
    }

    private function compileFunctionNode(FunctionNode $node): void
    {
        $this->functionHandler->defineUserFunction(
            $node->name,
            $node->args ?? [],
            $node->body ?? [],
            $this->variableHandler,
        );
    }

    private function evaluateInterpolationsInString(string $string): string
    {
        return $this->interpolationEvaluator->evaluate($string, $this->evaluateExpression(...));
    }

    private function getIndent(int $level): string
    {
        return $this->indentCache[$level] ??= str_repeat('  ', $level);
    }
}
