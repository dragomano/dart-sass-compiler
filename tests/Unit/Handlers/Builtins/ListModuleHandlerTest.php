<?php

declare(strict_types=1);

use DartSass\Handlers\Builtins\ListModuleHandler;
use DartSass\Handlers\SassModule;
use DartSass\Modules\ListModule;

beforeEach(function () {
    $this->listModule = new ListModule();
    $this->handler    = new ListModuleHandler($this->listModule);
});

describe('ListModuleHandler', function () {
    describe('canHandle method', function () {
        it('returns true for module functions', function () {
            expect($this->handler->canHandle('append'))->toBeTrue()
                ->and($this->handler->canHandle('index'))->toBeTrue()
                ->and($this->handler->canHandle('is-bracketed'))->toBeTrue()
                ->and($this->handler->canHandle('join'))->toBeTrue()
                ->and($this->handler->canHandle('length'))->toBeTrue()
                ->and($this->handler->canHandle('separator'))->toBeTrue()
                ->and($this->handler->canHandle('nth'))->toBeTrue()
                ->and($this->handler->canHandle('set-nth'))->toBeTrue()
                ->and($this->handler->canHandle('slash'))->toBeTrue()
                ->and($this->handler->canHandle('zip'))->toBeTrue();
        });

        it('returns true for global functions', function () {
            expect($this->handler->canHandle('append'))->toBeTrue()
                ->and($this->handler->canHandle('index'))->toBeTrue()
                ->and($this->handler->canHandle('is-bracketed'))->toBeTrue()
                ->and($this->handler->canHandle('join'))->toBeTrue()
                ->and($this->handler->canHandle('length'))->toBeTrue()
                ->and($this->handler->canHandle('list-separator'))->toBeTrue()
                ->and($this->handler->canHandle('nth'))->toBeTrue()
                ->and($this->handler->canHandle('set-nth'))->toBeTrue()
                ->and($this->handler->canHandle('zip'))->toBeTrue();
        });

        it('returns false for unknown functions', function () {
            expect($this->handler->canHandle('unknown'))->toBeFalse()
                ->and($this->handler->canHandle('list-unknown'))->toBeFalse();
        });
    });

    describe('handle method', function () {
        it('handles function with mapping', function () {
            $result = $this->handler->handle('list-separator', [['item1', 'item2']]);

            expect($result)->toBe('space');
        });

        it('handles function without mapping', function () {
            $result = $this->handler->handle('length', [['item1', 'item2']]);

            expect($result)->toBe(2);
        });
    });

    describe('getModuleNamespace method', function () {
        it('returns LIST namespace', function () {
            expect($this->handler->getModuleNamespace())->toEqual(SassModule::LIST);
        });
    });

    describe('requiresRawResult method', function () {
        it('returns true', function () {
            expect($this->handler->requiresRawResult('length'))->toBeTrue();
        });
    });

    describe('shouldPreserveForConditions method', function () {
        it('returns true for index and is-bracketed', function () {
            expect($this->handler->shouldPreserveForConditions('index'))->toBeTrue()
                ->and($this->handler->shouldPreserveForConditions('is-bracketed'))->toBeTrue();
        });

        it('returns false for other functions', function () {
            expect($this->handler->shouldPreserveForConditions('length'))->toBeFalse()
                ->and($this->handler->shouldPreserveForConditions('append'))->toBeFalse();
        });
    });
})->covers(ListModuleHandler::class);
