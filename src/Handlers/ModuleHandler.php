<?php

declare(strict_types=1);

namespace DartSass\Handlers;

use DartSass\Exceptions\CompilationException;
use DartSass\Parsers\Nodes\VariableDeclarationNode;

use function is_array;
use function str_starts_with;
use function substr;

class ModuleHandler
{
    private array $loadedModules = [];

    private array $forwardedProperties = [];

    private array $globalVariables = [];

    public function __construct(
        private readonly ModuleLoader $loader,
        private readonly ModuleForwarder $forwarder,
        private readonly BuiltInModuleProvider $builtInProvider
    ) {}

    public function loadModule(string $path, string $namespace = ''): array
    {
        if ($this->isModuleLoaded($path)) {
            $module = $this->loadedModules[$path];

            return ['cssAst' => $module['cssAst'], 'namespace' => $module['namespace']];
        }

        if (str_starts_with($path, 'sass:')) {
            $ns = $this->registerModule($path, $namespace);
            $this->registerBuiltInModuleProperties($path);

            return ['cssAst' => [], 'namespace' => $ns];
        }

        $ast = $this->loader->loadAst($path);

        if (empty($namespace)) {
            $namespace = $this->loader->getNamespaceFromPath($path);
        }

        $namespace = $this->registerModule($path, $namespace, $ast);

        $cssAst = [];

        $this->forwarder->processAst(
            $ast,
            onCssNode: function ($node) use (&$cssAst): void {
                $cssAst[] = $node;
            },
            onVariable: function ($node) use ($namespace): void {
                $name = $node->name;

                if ($namespace === '*') {
                    $this->globalVariables[$name] = $node;
                } else {
                    $this->forwardedProperties[$namespace][$name] = $node;
                }
            },
            onMixin: function ($node) use ($namespace): void {
                $this->forwardedProperties[$namespace][$node->name] = [
                    'type' => 'mixin',
                    'args' => $node->args,
                    'body' => $node->body,
                ];
            },
            onFunction: function ($node) use ($namespace): void {
                $this->forwardedProperties[$namespace][$node->name] = [
                    'type' => 'function',
                    'args' => $node->args,
                    'body' => $node->body,
                ];
            },
        );

        return ['cssAst' => $cssAst, 'namespace' => $namespace];
    }

    public function forwardModule(
        string $path,
        callable $expression,
        ?string $namespace = null,
        array $config = [],
        array $hide = [],
        array $show = [],
    ): array {
        if ($this->isModuleLoaded($path)) {
            return [];
        }

        $namespace ??= $this->loader->getNamespaceFromPath($path);

        $this->registerModule($path, $namespace);

        $result = $this->forwarder->forwardModule($path, $expression, $config, $hide, $show);

        foreach ($result['variables'] as $name => $value) {
            $this->forwardedProperties[$namespace][$name] = $value;
        }

        foreach ($result['mixins'] as $name => $mixin) {
            $this->forwardedProperties[$namespace][$name] = ['type' => 'mixin', ...$mixin];
        }

        foreach ($result['functions'] as $name => $function) {
            $this->forwardedProperties[$namespace][$name] = ['type' => 'function', ...$function];
        }

        return $result;
    }

    public function getProperty(string $namespace, string $name, ?callable $expression = null): mixed
    {
        if (isset($this->forwardedProperties[$namespace][$name])) {
            $property = $this->forwardedProperties[$namespace][$name];

            if ($property instanceof VariableDeclarationNode && $expression) {
                return $expression($property->value);
            }

            return $property;
        }

        throw new CompilationException("Property $name not found in module $namespace");
    }

    public function isModuleLoaded(string $path): bool
    {
        return isset($this->loadedModules[$path]);
    }

    private function registerModule(string $path, ?string $namespace, array $cssAst = []): string
    {
        $actualNamespace = $namespace ?? $this->loader->getNamespaceFromPath($path);

        $this->loadedModules[$path] = ['namespace' => $actualNamespace, 'cssAst' => $cssAst];

        return $actualNamespace;
    }

    public function getLoadedModules(): array
    {
        return [
            'loadedModules'       => $this->loadedModules,
            'forwardedProperties' => $this->forwardedProperties,
            'globalVariables'     => $this->globalVariables,
        ];
    }

    public function setLoadedModules(array $state): void
    {
        $this->loadedModules       = $state['loadedModules'] ?? [];
        $this->forwardedProperties = $state['forwardedProperties'] ?? [];
        $this->globalVariables     = $state['globalVariables'] ?? [];
    }

    public function getGlobalVariables(): array
    {
        return $this->globalVariables;
    }

    public function getVariables(string $namespace): array
    {
        $variables  = [];
        $properties = $this->forwardedProperties[$namespace] ?? [];

        foreach ($properties as $name => $property) {
            if (! is_array($property) || ! isset($property['type'])) {
                $variables[$name] = $property;
            }
        }

        return $variables;
    }

    public function getMixins(string $namespace): array
    {
        $mixins = [];

        $properties = $this->forwardedProperties[$namespace] ?? [];

        foreach ($properties as $name => $property) {
            if (is_array($property) && $property['type'] === 'mixin') {
                $mixins[$name] = $property;
            }
        }

        return $mixins;
    }

    public function getFunctions(string $namespace): array
    {
        $functions  = [];
        $properties = $this->forwardedProperties[$namespace] ?? [];

        foreach ($properties as $name => $property) {
            if (is_array($property) && $property['type'] === 'function') {
                $functions[$name] = $property;
            }
        }

        return $functions;
    }

    private function registerBuiltInModuleProperties(string $path): void
    {
        $actualNamespace = substr($path, 5); // Remove 'sass:' prefix

        $properties = $this->builtInProvider->provideProperties($path);

        foreach ($properties as $name => $value) {
            $this->forwardedProperties[$actualNamespace][$name] = $value;
        }
    }
}
