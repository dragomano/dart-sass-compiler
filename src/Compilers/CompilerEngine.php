<?php

declare(strict_types=1);

namespace DartSass\Compilers;

use DartSass\Compilers\Nodes\ColorNodeCompiler;
use DartSass\Compilers\Nodes\DebugNodeCompiler;
use DartSass\Compilers\Nodes\ErrorNodeCompiler;
use DartSass\Compilers\Nodes\ForwardNodeCompiler;
use DartSass\Compilers\Nodes\FunctionNodeCompiler;
use DartSass\Compilers\Nodes\MixinNodeCompiler;
use DartSass\Compilers\Nodes\NodeCompiler;
use DartSass\Compilers\Nodes\RuleNodeCompiler;
use DartSass\Compilers\Nodes\UseNodeCompiler;
use DartSass\Compilers\Nodes\VariableNodeCompiler;
use DartSass\Compilers\Nodes\WarnNodeCompiler;
use DartSass\Evaluators\ExpressionEvaluator;
use DartSass\Evaluators\InterpolationEvaluator;
use DartSass\Evaluators\OperationEvaluator;
use DartSass\Exceptions\CompilationException;
use DartSass\Handlers\ExtendHandler;
use DartSass\Handlers\FunctionHandler;
use DartSass\Handlers\MixinHandler;
use DartSass\Handlers\ModuleHandler;
use DartSass\Handlers\NestingHandler;
use DartSass\Handlers\VariableHandler;
use DartSass\Loaders\LoaderInterface;
use DartSass\Parsers\Nodes\NodeType;
use DartSass\Parsers\Nodes\OperationNode;
use DartSass\Parsers\Nodes\VariableDeclarationNode;
use DartSass\Parsers\ParserFactory;
use DartSass\Parsers\Syntax;
use DartSass\Utils\LoggerInterface;
use DartSass\Utils\OutputOptimizer;
use DartSass\Utils\PositionTracker;
use DartSass\Utils\ResultFormatterInterface;
use DartSass\Utils\SourceMapGenerator;

use function basename;
use function file_put_contents;
use function is_array;
use function rtrim;
use function str_contains;
use function str_repeat;
use function str_starts_with;
use function substr;
use function substr_count;
use function trim;

class CompilerEngine implements CompilerEngineInterface
{
    private const NODE_COMPILER_CLASSES = [
        ColorNodeCompiler::class,
        DebugNodeCompiler::class,
        ErrorNodeCompiler::class,
        ForwardNodeCompiler::class,
        FunctionNodeCompiler::class,
        MixinNodeCompiler::class,
        RuleNodeCompiler::class,
        UseNodeCompiler::class,
        VariableNodeCompiler::class,
        WarnNodeCompiler::class,
    ];

    private array $mappings = [];

    public function __construct(
        private array $options,
        private readonly LoaderInterface $loader,
        private readonly ParserFactory $parserFactory,
        private readonly PositionTracker $positionTracker,
        private readonly ExtendHandler $extendHandler,
        private readonly OutputOptimizer $outputOptimizer,
        private readonly SourceMapGenerator $sourceMapGenerator,
        private readonly ResultFormatterInterface $resultFormatter,
        private readonly InterpolationEvaluator $interpolationEvaluator,
        private readonly OperationEvaluator $operationEvaluator,
        private readonly ExpressionEvaluator $expressionEvaluator,
        private readonly RuleCompiler $ruleCompiler,
        private readonly FlowControlCompiler $flowControlCompiler,
        private readonly DeclarationCompiler $declarationCompiler,
        private readonly MixinCompiler $mixinCompiler,
        private readonly ModuleCompiler $moduleCompiler,
        private readonly ModuleHandler $moduleHandler,
        private readonly MixinHandler $mixinHandler,
        private readonly NestingHandler $nestingHandler,
        private readonly VariableHandler $variableHandler,
        private readonly FunctionHandler $functionHandler,
        private readonly Environment $environment,
        private readonly LoggerInterface $logger
    ) {
        $this->mixinHandler->setEngine($this);
    }

    private array $compilerInstances = [];

    public function compileString(string $string, ?Syntax $syntax = null): string
    {
        $syntax ??= Syntax::SCSS;

        $this->mappings = [];

        $this->positionTracker->setSourceCode($string);

        $parser = $this->parserFactory->create($string, $syntax);

        $ast = $parser->parse();

        $compiled = $this->compileAst($ast);
        $compiled = $this->extendHandler->applyExtends($compiled);
        $compiled = $this->generateSourceMapIfNeeded($compiled, $string);

        return $this->outputOptimizer->optimize($compiled);
    }

    public function compileFile(string $filePath): string
    {
        $originalOptions = $this->options;

        $this->options['sourceFile'] = basename($filePath);

        try {
            $content = $this->loader->load($filePath);

            return $this->compileString($content, Syntax::fromPath($filePath, $content));
        } finally {
            $this->options = $originalOptions;
        }
    }

    public function evaluateExpression(mixed $expr): mixed
    {
        if ($expr instanceof OperationNode) {
            return $this->operationEvaluator->evaluate($expr);
        }

        return $this->expressionEvaluator->evaluate($expr);
    }

    public function addFunction(string $name, callable $callback): void
    {
        $this->functionHandler->addCustom($name, $callback);
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getMappings(): array
    {
        return $this->mappings;
    }

    public function findNodeCompiler(NodeType $nodeType): ?NodeCompiler
    {
        if (isset($this->compilerInstances[$nodeType->value])) {
            return $this->compilerInstances[$nodeType->value];
        }

        foreach (self::NODE_COMPILER_CLASSES as $className) {
            if (in_array($className, [
                DebugNodeCompiler::class,
                ErrorNodeCompiler::class,
                WarnNodeCompiler::class,
            ], true)) {
                $compiler = new $className($this->logger);
            } else {
                $compiler = new $className();
            }

            if ($compiler instanceof NodeCompiler && $compiler->canCompile($nodeType)) {
                $this->compilerInstances[$nodeType->value] = $compiler;

                return $compiler;
            }
        }

        return null;
    }

    public function compileAst(array $ast, string $parentSelector = '', int $nestingLevel = 0): string
    {
        $css = '';

        foreach ($ast as $node) {
            if (is_array($node)) {
                $css .= $this->compileDeclarations([$node], $parentSelector, $nestingLevel);

                continue;
            }

            if ($node->type === NodeType::AT_RULE && ($node->name ?? '') === '@extend') {
                $targetSelector = trim((string) $this->evaluateExpression($node->value ?? ''));
                $this->extendHandler->registerExtend($parentSelector, $targetSelector);

                continue;
            }

            if ($node->type === NodeType::AT_RULE && ($node->name ?? '') === '@import') {
                $path = $node->value ?? '';
                $path = $this->evaluateInterpolationsInString($path);

                if (str_starts_with($path, 'url(') || str_contains($path, ' ')) {
                    $css .= "@import $path;\n";
                } else {
                    $path = trim($path, '"\'');
                    $css .= $this->compileImportAst($path, $parentSelector, $nestingLevel);
                }

                continue;
            }

            if ($node->type === NodeType::COMMENT) {
                if (str_starts_with($node->value, '/*')) {
                    $indent = $this->indent($nestingLevel);
                    $commentValue = $node->value;

                    // Extract content between /* and */
                    $content = substr($commentValue, 2, -2);

                    // Apply interpolation evaluation
                    $evaluatedContent = $this->evaluateInterpolationsInString($content);

                    // Rewrap with comment delimiters
                    $css .= $indent . '/*' . $evaluatedContent . '*/' . "\n";
                }

                continue;
            }

            $compiler = $this->findNodeCompiler($node->type);

            if ($compiler) {
                $css .= $compiler->compile($node, $this, $parentSelector, $nestingLevel);
            } else {
                $css .= $this->compileSpecialNode($node, $parentSelector, $nestingLevel);
            }
        }

        return $css;
    }

    public function compileDeclarations(array $declarations, string $parentSelector = '', int $nestingLevel = 0): string
    {
        return $this->declarationCompiler->compile(
            $declarations,
            $parentSelector,
            $nestingLevel,
            $this->compileAst(...),
            $this->evaluateExpression(...),
            $this->evaluateInterpolationsInString(...),
            $this->options['sourceMap'] ?? false,
            $this->addMapping(...)
        );
    }

    public function formatRule(string $content, string $selector, int $nestingLevel): string
    {
        $indent  = $this->indent($nestingLevel);
        $content = rtrim($content, "\n");

        return "$indent$selector {\n$content\n$indent}\n";
    }

    public function getResultFormatter(): ResultFormatterInterface
    {
        return $this->resultFormatter;
    }

    public function getVariableHandler(): VariableHandler
    {
        return $this->variableHandler;
    }

    public function getMixinHandler(): MixinHandler
    {
        return $this->mixinHandler;
    }

    public function getNestingHandler(): NestingHandler
    {
        return $this->nestingHandler;
    }

    public function getExtendHandler(): ExtendHandler
    {
        return $this->extendHandler;
    }

    public function getModuleHandler(): ModuleHandler
    {
        return $this->moduleHandler;
    }

    public function getFunctionHandler(): FunctionHandler
    {
        return $this->functionHandler;
    }

    public function getInterpolationEvaluator(): InterpolationEvaluator
    {
        return $this->interpolationEvaluator;
    }

    public function getPositionTracker(): PositionTracker
    {
        return $this->positionTracker;
    }

    public function getEnvironment(): Environment
    {
        return $this->environment;
    }

    public function getModuleCompiler(): ModuleCompiler
    {
        return $this->moduleCompiler;
    }

    public function addMapping(array $mapping): void
    {
        $this->mappings[] = $mapping;
    }

    private function generateSourceMapIfNeeded(string $compiled, string $content): string
    {
        if (! $this->options['sourceMap'] || ! $this->options['sourceMapFile']) {
            return $compiled;
        }

        $sourceMapOptions = [];

        if ($this->options['includeSources']) {
            $sourceMapOptions['sourceContent']  = $content;
            $sourceMapOptions['includeSources'] = true;
        }

        $sourceMapOptions['outputLines'] = substr_count($compiled, "\n") + 1;

        $sourceMap = $this->sourceMapGenerator->generate(
            $this->mappings,
            $this->options['sourceFile'],
            $this->options['outputFile'],
            $sourceMapOptions
        );

        file_put_contents($this->options['sourceMapFile'], $sourceMap);

        $compiled .= "\n/*# sourceMappingURL=" . $this->options['sourceMapFile'] . ' */';

        return $compiled;
    }

    private function compileSpecialNode($node, string $parentSelector, int $nestingLevel): string
    {
        return match ($node->type) {
            NodeType::IF,
            NodeType::EACH,
            NodeType::FOR,
            NodeType::WHILE => $this->compileFlowControlNode($node, $parentSelector, $nestingLevel),
            NodeType::MEDIA,
            NodeType::CONTAINER,
            NodeType::KEYFRAMES,
            NodeType::AT_RULE,
            NodeType::AT_ROOT => $this->compileAtRuleNode($node, $parentSelector, $nestingLevel),
            NodeType::INCLUDE => $this->compileIncludeNode($node, $parentSelector, $nestingLevel),
            default => throw new CompilationException("Unknown AST node type: {$node->type->value}"),
        };
    }

    private function compileFlowControlNode($node, string $parentSelector, int $nestingLevel): string
    {
        return $this->flowControlCompiler->compile(
            $node,
            $parentSelector,
            $nestingLevel,
            $this->evaluateExpression(...),
            $this->compileAst(...)
        );
    }

    private function compileAtRuleNode($node, string $parentSelector, int $nestingLevel): string
    {
        $css = $this->ruleCompiler->compileRule(
            $node,
            $parentSelector,
            $nestingLevel,
            $this->evaluateInterpolationsInString(...),
            $this->compileDeclarations(...),
            $this->compileAst(...),
            $this->evaluateExpression(...),
            $this->resultFormatter->format(...),
            $this->mixinHandler->define(...),
        );

        $this->positionTracker->updatePosition($css);

        return $css;
    }

    private function compileIncludeNode($node, string $parentSelector, int $nestingLevel): string
    {
        return $this->mixinCompiler->compile(
            $node,
            $parentSelector,
            $nestingLevel,
            $this->evaluateExpression(...)
        );
    }

    private function compileImportAst(string $path, string $parentSelector, int $nestingLevel): string
    {
        $result     = $this->moduleHandler->loadModule($path);
        $moduleVars = $this->moduleHandler->getVariables($result['namespace']);

        foreach ($moduleVars as $name => $varNode) {
            if ($varNode instanceof VariableDeclarationNode) {
                $value = $this->evaluateExpression($varNode->value);

                $this->variableHandler->define($name, $value);
            }
        }

        return $this->compileAst($result['cssAst'], $parentSelector, $nestingLevel);
    }

    private function evaluateInterpolationsInString(string $string): string
    {
        return $this->interpolationEvaluator->evaluate($string, $this->evaluateExpression(...));
    }

    private function indent(int $level): string
    {
        return str_repeat('  ', $level);
    }
}
