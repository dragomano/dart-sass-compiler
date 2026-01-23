<?php

declare(strict_types=1);

namespace DartSass\Values;

use DartSass\Utils\ValueFormatter;
use Stringable;

use function array_filter;
use function array_map;
use function implode;

readonly class SassList implements Stringable
{
    public function __construct(
        public array $value,
        public string $separator = 'space',
        public bool $bracketed = false
    ) {}

    public function __toString(): string
    {
        $formatter = new ValueFormatter();

        $formattedItems = array_filter(
            array_map($formatter->format(...), $this->value),
            fn($item): bool => $item !== ''
        );

        $separator = match($this->separator) {
            'comma' => ', ',
            'slash' => ' / ',
            default => ' ',
        };

        $result = implode($separator, $formattedItems);

        if ($this->bracketed) {
            return '[' . $result . ']';
        }

        return $result;
    }
}
