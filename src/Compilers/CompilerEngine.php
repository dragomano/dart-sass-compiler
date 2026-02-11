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
use DartSass\Exceptions\CompilationException;
use DartSass\Parsers\Nodes\NodeType;
use DartSass\Parsers\Nodes\OperationNode;
use DartSass\Parsers\Nodes\VariableDeclarationNode;
use DartSass\Parsers\Syntax;
use DartSass\Utils\LoggerInterface;

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

    private array $compilerInstances = [];

    public function __construct(
        private readonly CompilerContext $context,
        private readonly LoggerInterface $logger
    ) {
        $this->context->engine = $this;
    }

    public function compileString(string $string, ?Syntax $syntax = null): string
    {
        $syntax ??= Syntax::SCSS;

        $this->context->mappings = [];

        $this->context->positionTracker->setSourceCode($string);

        $parser = $this->context->parserFactory->create($string, $syntax);

        $ast = $parser->parse();

        $compiled = $this->compileAst($ast);
        $compiled = $this->context->extendHandler->applyExtends($compiled);
        $compiled = $this->generateSourceMapIfNeeded($compiled, $string);

        return $this->context->outputOptimizer->optimize($compiled);
    }

    public function compileFile(string $filePath): string
    {
        $originalOptions = $this->context->options;

        $this->context->options['sourceFile'] = basename($filePath);

        try {
            $content = $this->context->loader->load($filePath);

            return $this->compileString($content, Syntax::fromPath($filePath));
        } finally {
            $this->context->options = $originalOptions;
        }
    }

    public function evaluateExpression(mixed $expr): mixed
    {
        if ($expr instanceof OperationNode) {
            return $this->context->operationEvaluator->evaluate($expr);
        }

        return $this->context->expressionEvaluator->evaluate($expr);
    }

    public function addFunction(string $name, callable $callback): void
    {
        $this->context->functionHandler->addCustom($name, $callback);
    }

    public function getContext(): CompilerContext
    {
        return $this->context;
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
                $this->context->extendHandler->registerExtend($parentSelector, $targetSelector);

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
                    $indent = $this->getIndent($nestingLevel);
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
                $css .= $compiler->compile($node, $this->context, $parentSelector, $nestingLevel);
            } else {
                $css .= $this->compileSpecialNode($node, $parentSelector, $nestingLevel);
            }
        }

        return $css;
    }

    public function compileDeclarations(array $declarations, string $parentSelector = '', int $nestingLevel = 0): string
    {
        return $this->context->declarationCompiler->compile(
            $declarations,
            $parentSelector,
            $nestingLevel,
            $this->context,
            $this->compileAst(...),
            $this->evaluateExpression(...)
        );
    }

    public function formatRule(string $content, string $selector, int $nestingLevel): string
    {
        $indent  = $this->getIndent($nestingLevel);
        $content = rtrim($content, "\n");

        return "$indent$selector {\n$content\n$indent}\n";
    }

    public function getIndent(int $level): string
    {
        return str_repeat('  ', $level);
    }

    private function generateSourceMapIfNeeded(string $compiled, string $content): string
    {
        if (! $this->context->options['sourceMap'] || ! $this->context->options['sourceMapFile']) {
            return $compiled;
        }

        $sourceMapOptions = [];

        if ($this->context->options['includeSources']) {
            $sourceMapOptions['sourceContent']  = $content;
            $sourceMapOptions['includeSources'] = true;
        }

        $sourceMapOptions['outputLines'] = substr_count($compiled, "\n") + 1;

        $sourceMap = $this->context->sourceMapGenerator->generate(
            $this->context->mappings,
            $this->context->options['sourceFile'],
            $this->context->options['outputFile'],
            $sourceMapOptions
        );

        file_put_contents($this->context->options['sourceMapFile'], $sourceMap);

        $compiled .= "\n/*# sourceMappingURL=" . $this->context->options['sourceMapFile'] . ' */';

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
        return $this->context->flowControlCompiler->compile(
            $node,
            $parentSelector,
            $nestingLevel,
            $this->evaluateExpression(...),
            $this->compileAst(...)
        );
    }

    private function compileAtRuleNode($node, string $parentSelector, int $nestingLevel): string
    {
        $css = $this->context->ruleCompiler->compileRule(
            $node,
            $this->context,
            $parentSelector,
            $nestingLevel,
            $this->evaluateInterpolationsInString(...),
            $this->compileDeclarations(...),
            $this->compileAst(...),
            $this->evaluateExpression(...),
        );

        $this->context->positionTracker->updatePosition($css);

        return $css;
    }

    private function compileIncludeNode($node, string $parentSelector, int $nestingLevel): string
    {
        return $this->context->mixinCompiler->compile(
            $node,
            $parentSelector,
            $nestingLevel,
            $this->evaluateExpression(...)
        );
    }

    private function compileImportAst(string $path, string $parentSelector, int $nestingLevel): string
    {
        $result     = $this->context->moduleHandler->loadModule($path);
        $moduleVars = $this->context->moduleHandler->getVariables($result['namespace']);

        foreach ($moduleVars as $name => $varNode) {
            if ($varNode instanceof VariableDeclarationNode) {
                $value = $this->evaluateExpression($varNode->value);

                $this->context->variableHandler->define($name, $value);
            }
        }

        return $this->compileAst($result['cssAst'], $parentSelector, $nestingLevel);
    }

    private function evaluateInterpolationsInString(string $string): string
    {
        return $this->context->interpolationEvaluator->evaluate($string, $this->evaluateExpression(...));
    }
}
