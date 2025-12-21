<?php

declare(strict_types=1);

namespace DartSass\Utils;

use Closure;
use Stringable;

class LazyValue implements Stringable
{
    private mixed $value = null;

    private bool $computed = false;

    public function __construct(private readonly Closure $computation) {}

    public function __toString(): string
    {
        $value = $this->getValue();

        return is_string($value) ? $value : (string) $value;
    }

    public function getValue(): mixed
    {
        if (! $this->computed) {
            $this->value = ($this->computation)();
            $this->computed = true;
        }

        return $this->value;
    }
}
