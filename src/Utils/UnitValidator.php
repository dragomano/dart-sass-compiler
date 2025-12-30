<?php

declare(strict_types=1);

namespace DartSass\Utils;

use function array_unique;
use function count;
use function is_array;
use function is_string;
use function preg_match;

class UnitValidator
{
    public function validate(array $args = []): bool
    {
        $units = [];
        foreach ($args as $arg) {
            $unit = $this->extractUnit($arg);
            if ($unit !== '') {
                $units[] = $unit;
            }
        }

        return count(array_unique($units)) <= 1;
    }

    private function extractUnit(mixed $arg): string
    {
        if (is_array($arg) && isset($arg['unit'])) {
            return $arg['unit'];
        }

        if (is_string($arg) && preg_match('/^-?\d+(?:\.\d+)?(\D*)$/', $arg, $matches)) {
            return $matches[1] ?? '';
        }

        return '';
    }
}
