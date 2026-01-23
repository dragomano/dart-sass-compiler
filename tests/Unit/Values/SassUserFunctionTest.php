<?php

declare(strict_types=1);

use DartSass\Handlers\FunctionHandler;
use DartSass\Values\SassUserFunction;

describe('SassUserFunction', function () {
    describe('__toString()', function () {
        it('returns function name', function () {
            $handler  = mock(FunctionHandler::class);
            $function = new SassUserFunction($handler, 'userFunc');

            expect((string) $function)->toBe('userFunc');
        });
    });
})->covers(SassUserFunction::class);
