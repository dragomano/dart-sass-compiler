<?php

declare(strict_types=1);

use DartSass\Handlers\Builtins\ModuleHandlerInterface;
use DartSass\Values\SassFunction;

describe('SassFunction', function () {
    describe('__toString()', function () {
        it('returns function name', function () {
            $handler  = mock(ModuleHandlerInterface::class);
            $function = new SassFunction($handler, 'testFunc');

            expect((string) $function)->toBe('testFunc');
        });
    });
})->covers(SassFunction::class);
