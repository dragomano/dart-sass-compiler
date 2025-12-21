<?php

declare(strict_types=1);

namespace DartSass\Utils;

use DartSass\Parsers\Nodes\AstNode;

use function array_map;
use function implode;
use function is_array;
use function is_numeric;
use function is_string;
use function preg_replace;
use function round;
use function strtolower;

class ValueFormatter
{
    public function format(mixed $value): string
    {
        if ($value instanceof LazyValue) {
            return $this->format($value->getValue());
        }

        if ($value === null || $value === '') {
            return '0';
        }

        if ($value instanceof AstNode) {
            if (isset($value->properties['value'])) {
                return $this->format($value->properties['value']);
            }

            return 'UNPROCESSED_AST_NODE';
        }

        if (is_array($value)) {
            if (isset($value['value'])) {
                $formattedValue = $this->formatNumber($value['value']);

                return $formattedValue . ($value['unit'] ?? '');
            }

            if (
                isset($value['type'], $value['properties']['value'])
                && $value['type'] === 'identifier'
                && $value['properties']['value'] === 'null'
            ) {
                return '0';
            }

            $formattedItems = array_map($this->format(...), $value);
            $result = implode(' ', $formattedItems);

            return preg_replace('/\s*,\s*/', ', ', $result);
        }

        if (is_string($value) && strtolower($value) === 'null') {
            return '0';
        }

        if (is_numeric($value)) {
            return $this->formatNumber($value);
        }

        return (string) $value;
    }

    private function formatNumber(mixed $value): string
    {
        if (!is_numeric($value)) {
            return (string)$value;
        }

        $floatVal = (float)$value;

        if ($floatVal == 0) {
            return '0';
        }

        $stringVal = (string)round($floatVal, 15);

        if ($floatVal > 0 && $floatVal < 1) {
            return preg_replace('/^0\.(\d+)$/', '.$1', $stringVal);
        }

        if ($floatVal > -1 && $floatVal < 0) {
            return preg_replace('/^-0\.(\d+)$/', '-.$1', $stringVal);
        }

        return $stringVal;
    }
}
