<?php

declare(strict_types=1);

namespace DartSass\Compilers;

use DartSass\Exceptions\CompilationException;
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
use DartSass\Parsers\Syntax;

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

readonly class CompilerEngine implements CompilerEngineInterface
{
    public function __construct(private CompilerContext $context)
    {
        $this->context->engine = $this;
    }

    public function getContext(): CompilerContext
    {
        return $this->context;
    }

    public function compileString(string $string, ?Syntax $syntax = null): string
    {
        $syntax ??= Syntax::SCSS;

        $this->context->mappings = [];

        $this->context->positionTracker->setSourceCode($string);

        $parser = $this->context->parserFactory->create($string, $syntax);

        $ast = $parser->parse();

        $this->context->variableHandler->enterScope();

        $compiled = $this->compileAst($ast);

        $this->context->variableHandler->exitScope();

        $compiled = $this->context->extendHandler->applyExtends($compiled);

        if ($this->context->options['sourceMap'] && $this->context->options['sourceMapFilename']) {
            $sourceMapOptions = [];

            if ($this->context->options['includeSources']) {
                $sourceMapOptions['sourceContent']  = $string;
                $sourceMapOptions['includeSources'] = true;
            }

            $sourceMap = $this->context->sourceMapGenerator->generate(
                $this->context->mappings,
                $this->context->options['sourceFile'],
                $this->context->options['outputFile'],
                $sourceMapOptions
            );

            file_put_contents($this->context->options['sourceMapFilename'], $sourceMap);

            $compiled .= "\n/*# sourceMappingURL=" . $this->context->options['sourceMapFilename'] . ' */';
        }

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
            $left     = $this->context->expressionEvaluator->evaluate($expr->properties['left']);
            $right    = $this->context->expressionEvaluator->evaluate($expr->properties['right']);
            $operator = $expr->properties['operator'];

            return $this->context->operationEvaluator->evaluate($left, $operator, $right);
        }

        return $this->context->expressionEvaluator->evaluate($expr);
    }

    public function addFunction(string $name, callable $callback): void
    {
        $this->context->functionHandler->addCustom($name, $callback);
    }

    public function pushState(): void
    {
        $this->context->stateManager->push();
    }

    public function popState(): void
    {
        $this->context->stateManager->pop();
    }

    public function compileDeclarations(array $declarations, int $nestingLevel, string $parentSelector = ''): string
    {
        return $this->context->declarationCompiler->compile(
            $declarations,
            $nestingLevel,
            $parentSelector,
            $this->context->options,
            $this->context->mappings,
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
                $this->context->extendHandler->registerExtend($parentSelector, $targetSelector);

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
                    $css .= $this->context->flowControlCompiler->compile(
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
                    $css .= $this->context->atRuleCompiler->compile(
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

    private function compileVariableNode(VariableDeclarationNode $node): void
    {
        $valueNode = $node->value;

        $value = match ($valueNode->type) {
            'number'             => $this->context->expressionEvaluator->evaluateNumberExpression($valueNode),
            'string'             => $this->context->expressionEvaluator->evaluateStringExpression($valueNode),
            'hex_color', 'color' => $valueNode->properties['value'],
            'identifier'         => $this->context->expressionEvaluator->evaluateIdentifierExpression($valueNode),
            default              => $this->evaluateExpression($valueNode),
        };

        $this->context->variableHandler->define(
            $node->name,
            $value,
            $node->global ?? false,
            $node->default ?? false,
        );
    }

    private function compileMixinNode(MixinNode $node): void
    {
        $this->context->mixinHandler->define(
            $node->name,
            $node->args ?? [],
            $node->body ?? [],
        );
    }

    private function compileIncludeNode(IncludeNode $node, string $parentSelector, int $nestingLevel): string
    {
        return $this->context->mixinCompiler->compile(
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

        $selector = $this->context->nestingHandler->resolveSelector($selectorString, $parentSelector);

        $this->context->variableHandler->enterScope();

        $includesCss    = '';
        $flowControlCss = '';
        $otherNestedCss = '';

        foreach ($node->properties['nested'] ?? [] as $nestedItem) {
            if ($nestedItem->type === 'include') {
                $itemCss = $this->compileIncludeNode($nestedItem, $selector, $nestingLevel + 1);
            } elseif ($nestedItem->type === 'media') {
                $itemCss = $this->context->ruleCompiler->bubbleMediaQuery(
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
                            $braceLevel = 0;
                        }
                    } else {
                        $declarationsPart .= $line . "\n";
                    }
                }

                $includesCss .= $declarationsPart;
                $otherNestedCss .= $nestedPart;
            } elseif (in_array($nestedItem->type, ['if', 'each', 'for', 'while'], true)) {
                $flowControlCss .= $itemCss;
            } else {
                $otherNestedCss .= $itemCss;
            }
        }

        $generatedPosition = $this->context->positionTracker->getCurrentPosition();

        if ($this->context->options['sourceMap']) {
            $this->context->mappings[] = [
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

        $combinedRuleCss .= $flowControlCss;

        $css = '';
        if (trim($combinedRuleCss) !== '') {
            $indent = $this->getIndent($nestingLevel);

            $css .= $indent . $selector . " {\n";

            $this->context->positionTracker->updatePosition($indent . $selector . " {\n");

            $css .= $combinedRuleCss;

            $this->context->positionTracker->updatePosition($combinedRuleCss);

            $css .= $indent . "}\n";

            $this->context->positionTracker->updatePosition($indent . "}\n");
        }

        $css .= $otherNestedCss;

        $this->context->positionTracker->updatePosition($otherNestedCss);
        $this->context->extendHandler->addDefinedSelector($selector);

        $this->context->variableHandler->exitScope();

        return $css;
    }

    private function compileUseNode(UseNode $node, int $nestingLevel, string &$css): void
    {
        $path = $node->properties['path'];
        $namespace = $node->properties['namespace'] ?? null;

        if (! $this->context->moduleHandler->isModuleLoaded($path)) {
            $result = $this->context->moduleHandler->loadModule($path, $namespace);
            $actualNamespace = $result['namespace'];

            $this->context->moduleCompiler->registerModuleMixins($actualNamespace);

            $css .= $this->context->moduleCompiler->compile(
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

        $properties = $this->context->moduleHandler->forwardModule(
            $path,
            fn($expr): mixed => $this->evaluateExpression($expr),
            $namespace,
            $config,
            $hide,
            $show
        );

        foreach ($properties['variables'] as $varName => $varValue) {
            $this->context->variableHandler->define($varName, $varValue, true);
        }
    }

    private function compileImportNode(AtRuleNode $node): void
    {
        $path = $node->properties['value'] ?? '';

        if (! $this->context->moduleHandler->isModuleLoaded($path)) {
            $result = $this->context->moduleHandler->forwardModule($path, $this->evaluateExpression(...));

            foreach ($result['variables'] as $varName => $varValue) {
                $this->context->variableHandler->define($varName, $varValue, true);
            }
        }
    }

    private function compileFunctionNode(FunctionNode $node): void
    {
        $this->context->functionHandler->defineUserFunction(
            $node->name,
            $node->args ?? [],
            $node->body ?? [],
            $this->context->variableHandler,
        );
    }

    private function evaluateInterpolationsInString(string $string): string
    {
        return $this->context->interpolationEvaluator->evaluate($string, $this->evaluateExpression(...));
    }

    private function getIndent(int $level): string
    {
        return $this->context->indentCache[$level] ??= str_repeat('  ', $level);
    }
}
