<?php

declare(strict_types=1);

namespace DartSass\Compilers\Strategies;

use DartSass\Compilers\CompilerContext;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\KeyframesNode;
use DartSass\Parsers\Nodes\NodeType;
use InvalidArgumentException;

use function current;
use function implode;
use function key;
use function str_repeat;

readonly class KeyframesRuleStrategy implements RuleCompilationStrategy
{
    public function canHandle(NodeType $ruleType): bool
    {
        return $ruleType === NodeType::KEYFRAMES;
    }

    public function compile(
        KeyframesNode|AstNode $node,
        CompilerContext $context,
        string $parentSelector,
        int $currentNestingLevel,
        ...$params
    ): string {
        $expression = $params[3] ?? $params[0] ?? null;

        if (! $expression) {
            throw new InvalidArgumentException('Missing required parameters for keyframes rule compilation');
        }

        $name             = $node->name;
        $keyframes        = $node->keyframes;
        $bodyNestingLevel = $currentNestingLevel + 1;

        $body = '';
        foreach ($keyframes as $keyframe) {
            $selectors = implode(', ', $keyframe['selectors']);
            $indent    = str_repeat('  ', $bodyNestingLevel);

            $body .= "$indent$selectors {\n";

            foreach ($keyframe['declarations'] as $declaration) {
                $property = key($declaration);
                $value    = current($declaration);

                $evaluatedValue = $expression($value);
                $formattedValue = $context->resultFormatter->format($evaluatedValue);
                $declarationCss = "$indent  $property: " . $formattedValue . ";\n";

                $body .= $declarationCss;
            }

            $body .= "$indent}\n";
        }

        $indent = str_repeat('  ', $currentNestingLevel);

        return "$indent@keyframes $name {\n$body$indent}\n";
    }
}
