<?php

declare(strict_types=1);

use DartSass\Handlers\Builtins\CssColorFunctionHandler;
use DartSass\Handlers\SassModule;
use DartSass\Values\SassList;
use DartSass\Values\SassNumber;
use Tests\ReflectionAccessor;

describe('CssColorFunctionHandler', function () {
    beforeEach(function () {
        $this->handler  = new CssColorFunctionHandler();
        $this->accessor = new ReflectionAccessor($this->handler);
    });

    describe('handle method', function () {
        it('handles args as SassList', function () {
            $args = [new SassList([new SassNumber(120), new SassNumber(50), new SassNumber(50)])];

            $result = $this->handler->handle('hsl', $args);

            expect($result)->toBeString();
        });

        it('handles args as array', function () {
            $args = [new SassNumber(120), new SassNumber(50), new SassNumber(50)];

            $result = $this->handler->handle('hsl', $args);

            expect($result)->toBeString();
        });
    });

    describe('extractValue method', function () {
        it('extracts value from SassNumber', function () {
            $result = $this->accessor->callMethod('extractValue', [new SassNumber(42.5)]);

            expect($result)->toBe(42.5);
        });

        it('extracts value from array with value key', function () {
            $result = $this->accessor->callMethod('extractValue', [['value' => 42.5]]);

            expect($result)->toBe(42.5);
        });

        it('extracts value from plain value', function () {
            $result = $this->accessor->callMethod('extractValue', [42.5]);

            expect($result)->toBe(42.5);
        });
    });

    describe('getModuleNamespace method', function () {
        it('returns CSS namespace', function () {
            expect($this->handler->getModuleNamespace())->toEqual(SassModule::CSS);
        });
    });
})->covers(CssColorFunctionHandler::class);
