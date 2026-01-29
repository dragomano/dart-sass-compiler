<?php

declare(strict_types=1);

namespace DartSass\Compilers\Nodes;

use DartSass\Compilers\CompilerContext;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\IncludeNode;
use DartSass\Parsers\Nodes\MediaNode;
use DartSass\Parsers\Nodes\NodeType;
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

    protected function getNodeType(): NodeType
    {
        return NodeType::RULE;
    }

    protected function compileNode(
        RuleNode|AstNode $node,
        CompilerContext $context,
        string $parentSelector = '',
        int $nestingLevel = 0
    ): string {
        $selectorString = $node->selector instanceof SelectorNode ? $node->selector->value : null;
        $selectorString = $this->evaluateInterpolationsInString($selectorString, $context);
        $selector       = $context->nestingHandler->resolveSelector($selectorString, $parentSelector);

        $context->environment->enterScope();

        [$includes, $nested, $postDecl] = $this->processNestedItems($node, $context, $selector, $nestingLevel);

        $ruleCss = $this->buildRule($node, $context, $selector, $nestingLevel, $includes, $postDecl);

        $context->extendHandler->addDefinedSelector($selector);
        $context->environment->exitScope();

        return $ruleCss . $nested;
    }

    private function processNestedItems(
        RuleNode $node,
        CompilerContext $context,
        string $selector,
        int $nestingLevel
    ): array {
        $includes = $nested = $postDecl = '';

        foreach ($node->nested ?? [] as $item) {
            $itemCss = $this->compileNestedItem($item, $context, $selector, $nestingLevel);

            if (in_array($item->type, [NodeType::IF, NodeType::EACH, NodeType::FOR, NodeType::WHILE], true)) {
                $extracted = $this->extractDeclarations(trim($itemCss), $selector, $nestingLevel);
                $postDecl .= $extracted;
                $itemCss   = $extracted ? '' : $itemCss;
            }

            if ($item->type === NodeType::INCLUDE && ! str_starts_with(trim($itemCss), '@')) {
                [$declPart, $nestedPart] = $this->separateIncludeContent($itemCss);
                $includes .= $declPart;
                $nested   .= $nestedPart;
            } else {
                $nested .= $itemCss;
            }
        }

        return [$includes, $nested, $postDecl];
    }

    private function compileNestedItem(
        AstNode $item,
        CompilerContext $context,
        string $selector,
        int $nestingLevel
    ): string {
        return match ($item->type) {
            NodeType::EACH,
            NodeType::IF,
            NodeType::FOR,
            NodeType::WHILE   => $this->compileFlowControlNode($item, $context, $selector, $nestingLevel + 1),
            NodeType::INCLUDE => $this->compileIncludeNode($item, $context, $selector, $nestingLevel + 1),
            NodeType::MEDIA   => $this->compileMediaNode($item, $selector, $nestingLevel, $context),
            default           => $context->engine->compileAst([$item], $selector, $nestingLevel + 1),
        };
    }

    private function extractDeclarations(string $css, string $selector, int $nestingLevel): string
    {
        if ($css === '' || str_contains($css, $selector)) {
            return '';
        }

        foreach (explode("\n", $css) as $line) {
            $line = trim($line);
            if ($line !== '' && str_contains($line, ':') && str_ends_with($line, ';')) {
                return str_repeat('  ', $nestingLevel + 1) . rtrim($css) . "\n";
            }
        }

        return '';
    }

    private function separateIncludeContent(string $css): array
    {
        $lines      = explode("\n", rtrim($css));
        $decl       = '';
        $nested     = '';
        $braceLevel = 0;
        $inNested   = false;

        foreach ($lines as $line) {
            $trimmed = trim($line);
            $opens   = substr_count($line, '{');
            $closes  = substr_count($line, '}');

            $isRuleStart = ! $inNested
                && (preg_match('/^[a-zA-Z.#-]/', $trimmed) || str_starts_with($trimmed, '@'))
                && $opens > 0;

            if ($isRuleStart) {
                $inNested = true;
            }

            if ($inNested) {
                $nested     .= $line . "\n";
                $braceLevel += $opens - $closes;

                if ($braceLevel <= 0) {
                    $inNested   = false;
                    $braceLevel = 0;
                }
            } else {
                $decl .= $line . "\n";
            }
        }

        return [$decl, $nested];
    }

    private function buildRule(
        RuleNode $node,
        CompilerContext $context,
        string $selector,
        int $nestingLevel,
        string $includes,
        string $postDecl
    ): string {
        $indent    = str_repeat('  ', $nestingLevel);
        $ruleStart = "$indent$selector {\n";

        if ($context->options['sourceMap'] ?? false) {
            $genPos = $context->positionTracker->getCurrentPosition();
            $orgPos = [
                'line'   => max(0, ($node->line ?? 1) - 1),
                'column' => max(0, ($node->column ?? 1) - 1),
            ];

            $context->mappings[] = [
                'generated'   => ['line' => $genPos['line'] - 1, 'column' => $genPos['column']],
                'original'    => ['line' => $orgPos['line'], 'column' => $orgPos['column']],
                'sourceIndex' => 0,
            ];
        }

        $context->positionTracker->updatePosition($ruleStart);

        $decl = $context->declarationCompiler->compile(
            $node->declarations ?? [],
            $selector,
            $nestingLevel + 1,
            $context,
            $context->engine->compileAst(...),
            $context->engine->evaluateExpression(...)
        );

        $content = $includes . $decl . $postDecl;

        if (trim($content) === '') {
            return '';
        }

        $ruleEnd = "$indent}\n";
        $context->positionTracker->updatePosition($ruleEnd);

        return $ruleStart . rtrim($content) . "\n" . $ruleEnd;
    }

    private function compileFlowControlNode(
        AstNode $node,
        CompilerContext $context,
        string $parentSelector,
        int $nestingLevel
    ): string {
        return $context->flowControlCompiler->compile(
            $node,
            $parentSelector,
            $nestingLevel,
            $context->engine->evaluateExpression(...),
            $context->engine->compileAst(...)
        );
    }

    private function compileIncludeNode(
        IncludeNode|AstNode $node,
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

    private function compileMediaNode(
        MediaNode|AstNode $mediaNode,
        string $parentSelector,
        int $nestingLevel,
        CompilerContext $context
    ): string {
        $query        = $this->evaluateInterpolationsInString($mediaNode->query, $context);
        $query        = $context->resultFormatter->format($query);
        $declarations = $mediaNode->body['declarations'] ?? [];
        $nested       = $mediaNode->body['nested'] ?? [];

        $includesCss = '';
        foreach ($nested as $item) {
            if ($item->type === NodeType::INCLUDE) {
                $includesCss .= $this->compileIncludeNode($item, $context, $parentSelector, $nestingLevel + 2);
            }
        }

        $hasContent = ! empty($declarations) || $includesCss !== '';
        $indent     = str_repeat('  ', $nestingLevel);
        $css        = "$indent@media $query {\n";

        if ($hasContent) {
            $bodyIndent = str_repeat('  ', $nestingLevel + 1);

            $css .= "$bodyIndent$parentSelector {\n";
            $css .= $includesCss;
            $css .= $context->declarationCompiler->compile(
                $declarations,
                $parentSelector,
                $nestingLevel + 2,
                $context,
                $context->engine->compileAst(...),
                $context->engine->evaluateExpression(...)
            );
            $css .= "$bodyIndent}\n";
        }

        foreach ($nested as $item) {
            if ($item->type !== NodeType::INCLUDE) {
                $css .= $context->engine->compileAst([$item], $parentSelector, $nestingLevel + 1);
            }
        }

        $css .= "$indent}\n";

        return $css;
    }

    private function evaluateInterpolationsInString(?string $string, CompilerContext $context): string
    {
        if ($string === null) {
            return '';
        }

        return $context->interpolationEvaluator->evaluate($string, $context->engine->evaluateExpression(...));
    }
}
