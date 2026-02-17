<?php

declare(strict_types=1);

namespace DartSass\Compilers;

use DartSass\Compilers\Strategies\AtRootStrategy;
use DartSass\Compilers\Strategies\AtRuleStrategy;
use DartSass\Compilers\Strategies\ContainerRuleStrategy;
use DartSass\Compilers\Strategies\KeyframesRuleStrategy;
use DartSass\Compilers\Strategies\MediaRuleStrategy;
use DartSass\Compilers\Strategies\RuleCompilationStrategy;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\NodeType;
use InvalidArgumentException;

class RuleCompiler
{
    private const STRATEGIES = [
        AtRootStrategy::class,
        AtRuleStrategy::class,
        ContainerRuleStrategy::class,
        KeyframesRuleStrategy::class,
        MediaRuleStrategy::class,
    ];

    private array $strategyInstances = [];

    public function __construct(private ?array $strategies = null)
    {
        $this->strategies ??= self::STRATEGIES;
    }

    private function findStrategy(NodeType $ruleType): ?RuleCompilationStrategy
    {
        if (isset($this->strategyInstances[$ruleType->value])) {
            return $this->strategyInstances[$ruleType->value];
        }

        foreach ($this->strategies as $className) {
            $strategy = new $className();

            if ($strategy instanceof RuleCompilationStrategy && $strategy->canHandle($ruleType)) {
                $this->strategyInstances[$ruleType->value] = $strategy;

                return $strategy;
            }
        }

        return null;
    }

    public function compileRule(
        AstNode $node,
        string $parentSelector,
        int $currentNestingLevel,
        ...$params
    ): string {
        $ruleType = $node->type;
        $strategy = $this->findStrategy($ruleType);

        if ($strategy) {
            return $strategy->compile($node, $parentSelector, $currentNestingLevel, ...$params);
        }

        throw new InvalidArgumentException("Unknown rule type: $ruleType->value");
    }
}
