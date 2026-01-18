<?php

declare(strict_types=1);

namespace DartSass\Modules;

use DartSass\Exceptions\CompilationException;

use function array_filter;
use function array_map;
use function array_merge;
use function array_shift;
use function array_unique;
use function array_values;
use function count;
use function explode;
use function implode;
use function is_string;
use function preg_match;
use function preg_quote;
use function preg_split;
use function str_replace;
use function str_starts_with;
use function substr;
use function trim;

class SelectorModule
{
    public function isSuperSelector(array $args): bool
    {
        [$super, $sub] = $this->validateArgs($args, 2, 'is-superselector');

        $this->validateStringArgs([$super, $sub], 'is-superselector');

        return $this->selectorMatches($sub, $super);
    }

    public function append(array $args): string
    {
        $this->validateArgs($args, 2, 'selector-append', true);
        $this->validateStringArgs($args, 'selector-append');

        $first      = array_shift($args);
        $combinator = '';

        if (preg_match('/\s*([>+~])\s*$/', $first, $matches)) {
            $combinator = $matches[1];
            $first      = (string) preg_replace('/\s*([>+~])\s*$/', '', $first);
        }

        $appended = [];
        foreach ($args as $sel) {
            $parts = array_map(trim(...), explode(',', $sel));
            foreach ($parts as $part) {
                $appended[] = $first . $combinator . $part;
            }
        }

        return implode(', ', $appended);
    }

    public function extend(array $args): string
    {
        [$selector, $extendee, $extender] = $this->validateArgs($args, 3, 'selector-extend');

        $this->validateStringArgs([$selector, $extendee, $extender], 'selector-extend');

        $extended = str_replace(
            $this->normalizeSelector($extendee),
            $this->normalizeSelector($extender),
            $this->normalizeSelector($selector)
        );

        return $extended !== $this->normalizeSelector($selector)
            ? $selector . ', ' . $extended
            : $selector;
    }

    public function nest(array $args): string
    {
        $this->validateArgs($args, 2, 'selector-nest', true);
        $this->validateStringArgs($args, 'selector-nest');

        return implode(' ', $args);
    }

    public function parse(array $args): SassList
    {
        [$selector] = $this->validateArgs($args, 1, 'selector-parse');

        $this->validateStringArgs([$selector], 'selector-parse');

        $selectors = array_filter(
            array_map(trim(...), explode(',', $selector)),
            fn($s): bool => $s !== ''
        );

        return new SassList($selectors, 'comma', false);
    }

    public function replace(array $args): string
    {
        [$selector, $original, $replacement] = $this->validateArgs($args, 3, 'selector-replace');

        $this->validateStringArgs([$selector, $original, $replacement], 'selector-replace');

        return str_replace(
            $this->normalizeSelector($original),
            trim($replacement),
            $this->normalizeSelector($selector)
        );
    }

    public function unify(array $args): ?string
    {
        [$selector1, $selector2] = $this->validateArgs($args, 2, 'selector-unify');

        $this->validateStringArgs([$selector1, $selector2], 'selector-unify');

        $parts1 = $this->parseSelectorParts($selector1);
        $parts2 = $this->parseSelectorParts($selector2);

        if ($parts1['tag'] && $parts2['tag'] && $parts1['tag'] !== $parts2['tag']) {
            return null;
        }

        $ids = array_unique(array_merge($parts1['ids'], $parts2['ids']));
        if (count($ids) > 1) {
            return null;
        }

        $tag     = $parts1['tag'] ?: $parts2['tag'];
        $classes = array_unique(array_merge($parts1['classes'], $parts2['classes']));

        return $tag
            . implode('', array_map(fn($c): string => '.' . $c, $classes))
            . implode('', array_map(fn($i): string => '#' . $i, $ids));
    }

    public function simpleSelectors(array $args): SassList
    {
        [$selector] = $this->validateArgs($args, 1, 'simple-selectors');

        $this->validateStringArgs([$selector], 'simple-selectors');

        $parts = array_values(
            array_filter(
                preg_split('/(?<!:)(?=[.#:\[])/', trim($selector)),
                fn($p): bool => $p !== ''
            )
        );

        return new SassList($parts, 'space', false);
    }

    private function validateArgs(array $args, int $expected, string $function, bool $minimum = false): array
    {
        $count = count($args);
        $valid = $minimum ? $count >= $expected : $count === $expected;

        if (! $valid) {
            $numbers = [1 => 'one', 2 => 'two', 3 => 'three'];
            $word    = $numbers[$expected] ?? (string) $expected;
            $plural  = $expected === 1 ? '' : 's';
            $type    = $minimum ? 'at least' : 'exactly';

            throw new CompilationException("$function() requires $type $word argument$plural");
        }

        return $args;
    }

    private function validateStringArgs(array $args, string $function): void
    {
        foreach ($args as $arg) {
            if (! is_string($arg)) {
                throw new CompilationException("$function() arguments must be strings");
            }
        }
    }

    private function normalizeSelector(string $selector): string
    {
        return (string) preg_replace('/\s+/', ' ', trim($selector));
    }

    private function selectorMatches(string $selector, string $target): bool
    {
        $cleanSelector = $this->normalizeSelector($selector);
        $cleanTarget   = $this->normalizeSelector($target);

        return $cleanSelector === $cleanTarget
            || preg_match('/\s' . preg_quote($cleanTarget, '/') . '(\s|$)/', $cleanSelector)
            || preg_match('/^(.*?)\s+' . preg_quote($cleanTarget, '/') . '(:[^\s,{]+)*/', $cleanSelector);
    }

    private function parseSelectorParts(string $selector): array
    {
        $parts = ['tag' => '', 'classes' => [], 'ids' => []];

        preg_match_all('/([.#]?[^.#]+)/', trim($selector), $matches);

        foreach ($matches[1] as $part) {
            if (str_starts_with($part, '.')) {
                $parts['classes'][] = substr($part, 1);
            } elseif (str_starts_with($part, '#')) {
                $parts['ids'][] = substr($part, 1);
            } else {
                $parts['tag'] = $part;
            }
        }

        return $parts;
    }
}
