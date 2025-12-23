<?php

declare(strict_types=1);

namespace DartSass\Utils;

use function array_map;
use function implode;
use function is_array;
use function is_numeric;
use function is_string;
use function preg_replace;
use function round;

class ValueFormatter
{
    public function format(mixed $value): string
    {
        if ($value instanceof LazyValue) {
            return $this->format($value->getValue());
        }

        if (is_array($value)) {
            if (isset($value['value'])) {
                // Handle boolean values specially - check for string booleans first
                if ($value['value'] === 'true' || $value['value'] === 'false') {
                    return $value['value'];
                }

                // Handle PHP boolean values in arrays
                if (is_bool($value['value'])) {
                    return $value['value'] ? 'true' : 'false';
                }

                // Handle quoted string values
                if (is_string($value['value']) && strlen($value['value']) >= 2 &&
                    $value['value'][0] === '"' && $value['value'][strlen($value['value']) - 1] === '"') {
                    return $value['value'];
                }

                $formattedValue = $this->formatNumber($value['value']);

                return $formattedValue . ($value['unit'] ?? '');
            }

            $formattedItems = array_map($this->format(...), $value);
            $result = implode(' ', $formattedItems);

            return preg_replace('/\s*,\s*/', ', ', $result);
        }

        if (is_numeric($value)) {
            return $this->formatNumber($value);
        }

        // Handle PHP boolean values
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

        if ($floatVal > 0 && $floatVal < 1) {
            return preg_replace('/^0\.(\d+)$/', '.$1', $stringVal);
        }

        return $stringVal;
    }
}
