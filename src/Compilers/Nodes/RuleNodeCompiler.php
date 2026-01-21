<?php

declare(strict_types=1);

namespace DartSass\Compilers\Nodes;

use DartSass\Compilers\CompilerContext;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\IncludeNode;
use DartSass\Parsers\Nodes\RuleNode;
use DartSass\Parsers\Nodes\SelectorNode;

use function explode;
use function in_array;
use function max;
use function preg_match;
use function rtrim;
use function str_repeat;
use function str_starts_with;
use function substr_count;
use function trim;

class RuleNodeCompiler extends AbstractNodeCompiler
{
    protected function getNodeClass(): string
    {
        return RuleNode::class;
    }

    protected function getNodeType(): string
    {
        return 'rule';
    }

    protected function compileNode(
        AstNode $node,
        CompilerContext $context,
        string $parentSelector = '',
        int $nestingLevel = 0
    ): string {
        $selectorString = $node->properties['selector'] instanceof SelectorNode
            ? $node->properties['selector']->value
            : null;

        $selectorString = $this->evaluateInterpolationsInString($selectorString, $context);

        $selector = $context->nestingHandler->resolveSelector($selectorString, $parentSelector);

        $compileAstClosure = $context->engine->compileAst(...);

        $context->variableHandler->enterScope();

        $includesCss    = '';
        $flowControlCss = '';
        $otherNestedCss = '';

        foreach ($node->properties['nested'] ?? [] as $nestedItem) {
            $itemCss = match ($nestedItem->type) {
                'include' => $this->compileIncludeNode($nestedItem, $context, $selector, $nestingLevel + 1),
                'media'   => $this->bubbleMediaQuery($nestedItem, $selector, $nestingLevel, $context),
                default   => $compileAstClosure([$nestedItem], $selector, $nestingLevel),
            };

            $trimmedCss = trim($itemCss);

            if ($nestedItem->type === 'include' && ! str_starts_with($trimmedCss, '@')) {
                $lines        = explode("\n", rtrim($itemCss));
                $braceLevel   = 0;
                $inNestedRule = false;

                $nestedPart = $declarationsPart = '';

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

        $expression = $context->engine->evaluateExpression(...);
        $indent     = str_repeat('  ', $nestingLevel);
        $ruleStart  = "$indent$selector {\n";
        $ruleEnd    = "$indent}\n";

        if ($context->options['sourceMap'] ?? false) {
            $generatedPosition = $context->positionTracker->getCurrentPosition();

            $originalPosition = [
                'line'   => max(0, ($node->line ?? 1) - 1),
                'column' => max(0, ($node->column ?? 1) - 1),
            ];

            $context->mappings[] = [
                'generated'   => ['line' => $generatedPosition['line'] - 1, 'column' => $generatedPosition['column']],
                'original'    => ['line' => $originalPosition['line'], 'column' => $originalPosition['column']],
                'sourceIndex' => 0,
            ];
        }

        $context->positionTracker->updatePosition($ruleStart);

        $combinedRuleCss = $includesCss . $context->declarationCompiler->compile(
            $node->declarations ?? [],
            $nestingLevel + 1,
            $selector,
            $context,
            $compileAstClosure,
            $expression
        ) . $flowControlCss;

        $css = '';
        if (trim($combinedRuleCss) !== '') {
            $css .= $ruleStart;
            $css .= $combinedRuleCss;
            $css .= $ruleEnd;

            $context->positionTracker->updatePosition($ruleEnd);
        }

        $css .= $otherNestedCss;

        $context->extendHandler->addDefinedSelector($selector);
        $context->variableHandler->exitScope();

        return $css;
    }

    private function compileIncludeNode(
        IncludeNode $node,
        CompilerContext $context,
        string $parentSelector,
        int $nestingLevel
    ): string {
        return $context->mixinCompiler->compile(
            $node,
            $parentSelector,
            $nestingLevel,
            $context->engine->evaluateExpression(...)
        );
    }

    private function evaluateInterpolationsInString(?string $string, CompilerContext $context): ?string
    {
        if ($string === null) {
            return null;
        }

        return $context->interpolationEvaluator->evaluate($string, $context->engine->evaluateExpression(...));
    }

    private function bubbleMediaQuery(
        AstNode $mediaNode,
        string $parentSelector,
        int $nestingLevel,
        CompilerContext $context
    ): string {
        $query = $mediaNode->properties['query'];
        $query = $this->evaluateInterpolationsInString($query, $context);
        $query = $context->resultFormatter->format($query);

        $declarations = $mediaNode->properties['body']['declarations'] ?? [];

        $hasDirectContent = ! empty($declarations);

        $includesCss = '';

        $nested = $mediaNode->properties['body']['nested'] ?? [];

        foreach ($nested as $item) {
            if ($item->type === 'include') {
                $includeCss = $this->compileIncludeNode($item, $context, $parentSelector, $nestingLevel + 2);
                $includesCss .= $includeCss;
                $hasDirectContent = true;
            }
        }

        $indent = str_repeat('  ', $nestingLevel);

        $css = "$indent@media $query {\n";

        if ($hasDirectContent) {
            $bodyIndent = str_repeat('  ', $nestingLevel + 1);

            $css .= "$bodyIndent$parentSelector {\n";
            $css .= $includesCss;
            $css .= $context->declarationCompiler->compile(
                $declarations,
                $nestingLevel + 2,
                $parentSelector,
                $context,
                $context->engine->compileAst(...),
                $context->engine->evaluateExpression(...)
            );
            $css .= "$bodyIndent}\n";
        }

        foreach ($nested as $item) {
            if ($item->type !== 'include') {
                $itemCss = $context->engine->compileAst([$item], $parentSelector, $nestingLevel + 1);
                $css .= $itemCss;
            }
        }

        $css .= "$indent}\n";

        return $css;
    }
}
