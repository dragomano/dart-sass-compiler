<?php

declare(strict_types=1);

namespace DartSass\Compilers\Strategies;

use DartSass\Compilers\CompilerContext;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\AtRuleNode;
use DartSass\Parsers\Nodes\NodeType;
use InvalidArgumentException;

use function array_merge;
use function explode;
use function preg_match;
use function rtrim;
use function str_repeat;
use function trim;

readonly class AtRuleStrategy implements RuleCompilationStrategy
{
    public function canHandle(NodeType $ruleType): bool
    {
        return $ruleType === NodeType::AT_RULE;
    }

    public function compile(
        AtRuleNode|AstNode $node,
        CompilerContext $context,
        string $parentSelector,
        int $currentNestingLevel,
        ...$params
    ): string {
        if ($node->name === '@mixin') {
            return $this->compileDefinition($node, $context);
        }

        $expression          = $params[0] ?? null;
        $compileDeclarations = $params[1] ?? null;
        $compileAst          = $params[2] ?? null;

        if (! $expression || ! $compileDeclarations || ! $compileAst) {
            throw new InvalidArgumentException('Missing required parameters for at-rule compilation');
        }

        $value = $expression($node->value ?? '');

        $bodyNestingLevel = $currentNestingLevel + 1;
        $bodyDeclarations = $node->body['declarations'] ?? [];
        $bodyNested       = $node->body['nested'] ?? [];

        $declarationsCss = $compileDeclarations($bodyDeclarations, nestingLevel: $bodyNestingLevel);
        $nestedCss       = $compileAst($bodyNested, $parentSelector, nestingLevel: $bodyNestingLevel);

        $body   = rtrim($declarationsCss . $nestedCss);
        $indent = str_repeat('  ', $currentNestingLevel);

        $valuePart = $value !== '' ? " $value" : '';

        return "$indent$node->name$valuePart {\n$body\n$indent}\n";
    }

    private function compileDefinition(AtRuleNode $node, CompilerContext $context): string
    {
        $signature = trim($node->value ?? '');
        $name      = $signature;
        $args      = [];

        if (preg_match('/^([^\s(]+)\s*(?:\((.*)\))?$/s', $signature, $matches)) {
            $name = $matches[1];

            if (isset($matches[2]) && trim($matches[2]) !== '') {
                $rawArgs = explode(',', $matches[2]);
                foreach ($rawArgs as $argStr) {
                    $parts          = explode(':', $argStr, 2);
                    $argName        = trim($parts[0]);
                    $default        = isset($parts[1]) ? trim($parts[1]) : null;
                    $args[$argName] = $default;
                }
            }
        }

        $bodyDeclarations = $node->body['declarations'] ?? [];
        $bodyNested       = $node->body['nested'] ?? [];
        $body             = array_merge($bodyDeclarations, $bodyNested);

        $context->mixinHandler->define($name, $args, $body);

        return '';
    }
}
