<?php

declare(strict_types=1);

use DartSass\Exceptions\LexicalException;

describe('LexicalException', function () {
    it('formats message with provided line and column', function () {
        $exception = new LexicalException('Invalid character', 15, 3);

        expect($exception)
            ->toBeInstanceOf(UnexpectedValueException::class)
            ->getMessage()->toBe('Invalid character at line 15, column 3');
    });

    it('uses default values for line and column', function () {
        $exception = new LexicalException('Unknown error');

        expect($exception->getMessage())
            ->toBe('Unknown error at line 0, column 0');
    });
});
