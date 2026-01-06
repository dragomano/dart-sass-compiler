<?php

declare(strict_types=1);

namespace DartSass\Compilers\Strategies;

use DartSass\Compilers\CompilerContext;
use DartSass\Parsers\Nodes\AstNode;
use InvalidArgumentException;

use function rtrim;
use function str_repeat;

readonly class AtRuleStrategy implements RuleCompilationStrategy
{
    public function canHandle(string $ruleType): bool
    {
        return $ruleType === 'at-rule';
    }

    public function compile(
        AstNode $node,
        CompilerContext $context,
        int $currentNestingLevel,
        string $parentSelector,
        ...$params
    ): string {
        $evaluateExpression  = $params[0] ?? null;
        $compileDeclarations = $params[1] ?? null;
        $compileAst          = $params[2] ?? null;

        if (! $evaluateExpression || ! $compileDeclarations || ! $compileAst) {
            throw new InvalidArgumentException('Missing required parameters for at-rule compilation');
        }

        $value = $evaluateExpression($node->properties['value'] ?? '');

        $bodyNestingLevel = $currentNestingLevel + 1;
        $bodyDeclarations = $node->properties['body']['declarations'] ?? [];
        $bodyNested       = $node->properties['body']['nested'] ?? [];

        $declarationsCss = $compileDeclarations($bodyDeclarations, $bodyNestingLevel);
        $nestedCss       = $compileAst($bodyNested, '', $bodyNestingLevel);

        $body   = rtrim($declarationsCss . $nestedCss);
        $indent = str_repeat('  ', $currentNestingLevel);

        $valuePart = $value !== '' ? " $value" : '';

        return "$indent{$node->properties['name']}$valuePart {\n$body\n$indent}\n";
    }
}
