<?php

declare(strict_types=1);

namespace DartSass\Utils;

class SassList
{
    public string $separator;

    public function __construct(public array $value, mixed $separator = 'space', public bool $bracketed = false)
    {
        $this->separator = is_string($separator) ? $separator : 'space';
    }
}
