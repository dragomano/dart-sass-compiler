<?php

declare(strict_types=1);

namespace DartSass\Compilers\Strategies;

use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\ContainerNode;
use DartSass\Parsers\Nodes\MediaNode;
use DartSass\Parsers\Nodes\NodeType;
use InvalidArgumentException;

use function array_map;
use function explode;
use function implode;
use function ltrim;
use function preg_match;
use function rtrim;
use function str_repeat;
use function strlen;
use function substr;

abstract readonly class ConditionalRuleStrategy implements RuleCompilationStrategy
{
    abstract protected function getRuleName(): NodeType;

    abstract protected function getAtSymbol(): string;

    public function canHandle(NodeType $ruleType): bool
    {
        return $ruleType === $this->getRuleName();
    }

    public function compile(
        ContainerNode|MediaNode|AstNode $node,
        string $parentSelector,
        int $currentNestingLevel,
        ...$params
    ): string {
        $evaluateInterpolations = $params[0] ?? null;
        $compileDeclarations    = $params[1] ?? null;
        $compileAst             = $params[2] ?? null;
        $formatValue            = $params[4] ?? null;

        if (! $evaluateInterpolations || ! $compileDeclarations || ! $compileAst || ! $formatValue) {
            throw new InvalidArgumentException(
                'Missing required parameters for ' . $this->getRuleName()->value . ' rule compilation'
            );
        }

        $query = $node->query;
        $query = $evaluateInterpolations($query);
        $query = $formatValue($query);

        $bodyNestingLevel = $currentNestingLevel + 1;
        $bodyDeclarations = $node->body['declarations'] ?? [];
        $bodyNested       = $node->body['nested'] ?? [];

        $declarationsCss = '';
        if (! empty($bodyDeclarations) && ! empty($parentSelector)) {
            $declarationsCss = $compileDeclarations($bodyDeclarations, $parentSelector, $bodyNestingLevel + 1);
            $indent          = str_repeat('  ', $bodyNestingLevel);
            $declarationsCss = $indent . $parentSelector . " {\n" . $declarationsCss . $indent . "}\n";
        } elseif (! empty($bodyDeclarations)) {
            $declarationsCss = $compileDeclarations($bodyDeclarations, $parentSelector, $bodyNestingLevel);
        }

        if (! empty($bodyNested)) {
            $nestedCss = $compileAst($bodyNested, $parentSelector, $bodyNestingLevel);
            $nestedCss = $this->fixNestedSelectorsInMedia($nestedCss);
        } else {
            $nestedCss = '';
        }

        $body   = rtrim($declarationsCss . $nestedCss);
        $indent = str_repeat('  ', $currentNestingLevel);

        $atSymbol = $this->getAtSymbol();

        return "$indent$atSymbol $query {\n$body\n$indent}\n";
    }

    private function fixNestedSelectorsInMedia(string $css): string
    {
        $lines      = explode("\n", $css);
        $fixedLines = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if (preg_match('/^([^,]+,\s*[^,{]+)\s*\{$/', $trimmed, $matches)) {
                $selector      = $matches[1];
                $selectors     = array_map(\trim(...), explode(',', $selector));
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
