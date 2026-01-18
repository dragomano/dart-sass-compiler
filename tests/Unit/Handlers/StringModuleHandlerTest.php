<?php

declare(strict_types=1);

use DartSass\Handlers\Builtins\StringModuleHandler;
use DartSass\Modules\StringModule;

beforeEach(function () {
    $this->stringModule = new StringModule();
    $this->handler      = new StringModuleHandler($this->stringModule);
});

describe('StringModuleHandler', function () {
    describe('canHandle method', function () {
        it('returns true for module functions', function () {
            expect($this->handler->canHandle('quote'))->toBeTrue()
                ->and($this->handler->canHandle('index'))->toBeTrue()
                ->and($this->handler->canHandle('insert'))->toBeTrue()
                ->and($this->handler->canHandle('length'))->toBeTrue()
                ->and($this->handler->canHandle('slice'))->toBeTrue()
                ->and($this->handler->canHandle('split'))->toBeTrue()
                ->and($this->handler->canHandle('to-upper-case'))->toBeTrue()
                ->and($this->handler->canHandle('to-lower-case'))->toBeTrue()
                ->and($this->handler->canHandle('unique-id'))->toBeTrue()
                ->and($this->handler->canHandle('unquote'))->toBeTrue();
        });

        it('returns true for global functions', function () {
            expect($this->handler->canHandle('str-index'))->toBeTrue()
                ->and($this->handler->canHandle('str-length'))->toBeTrue()
                ->and($this->handler->canHandle('str-insert'))->toBeTrue()
                ->and($this->handler->canHandle('str-slice'))->toBeTrue();
        });

        it('returns false for unknown functions', function () {
            expect($this->handler->canHandle('unknown'))->toBeFalse()
                ->and($this->handler->canHandle('str-unknown'))->toBeFalse();
        });
    });
})->covers(StringModuleHandler::class);
