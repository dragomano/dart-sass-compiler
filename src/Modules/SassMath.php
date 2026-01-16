<?php

declare(strict_types=1);

namespace DartSass\Modules;

use ArrayAccess;

use function in_array;

readonly class SassMath implements ArrayAccess
{
    public function __construct(public float $value, public string $unit) {}

    public function offsetExists(mixed $offset): bool
    {
        return in_array($offset, ['value', 'unit'], true);
    }

    public function offsetGet(mixed $offset): string|null|float
    {
        return match ($offset) {
            'value' => $this->value,
            'unit'  => $this->unit,
            default => null,
        };
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        // Readonly, do nothing
    }

    public function offsetUnset(mixed $offset): void
    {
        // Readonly, do nothing
    }
}
