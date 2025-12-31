<?php

declare(strict_types=1);

namespace DartSass\Compilers;

use Closure;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Utils\ValueFormatter;

use function array_map;
use function current;
use function explode;
use function implode;
use function key;
use function ltrim;
use function preg_match;
use function rtrim;
use function str_repeat;
use function strlen;
use function substr;
use function trim;

readonly class RuleCompiler
{
    public function __construct(private ValueFormatter $valueFormatter) {}

    public function compileAtRule(
        AstNode $node,
        int $currentNestingLevel,
        Closure $evaluateExpression,
        Closure $compileDeclarations,
        Closure $compileAst
    ): string {
        $value = $evaluateExpression($node->properties['value'] ?? '');

        $bodyNestingLevel = $currentNestingLevel + 1;
        $bodyDeclarations = $node->properties['body']['declarations'] ?? [];

        $bodyNested = $node->properties['body']['nested'] ?? [];

        $declarationsCss = $compileDeclarations($bodyDeclarations, $bodyNestingLevel);
        $nestedCss = $compileAst($bodyNested, '', $bodyNestingLevel);

        $body   = rtrim($declarationsCss . $nestedCss);
        $indent = str_repeat('  ', $currentNestingLevel);

        $valuePart = $value !== '' ? " $value" : '';

        return "$indent{$node->properties['name']}$valuePart {\n$body\n$indent}\n";
    }

    public function compileConditionalRule(
        string $ruleName,
        AstNode $node,
        int $currentNestingLevel,
        string $parentSelector,
        Closure $evaluateInterpolations,
        Closure $compileDeclarations,
        Closure $compileAst
    ): string {
        $query = $node->properties['query'];
        $query = $evaluateInterpolations($query);
        $query = $this->valueFormatter->format($query);

        $bodyNestingLevel = $currentNestingLevel + 1;
        $bodyDeclarations = $node->properties['body']['declarations'] ?? [];

        $bodyNested = $node->properties['body']['nested'] ?? [];

        $declarationsCss = '';
        if (! empty($bodyDeclarations) && ! empty($parentSelector)) {
            $declarationsCss = $compileDeclarations($bodyDeclarations, $bodyNestingLevel + 1, $parentSelector);
            $indent = str_repeat('  ', $bodyNestingLevel);
            $declarationsCss = $indent . $parentSelector . " {\n" . $declarationsCss . $indent . "}\n";
        } elseif (! empty($bodyDeclarations)) {
            $declarationsCss = $compileDeclarations($bodyDeclarations, $bodyNestingLevel, $parentSelector);
        }

        if (! empty($bodyNested)) {
            $nestedCss = $compileAst($bodyNested, $parentSelector, $bodyNestingLevel);
            $nestedCss = $this->fixNestedSelectorsInMedia($nestedCss);
        } else {
            $nestedCss = '';
        }

        $body   = rtrim($declarationsCss . $nestedCss);
        $indent = str_repeat('  ', $currentNestingLevel);

        return "$indent@$ruleName $query {\n$body\n$indent}\n";
    }

    public function bubbleMediaQuery(
        AstNode $mediaNode,
        string $parentSelector,
        int $nestingLevel,
        Closure $compileIncludeNode,
        Closure $compileDeclarations,
        Closure $compileAst,
        Closure $getIndent
    ): string {
        $query  = $mediaNode->properties['query'];
        $indent = $getIndent($nestingLevel);
        $css    = "$indent@media $query {\n";

        $nested = $mediaNode->properties['body']['nested'] ?? [];
        $declarations = $mediaNode->properties['body']['declarations'] ?? [];

        $hasDirectContent = ! empty($declarations);
        $includesCss = '';

        foreach ($nested as $item) {
            if ($item->type === 'include') {
                $includeCss = $compileIncludeNode($item, $parentSelector, $nestingLevel + 2);
                $includesCss .= $includeCss;
                $hasDirectContent = true;
            }
        }

        if ($hasDirectContent) {
            $bodyIndent = $getIndent($nestingLevel + 1);

            $css .= "$bodyIndent$parentSelector {\n";
            $css .= $includesCss;
            $css .= $compileDeclarations($declarations, $nestingLevel + 2, $parentSelector);
            $css .= "$bodyIndent}\n";
        }

        foreach ($nested as $item) {
            if ($item->type !== 'include') {
                $itemCss = $compileAst([$item], $parentSelector, $nestingLevel + 1);
                $css .= $itemCss;
            }
        }

        $css .= "$indent}\n";

        return $css;
    }

    public function compileMediaRule(
        AstNode $node,
        int $currentNestingLevel,
        string $parentSelector,
        Closure $evaluateInterpolations,
        Closure $compileDeclarations,
        Closure $compileAst
    ): string {
        return $this->compileConditionalRule(
            'media',
            $node,
            $currentNestingLevel,
            $parentSelector,
            $evaluateInterpolations,
            $compileDeclarations,
            $compileAst
        );
    }

    public function compileContainerRule(
        AstNode $node,
        int $currentNestingLevel,
        string $parentSelector,
        Closure $evaluateInterpolations,
        Closure $compileDeclarations,
        Closure $compileAst
    ): string {
        return $this->compileConditionalRule(
            'container',
            $node,
            $currentNestingLevel,
            $parentSelector,
            $evaluateInterpolations,
            $compileDeclarations,
            $compileAst
        );
    }

    public function compileKeyframesRule(
        AstNode $node,
        int $currentNestingLevel,
        Closure $evaluateExpression
    ): string {
        $name = $node->properties['name'];
        $keyframes = $node->properties['keyframes'];
        $bodyNestingLevel = $currentNestingLevel + 1;

        $body = '';
        foreach ($keyframes as $keyframe) {
            $selectors = implode(', ', $keyframe['selectors']);
            $indent = str_repeat('  ', $bodyNestingLevel);

            $body .= "$indent$selectors {\n";

            foreach ($keyframe['declarations'] as $declaration) {
                $property = key($declaration);
                $value = current($declaration);

                $evaluatedValue = $evaluateExpression($value);
                $formattedValue = $this->valueFormatter->format($evaluatedValue);
                $declarationCss = "$indent  $property: " . $formattedValue . ";\n";

                $body .= $declarationCss;
            }

            $body .= "$indent}\n";
        }

        $indent = str_repeat('  ', $currentNestingLevel);

        return "$indent@keyframes $name {\n$body$indent}\n";
    }

    private function fixNestedSelectorsInMedia(string $css): string
    {
        $lines = explode("\n", $css);
        $fixedLines = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if (preg_match('/^([^,]+,\s*[^,{]+)\s*\{$/', $trimmed, $matches)) {
                $selector      = $matches[1];
                $selectors     = array_map(trim(...), explode(',', $selector));
                $fixedSelector = implode(",\n  ", $selectors);
                $indent        = substr($line, 0, strlen($line) - strlen(ltrim($line)));

                $fixedLines[] = $indent . $fixedSelector . ' {';
            } else {
                $fixedLines[] = $line;
            }
        }

        return implode("\n", $fixedLines);
    }
}
