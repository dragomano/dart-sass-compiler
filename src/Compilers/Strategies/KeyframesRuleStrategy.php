<?php

declare(strict_types=1);

namespace DartSass\Compilers\Strategies;

use DartSass\Compilers\CompilerContext;
use DartSass\Parsers\Nodes\AstNode;
use InvalidArgumentException;

use function current;
use function implode;
use function key;
use function str_repeat;

readonly class KeyframesRuleStrategy implements RuleCompilationStrategy
{
    public function canHandle(string $ruleType): bool
    {
        return $ruleType === 'keyframes';
    }

    public function compile(
        AstNode $node,
        CompilerContext $context,
        int $currentNestingLevel,
        string $parentSelector,
        ...$params
    ): string {
        $evaluateExpression = $params[3] ?? $params[0] ?? null;

        if (! $evaluateExpression) {
            throw new InvalidArgumentException('Missing required parameters for keyframes rule compilation');
        }

        $name             = $node->properties['name'];
        $keyframes        = $node->properties['keyframes'];
        $bodyNestingLevel = $currentNestingLevel + 1;

        $body = '';
        foreach ($keyframes as $keyframe) {
            $selectors = implode(', ', $keyframe['selectors']);
            $indent    = str_repeat('  ', $bodyNestingLevel);

            $body .= "$indent$selectors {\n";

            foreach ($keyframe['declarations'] as $declaration) {
                $property = key($declaration);
                $value    = current($declaration);

                $evaluatedValue = $evaluateExpression($value);
                $formattedValue = $context->valueFormatter->format($evaluatedValue);
                $declarationCss = "$indent  $property: " . $formattedValue . ";\n";

                $body .= $declarationCss;
            }

            $body .= "$indent}\n";
        }

        $indent = str_repeat('  ', $currentNestingLevel);

        return "$indent@keyframes $name {\n$body$indent}\n";
    }
}
