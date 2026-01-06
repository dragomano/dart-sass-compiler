<?php

declare(strict_types=1);

namespace DartSass\Handlers;

use function array_reverse;
use function array_unique;
use function end;
use function explode;
use function implode;
use function in_array;
use function preg_match;
use function preg_quote;
use function preg_replace;
use function rtrim;
use function str_ends_with;
use function substr;
use function trim;

class ExtendHandler
{
    private array $extends = [];

    private array $definedSelectors = [];

    public function registerExtend(string $selector, string $targetSelector): void
    {
        if (! isset($this->extends[$selector])) {
            $this->extends[$selector] = [];
        }

        $this->extends[$selector][] = $targetSelector;
    }

    public function applyExtends(string $css): string
    {
        if (empty($this->extends)) {
            return $css;
        }

        $lines  = explode("\n", $css);
        $result = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if (str_ends_with($trimmed, '{')) {
                $selector = trim(rtrim(substr($trimmed, 0, -1)));
                $expandedSelectors = $this->expandSelector($selector);

                if (count($expandedSelectors) > 1) {
                    $result[] = implode(', ', array_unique($expandedSelectors)) . ' {';
                } else {
                    $result[] = $line;
                }
            } else {
                $result[] = $line;
            }
        }

        return implode("\n", $result);
    }

    public function getExtends(): array
    {
        return $this->extends;
    }

    public function setExtends(array $extends): void
    {
        $this->extends = $extends;
    }

    public function addDefinedSelector(string $selector): void
    {
        $selectors = array_map(trim(...), explode(',', $selector));

        foreach ($selectors as $sel) {
            // Split by spaces to get individual selector parts
            $parts = array_map(trim(...), explode(' ', $sel));
            foreach ($parts as $part) {
                if (! in_array($part, $this->definedSelectors, true)) {
                    $this->definedSelectors[] = $part;
                }
            }
        }
    }

    private function expandSelector(string $selector): array
    {
        $selectors = [$selector];

        foreach ($this->extends as $extendingSelector => $targetSelectors) {
            foreach ($targetSelectors as $targetSelector) {
                if ($this->selectorMatches($selector, $targetSelector)) {
                    $newSelector = $this->replaceInSelector($selector, $targetSelector, $extendingSelector);
                    $selectors[] = $newSelector;
                }
            }
        }

        return $selectors;
    }

    private function selectorMatches(string $selector, string $target): bool
    {
        // Clean up selectors for comparison
        $cleanSelector = (string) preg_replace('/\s+/', ' ', trim($selector));
        $cleanTarget   = (string) preg_replace('/\s+/', ' ', trim($target));

        // Exact match
        if ($cleanSelector === $cleanTarget) {
            return true;
        }

        // Extract base selector (without pseudo-classes) for comparison
        $baseSelector = (string) preg_replace('/:[^\s,{]+/', '', $cleanSelector);
        $baseTarget   = (string) preg_replace('/:[^\s,{]+/', '', $cleanTarget);

        // Check if target base selector matches selector base
        if (
            preg_match('/\s' . preg_quote($baseTarget, '/') . '(\s|$)/', $baseSelector)
            || preg_match('/' . preg_quote($baseTarget, '/') . '$/', $baseSelector)
        ) {
            return true;
        }

        // Check if target is at the end of selector (e.g., ".article" matches ".container .article")
        if (preg_match('/\s' . preg_quote($cleanTarget, '/') . '(\s|$)/', $cleanSelector)) {
            return true;
        }

        // Check if target is the last part of selector
        if (preg_match('/' . preg_quote($cleanTarget, '/') . '$/', $cleanSelector)) {
            return true;
        }

        return false;
    }

    private function replaceInSelector(string $selector, string $target, string $replacement): string
    {
        // Extract the last part of replacement selector
        $replacementParts    = array_map(trim(...), explode(' ', $replacement));
        $replacementSelector = end($replacementParts);

        // Handle simple selectors (like ".article") that appear at the end
        if (preg_match('/\s' . preg_quote($target, '/') . '(\s|$)/', $selector)) {
            // Split by spaces to handle nested selectors
            $selectorParts = array_map(trim(...), explode(' ', $selector));
            $targetParts   = array_map(trim(...), explode(' ', $target));

            if (count($targetParts) === 1) {
                $newParts = [];
                $replaced = false;

                for ($i = count($selectorParts) - 1; $i >= 0; $i--) {
                    if (! $replaced && $selectorParts[$i] === $target) {
                        $newParts[] = $replacementSelector;
                        $replaced = true;
                    } else {
                        $newParts[] = $selectorParts[$i];
                    }
                }

                return implode(' ', array_reverse($newParts));
            }
        }

        // Handle complex selectors with pseudo-classes
        // For selectors like ".article-container .article" -> ".article-container .featured-article"
        if (preg_match('/^(.*?)\s+' . preg_quote($target, '/') . '(:[^\s,{]+)*/', $selector, $matches)) {
            $context = $matches[1];
            $pseudoClasses = '';
            if (isset($matches[2])) {
                $pseudoClasses = $matches[2];
            }

            return $context . ' ' . $replacementSelector . $pseudoClasses;
        }

        // Fallback: simple string replacement
        return str_replace($target, $replacementSelector, $selector);
    }
}
