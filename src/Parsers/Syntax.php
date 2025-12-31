<?php

declare(strict_types=1);

namespace DartSass\Parsers;

use InvalidArgumentException;

use function pathinfo;
use function strtolower;

enum Syntax: string
{
    case CSS  = 'css';
    case SASS = 'sass';
    case SCSS = 'scss';

    public static function fromPath(string $path): self
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($ext === self::CSS->value) {
            return self::CSS;
        }

        if ($ext === self::SASS->value) {
            return self::SASS;
        }

        if ($ext === self::SCSS->value || $ext === '') {
            return self::SCSS;
        }

        throw new InvalidArgumentException("Cannot detect syntax from path: $path");
    }
}
