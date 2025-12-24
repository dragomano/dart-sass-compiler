<?php

declare(strict_types=1);

namespace DartSass\Handlers;

use DartSass\Exceptions\CompilationException;
use DartSass\Loaders\LoaderInterface;
use DartSass\Parsers\Nodes\VariableDeclarationNode;
use DartSass\Parsers\ParserFactory;

use function basename;
use function in_array;
use function ltrim;
use function pathinfo;
use function rtrim;
use function str_starts_with;
use function substr;

use const M_E;
use const M_PI;

class ModuleHandler
{
    private array $loadedModules = [];

    private array $forwardedProperties = [];

    private array $globalVariables = [];

    public function __construct(
        private readonly LoaderInterface $loader,
        private readonly ParserFactory $parserFactory
    ) {}

    public function loadModule(string $path, ?string $namespace = null): array
    {
        if ($this->isModuleLoaded($path)) {
            return ['cssAst' => [], 'namespace' => $this->loadedModules[$path]];
        }

        if (str_starts_with($path, 'sass:')) {
            $ns = $this->registerModule($path, $namespace);

            $this->registerBuiltInModuleProperties($path);

            return ['cssAst' => [], 'namespace' => $ns];
        }

        $ast       = $this->loadAst($path);
        $namespace = $this->registerModule($path, $namespace);
        $cssAst    = [];

        $this->processAst(
            $ast,
            onCssNode: function ($node) use (&$cssAst): void {
                $cssAst[] = $node;
            },
            onVariable: function ($node) use ($namespace): void {
                $name = $node->properties['name'];

                if ($namespace === '*') {
                    $this->globalVariables[$name] = $node;
                } else {
                    $this->forwardedProperties[$namespace][$name] = $node;
                }
            },
            onMixin: fn($node): array => $this->forwardedProperties[$namespace][$node->properties['name']] = [
                'type' => 'mixin',
                'args' => $node->properties['args'],
                'body' => $node->properties['body'],
            ],
            onFunction: fn($node): array => $this->forwardedProperties[$namespace][$node->properties['name']] = [
                'type' => 'function',
                'args' => $node->properties['args'],
                'body' => $node->properties['body'],
            ],
        );

        return ['cssAst' => $cssAst, 'namespace' => $namespace];
    }

    public function forwardModule(
        string $path,
        callable $evaluateExpression,
        ?string $namespace = null,
        array $config = [],
        array $hide = [],
        array $show = [],
    ): array {
        if ($this->isModuleLoaded($path)) {
            return [];
        }

        $ast       = $this->loadAst($path);
        $namespace = $this->registerModule($path, $namespace);

        $result = [
            'variables' => [],
            'mixins'    => [],
            'functions' => [],
        ];

        $this->processAst(
            $ast,
            onCssNode: fn(): null => null,
            onVariable: function ($node) use (
                $namespace,
                $evaluateExpression,
                $config,
                $hide,
                $show,
                &$result
            ): void {
                $name      = $node->properties['name'];
                $configKey = ltrim((string) $name, '$');

                if (! $this->isAllowed($name, $hide, $show)) {
                    return;
                }

                $value = $config[$configKey] ?? $evaluateExpression($node->properties['value']);

                $this->forwardedProperties[$namespace][$name] = $value;
                $result['variables'][$name] = $value;
            },
            onMixin: fn($node) => $this->forwardCallable($node, $namespace, 'mixins', $result, $hide, $show),
            onFunction: fn($node) => $this->forwardCallable($node, $namespace, 'functions', $result, $hide, $show),
        );

        return $result;
    }

    public function getProperty(
        string $namespace,
        string $name,
        ?callable $evaluateExpression = null
    ): mixed {
        if (isset($this->forwardedProperties[$namespace][$name])) {
            $property = $this->forwardedProperties[$namespace][$name];

            if ($property instanceof VariableDeclarationNode && $evaluateExpression) {
                return $evaluateExpression($property->properties['value']);
            }

            return $property;
        }

        throw new CompilationException("Property $name not found in module $namespace");
    }

    public function isModuleLoaded(string $path): bool
    {
        return isset($this->loadedModules[$path]);
    }

    private function getNamespaceFromPath(string $path): string
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $basename  = basename($path, '.' . $extension);

        return ltrim($basename, '_');
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
        return $this->forwardedProperties[$namespace] ?? [];
    }

    private function loadAst(string $path): array
    {
        $content = $this->loader->load($path);
        $parser  = $this->parserFactory->createFromPath($content, $path);

        return $parser->parse();
    }

    private function registerModule(string $path, ?string $namespace): string
    {
        $actualNamespace = $namespace ?? $this->getNamespaceFromPath($path);

        $this->loadedModules[$path] = $actualNamespace;

        return $actualNamespace;
    }

    private function processAst(
        array $ast,
        callable $onCssNode,
        ?callable $onVariable = null,
        ?callable $onMixin = null,
        ?callable $onFunction = null,
    ): void {
        foreach ($ast as $node) {
            match ($node->type) {
                'variable' => $onVariable && $onVariable($node),
                'mixin'    => $onMixin && $onMixin($node),
                'function' => $onFunction && $onFunction($node),
                default    => $onCssNode($node),
            };
        }
    }

    private function isAllowed(string $name, array $hide, array $show): bool
    {
        if ($hide && in_array($name, $hide, true)) {
            return false;
        }

        if ($show && ! in_array($name, $show, true)) {
            return false;
        }

        return true;
    }

    private function forwardCallable(
        $node,
        string $namespace,
        string $type,
        array &$result,
        array $hide,
        array $show
    ): void {
        $name = $node->properties['name'];

        if (! $this->isAllowed($name, $hide, $show)) {
            return;
        }

        $payload = [
            'args' => $node->properties['args'],
            'body' => $node->properties['body'],
        ];

        $this->forwardedProperties[$namespace][$name] = [
            'type' => rtrim($type, 's'),
            ...$payload,
        ];

        $result[$type][$name] = $payload;
    }

    private function registerBuiltInModuleProperties(string $path): void
    {
        if ($path === 'sass:math') {
            // Remove 'sass:' prefix
            $actualNamespace = substr($path, 5);

            $this->forwardedProperties[$actualNamespace]['$e'] = M_E;
            $this->forwardedProperties[$actualNamespace]['$epsilon'] = 1e-12;
            $this->forwardedProperties[$actualNamespace]['$max-number'] = 1e308;
            $this->forwardedProperties[$actualNamespace]['$max-safe-integer'] = 9e15;
            $this->forwardedProperties[$actualNamespace]['$min-number'] = -1e308;
            $this->forwardedProperties[$actualNamespace]['$min-safe-integer'] = -9e15;
            $this->forwardedProperties[$actualNamespace]['$pi'] = M_PI;
        }
    }
}
