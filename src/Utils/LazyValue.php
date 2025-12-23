<?php

declare(strict_types=1);

namespace DartSass\Utils;

use Closure;

class LazyValue
{
    private mixed $value = null;

    private bool $computed = false;

    public function __construct(private readonly Closure $computation)
    {
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
