<?php

declare(strict_types=1);

namespace DartSass\Compilers;

use DartSass\Compilers\Nodes\NodeCompiler;
use DartSass\Evaluators\ExpressionEvaluator;
use DartSass\Evaluators\InterpolationEvaluator;
use DartSass\Evaluators\OperationEvaluator;
use DartSass\Exceptions\CompilationException;
use DartSass\Handlers\ExtendHandler;
use DartSass\Handlers\FunctionHandler;
use DartSass\Handlers\MixinHandler;
use DartSass\Handlers\ModuleHandler;
use DartSass\Handlers\VariableHandler;
use DartSass\Loaders\LoaderInterface;
use DartSass\Parsers\Nodes\NodeType;
use DartSass\Parsers\Nodes\OperationNode;
use DartSass\Parsers\Nodes\VariableDeclarationNode;
use DartSass\Parsers\ParserFactory;
use DartSass\Parsers\Syntax;
use DartSass\Utils\OutputOptimizer;
use DartSass\Utils\PositionTracker;
use DartSass\Utils\ResultFormatter;
use DartSass\Utils\SourceMapGenerator;
use DartSass\Utils\ValueFormatter;
use WeakMap;

use function basename;
use function file_put_contents;
use function is_array;
use function str_contains;
use function str_repeat;
use function str_starts_with;
use function substr;
use function substr_count;
use function trim;

class CompilerEngine implements CompilerEngineInterface
{
    private array $mappings = [];

    public function __construct(
        private array $options,
        private readonly LoaderInterface $loader,
        private readonly ParserFactory $parserFactory,
        private readonly Environment $environment,
        private readonly PositionTracker $positionTracker,
        private readonly ExtendHandler $extendHandler,
        private readonly NodeCompilerRegistry $nodeCompilerRegistry,
        private readonly ModuleHandler $moduleHandler,
        private readonly MixinHandler $mixinHandler,
        private readonly VariableHandler $variableHandler,
        private readonly FunctionHandler $functionHandler
    ) {}

    public function compileString(string $string, ?Syntax $syntax = null): string
    {
        $syntax ??= Syntax::SCSS;

        static $depthMap = null;

        $depthMap = $depthMap instanceof WeakMap ? $depthMap : new WeakMap();

        $currentDepth = ($depthMap[$this] ?? 0) + 1;
        $depthMap[$this] = $currentDepth;

        $this->mappings = [];

        try {
            $this->positionTracker->setSourceCode($string);

            $parser = $this->parserFactory->create($string, $syntax);
            $ast = $parser->parse();

            $compiled = $this->compileAst($ast);
            $compiled = $this->extendHandler->applyExtends($compiled);
            $compiled = $this->generateSourceMapIfNeeded($compiled, $string);

            $runtimeTools = $this->runtimeTools();

            return $runtimeTools['outputOptimizer']->optimize($compiled);
        } finally {
            $nextDepth = ($depthMap[$this] ?? 1) - 1;
            $depthMap[$this] = $nextDepth;

            if ($nextDepth === 0) {
                unset($depthMap[$this]);

                $this->positionTracker->setSourceCode('');
                $this->runtimeTools(true);
            }
        }
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

    private function compileAst(array $ast, string $parentSelector = '', int $nestingLevel = 0): string
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
                $path = $this->evaluateInterpolation($node->value ?? '');

                if (str_starts_with($path, 'url(') || str_contains($path, ' ')) {
                    $css .= "@import $path;\n";
                } else {
                    $css .= $this->compileImportAst(trim($path, '"\''), $parentSelector, $nestingLevel);
                }

                continue;
            }

            if ($node->type === NodeType::COMMENT) {
                if (str_starts_with($node->value, '/*')) {
                    $indent  = $this->indent($nestingLevel);
                    $content = substr($node->value, 2, -2);

                    $css .= $indent . '/*' . $this->evaluateInterpolation($content) . '*/' . "\n";
                }

                continue;
            }

            $compiler = $this->findNodeCompiler($node->type);

            $css .= $compiler
                ? $compiler->compile($node, $parentSelector, $nestingLevel)
                : $this->compileSpecialNode($node, $parentSelector, $nestingLevel);
        }

        return $css;
    }

    private function compileDeclarations(
        array $declarations,
        string $parentSelector = '',
        int $nestingLevel = 0
    ): string {
        $runtimeTools = $this->runtimeTools();

        return $runtimeTools['declarationCompiler']->compile(
            $declarations,
            $parentSelector,
            $nestingLevel,
            $this->compileAst(...),
            $this->evaluateExpression(...),
            $this->evaluateInterpolation(...),
            $this->options['sourceMap'] ?? false,
            $this->addMapping(...)
        );
    }

    private function addMapping(array $mapping): void
    {
        $this->mappings[] = $mapping;
    }

    private function evaluateExpression(mixed $expr): mixed
    {
        $runtimeTools = $this->runtimeTools();

        return ($runtimeTools['evaluateExpression'])($expr);
    }

    private function evaluateInterpolation(string $value): string
    {
        $runtimeTools = $this->runtimeTools();

        return ($runtimeTools['evaluateInterpolation'])($value);
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

        $runtimeTools = $this->runtimeTools();

        $sourceMap = $runtimeTools['sourceMapGenerator']->generate(
            $this->mappings,
            $this->options['sourceFile'],
            $this->options['outputFile'],
            $sourceMapOptions
        );

        file_put_contents($this->options['sourceMapFile'], $sourceMap);

        return $compiled . "\n/*# sourceMappingURL=" . $this->options['sourceMapFile'] . ' */';
    }

    private function compileImportAst(string $path, string $parentSelector, int $nestingLevel): string
    {
        $result     = $this->moduleHandler->loadModule($path);
        $moduleVars = $this->moduleHandler->getVariables($result['namespace']);

        foreach ($moduleVars as $name => $varNode) {
            if ($varNode instanceof VariableDeclarationNode) {
                $this->variableHandler->define($name, $this->evaluateExpression($varNode->value));
            }
        }

        return $this->compileAst($result['cssAst'], $parentSelector, $nestingLevel);
    }

    private function findNodeCompiler(NodeType $nodeType): ?NodeCompiler
    {
        return $this->nodeCompilerRegistry->find($nodeType);
    }

    private function compileSpecialNode($node, string $parentSelector, int $nestingLevel): string
    {
        return match ($node->type) {
            NodeType::IF,
            NodeType::EACH,
            NodeType::FOR,
            NodeType::WHILE => $this->compileFlowControlNode($node, $parentSelector, $nestingLevel),
            NodeType::SUPPORTS => $this->compileSupportsNode($node, $parentSelector, $nestingLevel),
            NodeType::MEDIA,
            NodeType::CONTAINER,
            NodeType::KEYFRAMES,
            NodeType::AT_RULE,
            NodeType::AT_ROOT,
            NodeType::SUPPORTS => $this->compileAtRuleNode($node, $parentSelector, $nestingLevel),
            NodeType::INCLUDE => $this->compileIncludeNode($node, $parentSelector, $nestingLevel),
            default => throw new CompilationException("Unknown AST node type: {$node->type->value}"),
        };
    }

    private function compileFlowControlNode($node, string $parentSelector, int $nestingLevel): string
    {
        $runtimeTools = $this->runtimeTools();

        return $runtimeTools['flowControlCompiler']->compile(
            $node,
            $parentSelector,
            $nestingLevel,
            $this->evaluateExpression(...),
            $this->compileAst(...)
        );
    }

    private function compileSupportsNode($node, string $parentSelector, int $nestingLevel): string
    {
        $runtimeTools = $this->runtimeTools();

        $css = $runtimeTools['ruleCompiler']->compileRule(
            $node,
            $parentSelector,
            $nestingLevel,
            $this->evaluateExpression(...),
            $this->compileDeclarations(...),
            $this->compileAst(...),
            $this->evaluateInterpolation(...),
            $runtimeTools['formatValue'],
            $this->mixinHandler->define(...),
        );

        $this->positionTracker->updatePosition($css);

        return $css;
    }

    private function compileAtRuleNode($node, string $parentSelector, int $nestingLevel): string
    {
        $runtimeTools = $this->runtimeTools();

        $css = $runtimeTools['ruleCompiler']->compileRule(
            $node,
            $parentSelector,
            $nestingLevel,
            $this->evaluateInterpolation(...),
            $this->compileDeclarations(...),
            $this->compileAst(...),
            $this->evaluateExpression(...),
            $runtimeTools['formatValue'],
            $this->mixinHandler->define(...),
        );

        $this->positionTracker->updatePosition($css);

        return $css;
    }

    private function compileIncludeNode($node, string $parentSelector, int $nestingLevel): string
    {
        $runtimeTools = $this->runtimeTools();

        return $runtimeTools['mixinCompiler']->compile(
            $node,
            $parentSelector,
            $nestingLevel,
            $this->evaluateExpression(...)
        );
    }

    private function indent(int $level): string
    {
        return str_repeat('  ', $level);
    }

    private function runtimeTools(bool $clear = false): array
    {
        static $cache = null;

        $cache = $cache instanceof WeakMap ? $cache : new WeakMap();

        if ($clear) {
            if (isset($cache[$this])) {
                unset($cache[$this]);
            }

            return [];
        }

        if (isset($cache[$this])) {
            return $cache[$this];
        }

        $resultFormatter = new ResultFormatter(new ValueFormatter());

        $interpolationEvaluator = new InterpolationEvaluator($this->parserFactory, $resultFormatter);

        $evaluateExpression = function (mixed $expr) use (&$operationEvaluator, &$expressionEvaluator): mixed {
            if ($expr instanceof OperationNode) {
                return $operationEvaluator->evaluate($expr);
            }

            return $expressionEvaluator->evaluate($expr);
        };

        $expressionEvaluator = new ExpressionEvaluator(
            $this->variableHandler,
            $this->moduleHandler,
            $this->functionHandler,
            $interpolationEvaluator,
            $resultFormatter,
            $evaluateExpression
        );

        $operationEvaluator = new OperationEvaluator(
            $resultFormatter,
            $expressionEvaluator->evaluate(...)
        );

        $cache[$this] = [
            'outputOptimizer'       => new OutputOptimizer($this->options['style'], $this->options['separateRules'] ?? false),
            'sourceMapGenerator'    => new SourceMapGenerator(),
            'ruleCompiler'          => new RuleCompiler(),
            'flowControlCompiler'   => new FlowControlCompiler($this->variableHandler, $this->environment),
            'declarationCompiler'   => new DeclarationCompiler($resultFormatter, $this->positionTracker),
            'mixinCompiler'         => new MixinCompiler($this->mixinHandler, $this->moduleHandler),
            'evaluateExpression'    => $evaluateExpression,
            'evaluateInterpolation' => fn(string $value): string => $interpolationEvaluator->evaluate($value, $evaluateExpression),
            'formatValue'           => $resultFormatter->format(...),
        ];

        return $cache[$this];
    }
}
