<?php

declare(strict_types=1);

namespace DartSass\Values;

readonly class SassList
{
    public function __construct(
        public array $value,
        public string $separator = 'space',
        public bool $bracketed = false
    ) {}
}
