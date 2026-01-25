<?php

declare(strict_types=1);

namespace DartSass\Utils;

use function array_key_last;
use function array_keys;
use function array_values;
use function count;
use function explode;
use function implode;
use function max;
use function preg_match;
use function preg_replace;
use function preg_replace_callback;
use function rtrim;
use function str_contains;
use function str_ends_with;
use function str_repeat;
use function str_replace;
use function str_starts_with;
use function strtolower;
use function substr;
use function trim;

readonly class OutputOptimizer
{
    private const SAFE_PROPERTIES = [
        'background'       => true,
        'background-image' => true,
        'width'            => true,
        'height'           => true,
        'top'              => true,
        'right'            => true,
        'bottom'           => true,
        'left'             => true,
        'margin'           => true,
        'margin-top'       => true,
        'margin-right'     => true,
        'margin-bottom'    => true,
        'margin-left'      => true,
        'padding'          => true,
        'padding-top'      => true,
        'padding-right'    => true,
        'padding-bottom'   => true,
        'padding-left'     => true,
        'border-width'     => true,
        'border-radius'    => true,
        'outline-width'    => true,
        'flex-basis'       => true,
        'text-indent'      => true,
        'letter-spacing'   => true,
        'word-spacing'     => true,
    ];

    private const UNSAFE_PROPERTIES = [
        'display'       => true,
        'filter'        => true,
        'clip-path'     => true,
        'text-overflow' => true,
    ];

    private const ZERO_UNIT_REGEX = '/(?<![\w\-(])0(?:px|em|rem|pt|pc|in|cm|mm|vmin|vmax)(?=[;\s}]|$)/';

    public function __construct(private string $style) {}

    public function optimize(string $css): string
    {
        $css = $this->addCharsetIfNeeded($css);
        $css = $this->removeRedundantProperties($css);
        $css = $this->applyStyleFormat($css);
        $css = preg_replace('/,+/', ',', $css);

        return $this->optimizeZeroUnits($css);
    }

    private function addCharsetIfNeeded(string $css): string
    {
        if (preg_match('/[^\x00-\x7F]/u', $css)) {
            return '@charset "UTF-8";' . "\n" . $css;
        }

        return $css;
    }

    private function applyStyleFormat(string $css): string
    {
        if ($this->style === 'expanded') {
            return $this->formatExpanded($css);
        }

        if ($this->style === 'compressed') {
            return $this->formatCompressed($css);
        }

        return $css;
    }

    private function formatExpanded(string $css): string
    {
        $css = $this->formatLongSelectors($css);

        $lines = explode("\n", $css);
        $depth = 0;

        foreach ($lines as &$line) {
            $trimmed = trim($line);

            if ($trimmed === '') {
                $line = '';

                continue;
            }

            if ($trimmed === '}') {
                $depth = max(0, $depth - 1);
                $line  = str_repeat('  ', $depth) . '}';

                continue;
            }

            $line = str_repeat('  ', $depth) . $trimmed;

            if (str_ends_with($trimmed, '{')) {
                $depth++;
            }
        }

        return implode("\n", $lines);
    }

    private function formatCompressed(string $css): string
    {
        $css = preg_replace('/\/\*(?!#?\s*sourceMappingURL|\s*!).*?\*\//s', '', $css);
        $css = preg_replace('/\s+/', ' ', $css);
        $css = preg_replace('/\s*([{}:;,])\s*/', '$1', $css);
        $css = preg_replace('/,([^}]*){/', ',$1{', $css);
        $css = preg_replace('/;+\s*}/', '}', $css);

        return trim($css);
    }

    private function optimizeZeroUnits(string $css): string
    {
        $comments = [];

        $css = $this->extractComments($css, $comments);
        $css = $this->replaceZeroUnits($css);

        return str_replace(array_keys($comments), array_values($comments), $css);
    }

    private function extractComments(string $css, array &$comments): string
    {
        return preg_replace_callback(
            '/\/\*.*?\*\//s',
            function ($match) use (&$comments) {
                $placeholder = '___COMMENT_' . count($comments) . '___';
                $comments[$placeholder] = $match[0];

                return $placeholder;
            },
            $css
        );
    }

    private function replaceZeroUnits(string $css): string
    {
        return preg_replace_callback(
            '/([a-zA-Z-]+)(\s*:\s*)([^;{}]+)(?=[;}])/m',
            function ($matches) {
                $prop = strtolower($matches[1]);
                $sep  = $matches[2];
                $val  = trim($matches[3]);

                if (isset(self::SAFE_PROPERTIES[$prop])) {
                    $val = preg_replace(self::ZERO_UNIT_REGEX, '0', $val);
                }

                return $matches[1] . $sep . $val;
            },
            $css
        );
    }

    private function removeRedundantProperties(string $css): string
    {
        $lines       = explode("\n", $css);
        $resultLines = [];
        $buffer      = [];
        $inBlock     = false;

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if (str_ends_with($trimmed, '{')) {
                $resultLines = $this->flushBuffer($buffer, $resultLines, $inBlock);
                $resultLines[] = $line;
                $inBlock = true;

                continue;
            }

            if ($trimmed === '}') {
                $resultLines = $this->flushBuffer($buffer, $resultLines, $inBlock);
                $resultLines[] = $line;
                $inBlock = false;

                continue;
            }

            if ($inBlock) {
                $buffer[] = $line;
            } else {
                $resultLines[] = $line;
            }
        }

        return implode("\n", $resultLines);
    }

    private function flushBuffer(array &$buffer, array $resultLines, bool $inBlock): array
    {
        if ($inBlock && ! empty($buffer)) {
            $optimizedBuffer = $this->optimizeBuffer($buffer);

            foreach ($optimizedBuffer as $bufLine) {
                $resultLines[] = $bufLine;
            }

            $buffer = [];
        }

        return $resultLines;
    }

    private function optimizeBuffer(array $buffer): array
    {
        $final = $propMap = [];

        foreach ($buffer as $line) {
            $trimmed = trim($line);

            if (str_starts_with($trimmed, '/*')) {
                $final[] = $line;

                continue;
            }

            if (str_contains($trimmed, ':') && ! str_ends_with($trimmed, '{')) {
                $parts = explode(':', $trimmed, 2);
                $prop  = trim($parts[0]);

                if (isset(self::UNSAFE_PROPERTIES[$prop])) {
                    $final[] = $line;
                } else {
                    if (isset($propMap[$prop])) {
                        $final[$propMap[$prop]] = $line;
                    } else {
                        $final[] = $line;
                        $propMap[$prop] = array_key_last($final);
                    }
                }
            } else {
                $final[] = $line;
            }
        }

        return $final;
    }

    private function formatLongSelectors(string $css): string
    {
        $lines  = explode("\n", $css);
        $result = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if (! str_ends_with($trimmed, '{')) {
                $result[] = $line;

                continue;
            }

            $selector = rtrim(substr($trimmed, 0, -1));

            if (str_starts_with($selector, '@') || ! str_contains($selector, ',')) {
                $result[] = $line;

                continue;
            }

            $result[] = $this->splitSelector($line, $selector);
        }

        return implode("\n", $result);
    }

    private function splitSelector(string $line, string $selector): string
    {
        preg_match('/^(\s*)/', $line, $matches);

        $indent = $matches[1] ?? '';
        $parts  = explode(',', $selector);

        $formatted = [];
        foreach ($parts as $i => $part) {
            $part = trim($part);
            if ($i === count($parts) - 1) {
                $formatted[] = $indent . $part . ' {';
            } else {
                $formatted[] = $indent . $part . ',';
            }
        }

        return implode("\n", $formatted);
    }
}
