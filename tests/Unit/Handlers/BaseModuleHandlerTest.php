<?php

declare(strict_types=1);

use DartSass\Handlers\ModuleHandlers\BaseModuleHandler;
use DartSass\Handlers\SassModule;
use Tests\ReflectionAccessor;

describe('BaseModuleHandler', function () {
    beforeEach(function () {
        $this->handler = new class () extends BaseModuleHandler {
            public function canHandle(string $functionName): bool
            {
                return true;
            }

            public function handle(string $functionName, array $args): string
            {
                return 'handled';
            }

            public function getSupportedFunctions(): array
            {
                return ['test'];
            }

            public function getModuleNamespace(): SassModule
            {
                return SassModule::CUSTOM;
            }

            public function getModuleFunctions(): array
            {
                return ['test'];
            }
        };

        $this->accessor = new ReflectionAccessor($this->handler);
    });

    describe('normalizeArgs method', function () {
        it('normalizes array with value and unit', function () {
            $args = [['value' => 10, 'unit' => 'px']];

            $result = $this->accessor->callMethod('normalizeArgs', [$args]);

            expect($result)->toEqual([['value' => 10, 'unit' => 'px']]);
        });

        it('normalizes array with only value', function () {
            $args = [['value' => 'red']];

            $result = $this->accessor->callMethod('normalizeArgs', [$args]);

            expect($result)->toEqual(['red']);
        });

        it('leaves plain values unchanged', function () {
            $args = [10, 'test'];

            $result = $this->accessor->callMethod('normalizeArgs', [$args]);

            expect($result)->toEqual([10, 'test']);
        });

        it('handles mixed arguments', function () {
            $args = [
                ['value' => 100, 'unit' => '%'],
                'plain',
                ['value' => 20],
            ];

            $result = $this->accessor->callMethod('normalizeArgs', [$args]);

            expect($result)->toEqual([
                ['value' => 100, 'unit' => '%'],
                'plain',
                20,
            ]);
        });

        it('handles empty array', function () {
            $args = [];

            $result = $this->accessor->callMethod('normalizeArgs', [$args]);

            expect($result)->toEqual([]);
        });
    });
});
