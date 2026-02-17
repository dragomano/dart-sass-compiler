<?php

declare(strict_types=1);

namespace DartSass\Compilers\Strategies;

use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\AtRootNode;
use DartSass\Parsers\Nodes\NodeType;
use InvalidArgumentException;

use function array_map;
use function explode;
use function implode;
use function in_array;
use function str_repeat;
use function str_starts_with;
use function strlen;
use function trim;

readonly class AtRootStrategy implements RuleCompilationStrategy
{
    public function canHandle(NodeType $ruleType): bool
    {
        return $ruleType === NodeType::AT_ROOT;
    }

    public function compile(
        AtRootNode|AstNode $node,
        string $parentSelector,
        int $currentNestingLevel,
        ...$params
    ): string {
        $compileDeclarations = $params[1] ?? null;
        $compileAst          = $params[2] ?? null;

        if (! $compileDeclarations || ! $compileAst) {
            throw new InvalidArgumentException('Missing required parameters for at-root compilation');
        }

        $bodyNestingLevel = 0;
        $bodyDeclarations = $node->body['declarations'] ?? [];
        $bodyNested       = $node->body['nested'] ?? [];

        $without = $node->without ?? '';
        $with    = $node->with ?? '';

        $withoutContexts  = $without ? array_map(trim(...), explode(',', $without)) : [];
        $withContexts     = $with ? array_map(trim(...), explode(',', $with)) : [];
        $filteredSelector = $this->filterParentSelector($parentSelector, $withoutContexts, $withContexts);

        $declarationsCss = '';
        if (! empty($bodyDeclarations)) {
            if ($filteredSelector !== '') {
                $indent = str_repeat('  ', $currentNestingLevel);

                $declarationsCss = "$indent$filteredSelector {\n";
                $declarationsCss .= $compileDeclarations($bodyDeclarations, $filteredSelector, $bodyNestingLevel);
                $declarationsCss .= "$indent}\n";
            } else {
                $declarationsCss = $compileDeclarations($bodyDeclarations, $filteredSelector, $bodyNestingLevel);
            }
        }

        $nestedCss = '';
        if (! empty($bodyNested)) {
            $nestedCss = $compileAst($bodyNested, $filteredSelector, $bodyNestingLevel);
        }

        $body   = rtrim($declarationsCss . $nestedCss);
        $indent = str_repeat('  ', $currentNestingLevel);

        return $body !== '' ? "$indent$body\n" : '';
    }

    private function filterParentSelector(
        string $parentSelector,
        array $withoutContexts,
        array $withContexts
    ): string {
        if ($parentSelector === '') {
            return '';
        }

        if (empty($withoutContexts) && empty($withContexts)) {
            return '';
        }

        $selectorGroups = array_map(trim(...), explode(',', $parentSelector));
        $filteredGroups = [];

        foreach ($selectorGroups as $group) {
            if ($group === '') {
                continue;
            }

            $parts = $this->splitSelectorParts($group);

            $filteredParts = [];

            foreach ($parts as $part) {
                $contextType = $part['type'];

                $shouldRemove = false;
                if (! empty($withoutContexts)) {
                    if (in_array($contextType, $withoutContexts, true)) {
                        $shouldRemove = true;
                    }
                }

                if (! empty($withContexts)) {
                    if (! in_array($contextType, $withContexts, true) && $contextType !== 'rule') {
                        $shouldRemove = true;
                    }
                }

                if (! $shouldRemove) {
                    $filteredParts[] = $part['value'];
                }
            }

            if (! empty($filteredParts)) {
                $filteredGroups[] = implode(' ', $filteredParts);
            }
        }

        return implode(', ', $filteredGroups);
    }

    private function splitSelectorParts(string $selector): array
    {
        $parts      = [];
        $current    = '';
        $braceLevel = 0;

        $inAtRuleDefinition = false;

        $length = strlen($selector);
        for ($i = 0; $i < $length; $i++) {
            $char = $selector[$i];

            if ($char === '(') {
                $braceLevel++;

                $current .= $char;
            } elseif ($char === ')') {
                $braceLevel--;

                $current .= $char;
            } elseif ($char === ' ' && $braceLevel === 0) {
                $trimmedCurrent = trim($current);

                if (
                    $trimmedCurrent === '@media'
                    || str_starts_with($trimmedCurrent, '@supports')
                    || str_starts_with($trimmedCurrent, '@container')
                ) {
                    $inAtRuleDefinition = true;

                    $current .= $char;

                    continue;
                }

                if ($inAtRuleDefinition) {
                    $nextChar = null;

                    for ($k = $i + 1; $k < $length; $k++) {
                        if ($selector[$k] !== ' ') {
                            $nextChar = $selector[$k];

                            break;
                        }
                    }

                    if ((in_array($nextChar, ['.', '#', '&', ':', '['], true))) {
                        $parts[] = ['type' => 'media', 'value' => $trimmedCurrent];
                        $current = '';

                        $inAtRuleDefinition = false;
                    } else {
                        $current .= $char;
                    }
                } else {
                    if ($trimmedCurrent !== '') {
                        $parts[] = ['type' => 'rule', 'value' => $trimmedCurrent];
                    }

                    $current = '';
                }
            } else {
                $current .= $char;
            }
        }

        if (trim($current) !== '') {
            $value = trim($current);
            $type  = str_starts_with($value, '@media') ? 'media' : 'rule';

            $parts[] = ['type' => $type, 'value' => $value];
        }

        return $parts;
    }
}
