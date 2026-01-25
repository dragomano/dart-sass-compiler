<?php

declare(strict_types=1);

namespace DartSass\Compilers\Strategies;

use DartSass\Compilers\CompilerContext;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\AtRuleNode;
use DartSass\Parsers\Nodes\NodeType;
use InvalidArgumentException;

use function rtrim;
use function str_repeat;

readonly class AtRuleStrategy implements RuleCompilationStrategy
{
    public function canHandle(NodeType $ruleType): bool
    {
        return $ruleType === NodeType::AT_RULE;
    }

    public function compile(
        AtRuleNode|AstNode $node,
        CompilerContext $context,
        int $currentNestingLevel,
        string $parentSelector,
        ...$params
    ): string {
        $expression  = $params[0] ?? null;
        $compileDeclarations = $params[1] ?? null;
        $compileAst          = $params[2] ?? null;

        if (! $expression || ! $compileDeclarations || ! $compileAst) {
            throw new InvalidArgumentException('Missing required parameters for at-rule compilation');
        }

        $value = $expression($node->value ?? '');

        $bodyNestingLevel = $currentNestingLevel + 1;
        $bodyDeclarations = $node->body['declarations'] ?? [];
        $bodyNested       = $node->body['nested'] ?? [];

        $declarationsCss = $compileDeclarations($bodyDeclarations, $bodyNestingLevel);
        $nestedCss       = $compileAst($bodyNested, '', $bodyNestingLevel);

        $body   = rtrim($declarationsCss . $nestedCss);
        $indent = str_repeat('  ', $currentNestingLevel);

        $valuePart = $value !== '' ? " $value" : '';

        return "$indent$node->name$valuePart {\n$body\n$indent}\n";
    }
}
