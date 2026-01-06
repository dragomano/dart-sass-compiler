<?php

declare(strict_types=1);

namespace DartSass\Compilers;

use DartSass\Compilers\Strategies\AtRuleStrategy;
use DartSass\Compilers\Strategies\ContainerRuleStrategy;
use DartSass\Compilers\Strategies\KeyframesRuleStrategy;
use DartSass\Compilers\Strategies\MediaRuleStrategy;
use DartSass\Compilers\Strategies\RuleCompilationStrategy;
use DartSass\Parsers\Nodes\AstNode;
use InvalidArgumentException;

class RuleCompiler
{
    public function __construct(private ?array $strategies = null)
    {
        $this->strategies ??= [
            new AtRuleStrategy(),
            new ContainerRuleStrategy(),
            new KeyframesRuleStrategy(),
            new MediaRuleStrategy(),
        ];
    }

    private function findStrategy(string $ruleType): ?RuleCompilationStrategy
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy instanceof RuleCompilationStrategy && $strategy->canHandle($ruleType)) {
                return $strategy;
            }
        }

        return null;
    }

    public function compileRule(
        AstNode $node,
        CompilerContext $context,
        int $currentNestingLevel,
        string $parentSelector,
        ...$params
    ): string {
        $ruleType = $node->type;
        $strategy = $this->findStrategy($ruleType);

        if ($strategy) {
            return $strategy->compile($node, $context, $currentNestingLevel, $parentSelector, ...$params);
        }

        throw new InvalidArgumentException("Unknown rule type: $ruleType");
    }
}
