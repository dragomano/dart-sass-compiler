<?php

declare(strict_types=1);

namespace DartSass\Handlers;

use DartSass\Parsers\Nodes\AstNode;

use function array_map;
use function explode;
use function implode;
use function preg_match;
use function preg_replace;
use function rtrim;
use function str_contains;
use function str_replace;
use function trim;

class NestingHandler
{
    public function resolveSelector(mixed $selector, string $parentSelector): string
    {
        $selectorValue = $selector instanceof AstNode ? $selector->properties['value'] : $selector;

        // Get parentSelector from SelectorNode if not provided
        if (empty($parentSelector) && $selector instanceof AstNode) {
            $parentSelector = $selector->properties['parent_selector'] ?? '';
        }

        if (empty($parentSelector)) {
            return $selectorValue;
        }

        $selectors = array_map(trim(...), explode(',', $selectorValue));
        $parentSelectors = array_map(trim(...), explode(',', $parentSelector));
        $resolvedSelectors = [];

        foreach ($selectors as $individualSelector) {
            if (str_contains($individualSelector, '&')) {
                foreach ($parentSelectors as $parentSel) {
                    $normalizedParent = $this->normalizeSelector($parentSel);
                    $resolvedSelectors[] = str_replace('&', $normalizedParent, $individualSelector);
                }
            } elseif (preg_match('/^\s*([>+~])\s*(.*)$/', trim($individualSelector), $matches)) {
                $normalizedParent = $this->normalizeSelector($parentSelectors[0]);
                $combinator = $matches[1];
                $rest = $matches[2];

                if (empty(trim($rest))) {
                    $result = "$normalizedParent $combinator";
                } else {
                    $result = "$normalizedParent $combinator " . trim($rest);
                }

                $resolvedSelectors[] = $result;
            } else {
                foreach ($parentSelectors as $parentSel) {
                    $normalizedParent = $this->normalizeSelector($parentSel);
                    $result = "$normalizedParent $individualSelector";
                    $resolvedSelectors[] = $result;
                }
            }
        }

        return implode(', ', $resolvedSelectors);
    }

    private function normalizeSelector(string $selector): string
    {
        $normalized = preg_replace('/\s*([>+~])\s*/', ' $1 ', trim($selector));

        return rtrim((string) $normalized);
    }
}
