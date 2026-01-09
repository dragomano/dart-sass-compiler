<?php

declare(strict_types=1);

namespace DartSass\Handlers;

use function array_map;
use function in_array;

enum SassModule: string
{
    case COLOR    = 'color';
    case CSS      = 'css';
    case CUSTOM   = 'custom';
    case LIST     = 'list';
    case MAP      = 'map';
    case MATH     = 'math';
    case META     = 'meta';
    case SELECTOR = 'selector';
    case STRING   = 'string';

    public static function isValid(string $namespace): bool
    {
        return in_array(
            $namespace,
            array_map(fn($case) => $case->value, self::cases()),
            true
        );
    }

    public static function getPath(string $namespace): string
    {
        return match ($namespace) {
            self::COLOR->value    => 'sass:color',
            self::LIST->value     => 'sass:list',
            self::MAP->value      => 'sass:map',
            self::MATH->value     => 'sass:math',
            self::META->value     => 'sass:meta',
            self::SELECTOR->value => 'sass:selector',
            self::STRING->value   => 'sass:string',
            default               => $namespace,
        };
    }

    public function path(): string
    {
        return self::getPath($this->value);
    }
}
