<?php

declare(strict_types=1);

use DartSass\Exceptions\InvalidColorException;
use DartSass\Exceptions\LexicalException;

describe('InvalidColorException', function () {
    it('formats message with color value, line and column', function () {
        $exception = new InvalidColorException('#ZZZZZZ', 5, 12);

        expect($exception)
            ->toBeInstanceOf(LexicalException::class)
            ->getMessage()->toBe('Invalid color value (#ZZZZZZ) at line 5, column 12');
    });
});
