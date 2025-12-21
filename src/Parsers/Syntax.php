<?php

declare(strict_types=1);

namespace DartSass\Parsers;

use InvalidArgumentException;

use function pathinfo;
use function strtolower;

enum Syntax: string
{
    case SASS = 'sass';
    case SCSS = 'scss';

    public static function fromPath(string $path): self
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($ext) {
            'sass'     => self::SASS,
            'scss', '' => self::SCSS,
            default    => throw new InvalidArgumentException("Cannot detect syntax from path: $path"),
        };
    }
}
