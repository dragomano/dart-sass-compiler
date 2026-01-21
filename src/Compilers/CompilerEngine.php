<?php

declare(strict_types=1);

namespace DartSass\Compilers;

use DartSass\Compilers\Nodes\ColorNodeCompiler;
use DartSass\Compilers\Nodes\ForwardNodeCompiler;
use DartSass\Compilers\Nodes\FunctionNodeCompiler;
use DartSass\Compilers\Nodes\MixinNodeCompiler;
use DartSass\Compilers\Nodes\NodeCompiler;
use DartSass\Compilers\Nodes\RuleNodeCompiler;
use DartSass\Compilers\Nodes\UseNodeCompiler;
use DartSass\Compilers\Nodes\VariableNodeCompiler;
use DartSass\Exceptions\CompilationException;
use DartSass\Parsers\Nodes\OperationNode;
use DartSass\Parsers\Nodes\VariableDeclarationNode;
use DartSass\Parsers\Syntax;

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
        ForwardNodeCompiler::class,
        FunctionNodeCompiler::class,
        MixinNodeCompiler::class,
        RuleNodeCompiler::class,
        UseNodeCompiler::class,
        VariableNodeCompiler::class,
    ];

    private array $compilerInstances = [];

    public function __construct(private readonly CompilerContext $context)
    {
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

    public function findNodeCompiler(string $nodeType): ?NodeCompiler
    {
        if (isset($this->compilerInstances[$nodeType])) {
            return $this->compilerInstances[$nodeType];
        }

        foreach (self::NODE_COMPILER_CLASSES as $className) {
            $compiler = new $className();

            if ($compiler instanceof NodeCompiler && $compiler->canCompile($nodeType)) {
                $this->compilerInstances[$nodeType] = $compiler;

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
                $css .= $this->compileDeclarations([$node], $nestingLevel, $parentSelector);

                continue;
            }

            if ($node->type === 'at-rule' && ($node->name ?? '') === '@extend') {
                $targetSelector = trim((string) $this->evaluateExpression($node->value ?? ''));
                $this->context->extendHandler->registerExtend($parentSelector, $targetSelector);

                continue;
            }

            if ($node->type === 'at-rule' && ($node->properties['name'] ?? '') === '@import') {
                $path = $node->properties['value'] ?? '';
                $path = $this->evaluateInterpolationsInString($path);

                if (str_starts_with($path, 'url(') || str_contains($path, ' ')) {
                    $css .= "@import $path;\n";
                } else {
                    $path = trim($path, '"\'');
                    $css .= $this->compileImportAst($path, $parentSelector, $nestingLevel);
                }

                continue;
            }

            if ($node->type === 'comment') {
                if (str_starts_with($node->properties['value'], '/*')) {
                    $indent = $this->getIndent($nestingLevel);
                    $commentValue = $node->properties['value'];

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

    public function compileDeclarations(array $declarations, int $nestingLevel, string $parentSelector = ''): string
    {
        return $this->context->declarationCompiler->compile(
            $declarations,
            $nestingLevel,
            $parentSelector,
            $this->context,
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
            'if',
            'each',
            'for',
            'while' => $this->compileFlowControlNode($node, $nestingLevel),
            'media',
            'container',
            'keyframes',
            'at-rule' => $this->compileAtRuleNode($node, $parentSelector, $nestingLevel),
            'include' => $this->compileIncludeNode($node, $parentSelector, $nestingLevel),
            default => throw new CompilationException("Unknown AST node type: $node->type"),
        };
    }

    private function compileFlowControlNode($node, int $nestingLevel): string
    {
        return $this->context->flowControlCompiler->compile(
            $node,
            $nestingLevel,
            $this->evaluateExpression(...),
            $this->compileAst(...)
        );
    }

    private function compileAtRuleNode($node, string $parentSelector, int $nestingLevel): string
    {
        return $this->context->atRuleCompiler->compile(
            $node,
            $nestingLevel,
            $parentSelector,
            $this->evaluateExpression(...),
            $this->compileDeclarations(...),
            $this->compileAst(...),
            $this->evaluateInterpolationsInString(...)
        );
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
        $result = $this->context->moduleHandler->loadModule($path);

        $namespace = $result['namespace'];

        $moduleVars = $this->context->moduleHandler->getVariables($namespace);
        foreach ($moduleVars as $name => $varNode) {
            if ($varNode instanceof VariableDeclarationNode) {
                $value = $this->evaluateExpression($varNode->properties['value']);
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
