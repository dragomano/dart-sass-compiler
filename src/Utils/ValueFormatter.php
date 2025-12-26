<?php

declare(strict_types=1);

namespace DartSass\Utils;

use DartSass\Modules\SassList;

use function array_filter;
use function array_map;
use function implode;
use function is_array;
use function is_bool;
use function is_numeric;
use function is_string;
use function preg_match;
use function preg_replace;
use function round;
use function strlen;

class ValueFormatter
{
    public function format(mixed $value): string
    {
        if ($value instanceof LazyValue) {
            return $this->format($value->getValue());
        }

        if ($value instanceof SassList) {
            return $this->formatSassList($value);
        }

        if (is_array($value)) {
            return $this->formatArray($value);
        }

        if (is_numeric($value)) {
            return $this->formatNumber($value);
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return (string) $value;
    }

    private function formatNumber(mixed $value): string
    {
        $floatVal = (float) $value;

        if ($floatVal == 0) {
            return '0';
        }

        $stringVal = (string) round($floatVal, 15);

        // Remove leading zero for decimals (0.5 -> .5)
        if ($floatVal > 0 && $floatVal < 1) {
            return preg_replace('/^0\./', '.', $stringVal);
        }

        return $stringVal;
    }

    private function formatArray(array $value): string
    {
        if (isset($value['value'])) {
            return $this->formatValueUnit($value);
        }

        return $this->formatPlainArray($value);
    }

    private function formatValueUnit(array $value): string
    {
        $val = $value['value'];

        if (is_bool($val) || $val === 'true' || $val === 'false') {
            return is_bool($val) ? ($val ? 'true' : 'false') : $val;
        }

        if ($this->isQuotedString($val)) {
            return $val;
        }

        $formattedValue = $this->formatNumber($val);

        return $formattedValue . ($value['unit'] ?? '');
    }

    private function isQuotedString(mixed $value): bool
    {
        if (! is_string($value) || strlen($value) < 2) {
            return false;
        }

        return $value[0] === '"' && $value[strlen($value) - 1] === '"';
    }

    private function formatPlainArray(array $value): string
    {
        $formattedItems = $this->filterEmptyItems(
            array_map($this->format(...), $value)
        );

        $separator = $this->detectArraySeparator($formattedItems);
        $result = implode($separator, $formattedItems);

        return preg_replace('/\s*,\s*/', ', ', $result);
    }

    private function detectArraySeparator(array $formattedItems): string
    {
        // Use comma if all items are simple strings without spaces
        foreach ($formattedItems as $item) {
            if (! is_string($item) || preg_match('/\s+/', $item)) {
                return ' ';
            }
        }

        return ', ';
    }

    private function formatSassList(SassList $list): string
    {
        $formattedItems = $this->filterEmptyItems(
            array_map($this->format(...), $list->value)
        );

        $separator = $this->getSeparatorString($list->separator);
        $result = implode($separator, $formattedItems);

        // Fix !important spacing for space-separated lists
        if ($list->separator === 'space') {
            $result = preg_replace('/!\s+important/', '!important', $result);
        }

        return $result;
    }

    private function filterEmptyItems(array $items): array
    {
        return array_filter($items, fn($item): bool => $item !== '');
    }

    private function getSeparatorString(string $separator): string
    {
        return match($separator) {
            'comma' => ', ',
            'slash' => ' / ',
            default => ' ',
        };
    }
}
