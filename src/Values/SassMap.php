<?php

declare(strict_types=1);

namespace DartSass\Values;

use Stringable;

use function implode;
use function is_array;
use function is_object;
use function is_string;
use function json_encode;
use function method_exists;

readonly class SassMap implements Stringable
{
    public function __construct(public array $value) {}

    public function __toString(): string
    {
        $parts = [];

        foreach ($this->value as $key => $value) {
            $formattedKey = is_string($key) ? $key : json_encode($key);

            if ($value instanceof SassMixin) {
                $formattedValue = $value;
            } elseif (is_object($value) && method_exists($value, '__toString')) {
                $formattedValue = $value->__toString();
            } elseif (is_array($value) || is_object($value)) {
                $formattedValue = json_encode($value);
            } else {
                $formattedValue = json_encode($value);
            }

            $parts[] = "$formattedKey: $formattedValue";
        }

        return '(' . implode(', ', $parts) . ')';
    }
}
