<?php

declare(strict_types=1);

namespace DartSass\Compilers\Strategies;

use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\NodeType;
use DartSass\Parsers\Nodes\SupportsNode;
use InvalidArgumentException;

use function rtrim;
use function str_repeat;

readonly class SupportsRuleStrategy implements RuleCompilationStrategy
{
    public function canHandle(NodeType $ruleType): bool
    {
        return $ruleType === NodeType::SUPPORTS;
    }

    public function compile(
        SupportsNode|AstNode $node,
        string $parentSelector,
        int $currentNestingLevel,
        ...$params
    ): string {
        $evaluateExpression     = $params[0] ?? null;
        $compileDeclarations    = $params[1] ?? null;
        $compileAst             = $params[2] ?? null;
        $evaluateInterpolations = $params[3] ?? null;
        $formatValue            = $params[4] ?? null;

        if (! $evaluateExpression || ! $compileDeclarations || ! $compileAst || ! $formatValue) {
            throw new InvalidArgumentException(
                'Missing required parameters for @supports rule compilation'
            );
        }

        $query = $node->query;
        $query = $evaluateInterpolations($query);
        $query = $evaluateExpression($query);
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
        } else {
            $nestedCss = '';
        }

        $body   = rtrim($declarationsCss . $nestedCss);
        $indent = str_repeat('  ', $currentNestingLevel);

        return "$indent@supports $query {\n$body\n$indent}\n";
    }
}
