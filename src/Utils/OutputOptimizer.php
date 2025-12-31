<?php

declare(strict_types=1);

namespace DartSass\Utils;

use function array_key_last;
use function explode;
use function implode;
use function max;
use function preg_match;
use function preg_replace;
use function preg_replace_callback;
use function str_contains;
use function str_ends_with;
use function str_repeat;
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
        if (preg_match('/[^\x00-\x7F]/u', $css)) {
            $css = '@charset "UTF-8";' . "\n" . $css;
        }

        $css = $this->removeRedundantProperties($css);

        if ($this->style === 'expanded') {
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
                    $line = str_repeat('  ', $depth) . '}';

                    continue;
                }

                $line = str_repeat('  ', $depth) . $trimmed;

                if (str_ends_with($trimmed, '{')) {
                    $depth++;
                }
            }

            $css = implode("\n", $lines);
        }

        if ($this->style === 'compressed') {
            // Remove /* comments */ but preserve sourceMappingURL comments
            $css = preg_replace('/\/\*(?!#?\s*sourceMappingURL).*?\*\//s', '', $css);

            // All whitespace → single space
            $css = preg_replace('/\s+/', ' ', $css);

            // Remove spaces around { } : ; ,
            $css = preg_replace('/\s*([{}:;,])\s*/', '$1', $css);

            // ,selector{ → ,selector{
            $css = preg_replace('/,([^}]*){/', ',$1{', $css);

            // ;;;} → }
            $css = preg_replace('/;+\s*}/', '}', $css);

            // Trim start/end spaces
            $css = trim($css);
        }

        // Clean up multiple consecutive commas
        $css = preg_replace('/,+/', ',', $css);

        return $this->optimizeZeroUnits($css);
    }

    private function optimizeZeroUnits(string $css): string
    {
        // More flexible regex to handle properties with spaces and special characters
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
                if ($inBlock) {
                    $buffer = [];
                }

                $resultLines[] = $line;
                $inBlock = true;

                continue;
            }

            if ($trimmed === '}') {
                if ($inBlock) {
                    $optimizedBuffer = $this->optimizeBuffer($buffer);

                    foreach ($optimizedBuffer as $bufLine) {
                        $resultLines[] = $bufLine;
                    }

                    $buffer = [];
                    $inBlock = false;
                }

                $resultLines[] = $line;

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

    private function optimizeBuffer(array $buffer): array
    {
        $final   = [];
        $propMap = [];

        foreach ($buffer as $line) {
            $trimmed = trim($line);
            $parts   = explode(':', $trimmed, 2);
            $prop    = trim($parts[0]);

            if (isset(self::UNSAFE_PROPERTIES[$prop])) {
                $final[] = $line;
            } else {
                if (isset($propMap[$prop])) {
                    unset($final[$propMap[$prop]]);
                }

                $final[] = $line;
                end($final);
                $propMap[$prop] = array_key_last($final);
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

            if (str_ends_with($trimmed, '{')) {
                $selector = rtrim(substr($trimmed, 0, -1));

                if (str_starts_with($selector, '@')) {
                    $result[] = $line;
                } elseif (str_contains($selector, ',')) {
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

                    $result[] = implode("\n", $formatted);
                } else {
                    $result[] = $line;
                }
            } else {
                $result[] = $line;
            }
        }

        return implode("\n", $result);
    }
}
