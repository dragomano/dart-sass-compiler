<?php

declare(strict_types=1);

use DartSass\Exceptions\SyntaxException;

describe('SyntaxException', function () {
    it('formats message with line and column', function () {
        $exception = new SyntaxException('Unexpected token', 10, 42);

        expect($exception)
            ->toBeInstanceOf(Exception::class)
            ->getMessage()->toBe('Unexpected token at line 10, column 42');
    });
});
