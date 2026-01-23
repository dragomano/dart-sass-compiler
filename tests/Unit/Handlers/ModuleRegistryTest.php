<?php

declare(strict_types=1);

use DartSass\Handlers\Builtins\BaseModuleHandler;
use DartSass\Handlers\ModuleRegistry;
use DartSass\Handlers\SassModule;
use Tests\ReflectionAccessor;

function createTestModuleHandler(
    array $supported = ['func1', 'func2'],
    array $module = ['func1'],
    SassModule $namespace = SassModule::CUSTOM
): BaseModuleHandler {
    return new class ($supported, $module, $namespace) extends BaseModuleHandler {
        public function __construct(
            private readonly array $supported,
            private readonly array $module,
            private readonly SassModule $namespace
        ) {}

        public function canHandle(string $functionName): bool
        {
            return in_array($functionName, $this->supported, true);
        }

        public function handle(string $functionName, array $args): string
        {
            return 'handled';
        }

        public function getSupportedFunctions(): array
        {
            return $this->supported;
        }

        public function getModuleNamespace(): SassModule
        {
            return $this->namespace;
        }

        public function getModuleFunctions(): array
        {
            return $this->module;
        }

        public function getGlobalFunctions(): array
        {
            return $this->supported;
        }
    };
}

describe('ModuleRegistry', function () {
    beforeEach(function () {
        $this->registry = new ModuleRegistry();
        $this->accessor = new ReflectionAccessor($this->registry);
    });

    describe('register method', function () {
        it('registers module functions with namespace', function () {
            $handler = createTestModuleHandler();

            $this->registry->register($handler);

            // Check that module function is registered with namespace
            $functionMap = $this->accessor->getProperty('functionMap');

            expect($functionMap)->toHaveKey('custom.func1')
                ->and($functionMap['custom.func1'])->toBe($handler);
        });

        it('registers all supported functions without namespace', function () {
            $handler = createTestModuleHandler();

            $this->registry->register($handler);

            $functionMap = $this->accessor->getProperty('functionMap');

            expect($functionMap)->toHaveKey('func1')
                ->and($functionMap)->toHaveKey('func2')
                ->and($functionMap['func1'])->toBe($handler)
                ->and($functionMap['func2'])->toBe($handler);
        });

        it('does not register non-module functions with namespace', function () {
            $handler = createTestModuleHandler();

            $this->registry->register($handler);

            $functionMap = $this->accessor->getProperty('functionMap');

            expect($functionMap)->not->toHaveKey('test.func2');
        });

        it('overwrites previous registration for same function', function () {
            $handler1 = createTestModuleHandler(['func1']);
            $handler2 = createTestModuleHandler(['func1']);

            $this->registry->register($handler1);
            $this->registry->register($handler2);

            $functionMap = $this->accessor->getProperty('functionMap');

            expect($functionMap['func1'])->toBe($handler2);
        });
    });

    describe('getHandler method', function () {
        it('returns registered handler for function', function () {
            $handler = createTestModuleHandler(['func1']);

            $this->registry->register($handler);

            expect($this->registry->getHandler('func1'))->toBe($handler);
        });

        it('returns registered handler for namespaced function', function () {
            $handler = createTestModuleHandler(['func1']);

            $this->registry->register($handler);

            expect($this->registry->getHandler('custom.func1'))->toBe($handler);
        });

        it('returns null for unregistered function', function () {
            expect($this->registry->getHandler('unknown'))->toBeNull();
        });
    });

    describe('getFunctionsForModule method', function () {
        it('returns functions for registered module', function () {
            $handler = createTestModuleHandler();

            $this->registry->register($handler);

            $functions = $this->registry->getFunctionsForModule('custom');

            expect($functions)->toContain('func1')
                ->and($functions)->toContain('func2');
        });

        it('returns empty array for unregistered module', function () {
            $functions = $this->registry->getFunctionsForModule('unknown');

            expect($functions)->toBeEmpty();
        });
    });
})->covers(ModuleRegistry::class);
