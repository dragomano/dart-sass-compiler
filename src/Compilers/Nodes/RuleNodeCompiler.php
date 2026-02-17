<?php

declare(strict_types=1);

namespace DartSass\Compilers\Nodes;

use Closure;
use DartSass\Handlers\ExtendHandler;
use DartSass\Handlers\NestingHandler;
use DartSass\Parsers\Nodes\AstNode;
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
    public function __construct(
        private readonly NestingHandler $nestingHandler,
        private readonly ExtendHandler $extendHandler,
        private readonly Closure $evaluateInterpolation,
        private readonly Closure $enterScope,
        private readonly Closure $exitScope,
        private readonly Closure $compileAst,
        private readonly Closure $compileDeclarations,
        private readonly Closure $getOptions,
        private readonly Closure $getCurrentPosition,
        private readonly Closure $addMapping,
        private readonly Closure $updatePosition,
        private readonly Closure $formatValue
    ) {}

    protected function getNodeClass(): string
    {
        return RuleNode::class;
    }

    protected function compileNode(
        RuleNode|AstNode $node,
        string $parentSelector = '',
        int $nestingLevel = 0
    ): string {
        $selectorString = $node->selector instanceof SelectorNode ? $node->selector->value : null;
        $selectorString = $this->evaluateInterpolationsInString($selectorString);
        $selector       = $this->nestingHandler->resolveSelector($selectorString, $parentSelector);

        ($this->enterScope)();

        [$includes, $nested, $postDecl] = $this->processNestedItems($node, $selector, $nestingLevel);

        $ruleCss = $this->buildRule($node, $selector, $nestingLevel, $includes, $postDecl);

        $this->extendHandler->addDefinedSelector($selector);

        ($this->exitScope)();

        return $ruleCss . $nested;
    }

    private function processNestedItems(
        RuleNode $node,
        string $selector,
        int $nestingLevel
    ): array {
        $includes = '';
        $nested   = '';
        $postDecl = '';

        foreach ($node->nested ?? [] as $item) {
            $itemCss = $this->compileNestedItem($item, $selector, $nestingLevel);

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
        string $selector,
        int $nestingLevel
    ): string {
        return match ($item->type) {
            NodeType::MEDIA => $this->compileMediaNode($item, $selector, $nestingLevel),
            default         => ($this->compileAst)([$item], $selector, $nestingLevel + 1),
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
        string $selector,
        int $nestingLevel,
        string $includes,
        string $postDecl
    ): string {
        $indent    = str_repeat('  ', $nestingLevel);
        $ruleStart = "$indent$selector {\n";

        if (($this->getOptions)()['sourceMap'] ?? false) {
            $genPos = ($this->getCurrentPosition)();
            $orgPos = [
                'line'   => max(0, ($node->line ?? 1) - 1),
                'column' => max(0, ($node->column ?? 1) - 1),
            ];

            ($this->addMapping)([
                'generated'   => ['line' => $genPos['line'] - 1, 'column' => $genPos['column']],
                'original'    => ['line' => $orgPos['line'], 'column' => $orgPos['column']],
                'sourceIndex' => 0,
            ]);
        }

        ($this->updatePosition)($ruleStart);

        $decl = ($this->compileDeclarations)(
            $node->declarations ?? [],
            $selector,
            $nestingLevel + 1,
        );

        $content = $includes . $decl . $postDecl;

        if (trim($content) === '') {
            return '';
        }

        $ruleEnd = "$indent}\n";
        ($this->updatePosition)($ruleEnd);

        return $ruleStart . rtrim($content) . "\n" . $ruleEnd;
    }

    private function compileMediaNode(
        MediaNode|AstNode $mediaNode,
        string $parentSelector,
        int $nestingLevel
    ): string {
        $query        = $this->evaluateInterpolationsInString($mediaNode->query);
        $query        = ($this->formatValue)($query);
        $declarations = $mediaNode->body['declarations'] ?? [];
        $nested       = $mediaNode->body['nested'] ?? [];

        $includesCss = '';
        foreach ($nested as $item) {
            if ($item->type === NodeType::INCLUDE) {
                $includesCss .= ($this->compileAst)([$item], $parentSelector, $nestingLevel + 2);
            }
        }

        $hasContent = ! empty($declarations) || $includesCss !== '';
        $indent     = str_repeat('  ', $nestingLevel);
        $css        = "$indent@media $query {\n";

        if ($hasContent) {
            $bodyIndent = str_repeat('  ', $nestingLevel + 1);

            $css .= "$bodyIndent$parentSelector {\n";
            $css .= $includesCss;
            $css .= ($this->compileDeclarations)(
                $declarations,
                $parentSelector,
                $nestingLevel + 2,
            );
            $css .= "$bodyIndent}\n";
        }

        foreach ($nested as $item) {
            if ($item->type !== NodeType::INCLUDE) {
                $css .= ($this->compileAst)([$item], $parentSelector, $nestingLevel + 1);
            }
        }

        $css .= "$indent}\n";

        return $css;
    }

    private function evaluateInterpolationsInString(?string $string): string
    {
        if ($string === null) {
            return '';
        }

        return ($this->evaluateInterpolation)($string);
    }
}
