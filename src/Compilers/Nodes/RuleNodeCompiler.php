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
use function preg_match;
use function rtrim;
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

        $context->variableHandler->enterScope();

        $includesCss    = '';
        $flowControlCss = '';
        $otherNestedCss = '';

        foreach ($node->properties['nested'] ?? [] as $nestedItem) {
            if ($nestedItem->type === 'include') {
                $itemCss = $this->compileIncludeNode($nestedItem, $context, $selector, $nestingLevel + 1);
            } elseif ($nestedItem->type === 'media') {
                $itemCss = $this->bubbleMediaQuery($nestedItem, $selector, $nestingLevel, $context);
            } else {
                $itemCss = $context->engine->compileAst([$nestedItem], $selector, $nestingLevel);
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

        $generatedPosition = $context->positionTracker->getCurrentPosition();

        if ($context->options['sourceMap']) {
            $context->mappings[] = [
                'generated'   => $generatedPosition,
                'original'    => ['line' => $node->line ?? 0, 'column' => $node->column ?? 0],
                'sourceIndex' => 0,
            ];
        }

        $combinedRuleCss = $includesCss . $context->declarationCompiler->compile(
            $node->declarations ?? [],
            $nestingLevel + 1,
            $selector,
            $context->options,
            $context->mappings,
            $context->engine->compileAst(...),
            $context->engine->evaluateExpression(...)
        );

        $combinedRuleCss .= $flowControlCss;

        $css = '';
        if (trim($combinedRuleCss) !== '') {
            $indent = $context->engine->getIndent($nestingLevel);

            $css .= $indent . $selector . " {\n";

            $context->positionTracker->updatePosition($indent . $selector . " {\n");

            $css .= $combinedRuleCss;

            $context->positionTracker->updatePosition($combinedRuleCss);

            $css .= $indent . "}\n";

            $context->positionTracker->updatePosition($indent . "}\n");
        }

        $css .= $otherNestedCss;

        $context->positionTracker->updatePosition($otherNestedCss);
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
        $query = $context->valueFormatter->format($query);

        $indent = $context->engine->getIndent($nestingLevel);
        $css    = "$indent@media $query {\n";

        $nested       = $mediaNode->properties['body']['nested'] ?? [];
        $declarations = $mediaNode->properties['body']['declarations'] ?? [];

        $hasDirectContent = ! empty($declarations);
        $includesCss      = '';

        foreach ($nested as $item) {
            if ($item->type === 'include') {
                $includeCss = $this->compileIncludeNode($item, $context, $parentSelector, $nestingLevel + 2);
                $includesCss .= $includeCss;
                $hasDirectContent = true;
            }
        }

        if ($hasDirectContent) {
            $bodyIndent = $context->engine->getIndent($nestingLevel + 1);

            $css .= "$bodyIndent$parentSelector {\n";
            $css .= $includesCss;
            $css .= $context->declarationCompiler->compile(
                $declarations,
                $nestingLevel + 2,
                $parentSelector,
                $context->options,
                $context->mappings,
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
