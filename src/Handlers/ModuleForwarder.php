<?php

declare(strict_types=1);

namespace DartSass\Handlers;

use function in_array;
use function ltrim;

readonly class ModuleForwarder
{
    public function __construct(private ModuleLoader $loader) {}

    public function forwardModule(
        string $path,
        callable $expression,
        array $config = [],
        array $hide = [],
        array $show = [],
    ): array {
        $ast = $this->loader->loadAst($path);

        $result = [
            'variables' => [],
            'mixins'    => [],
            'functions' => [],
        ];

        $this->processAst(
            $ast,
            onVariable: function ($node) use (
                $expression,
                $config,
                $hide,
                $show,
                &$result
            ): void {
                $name = $node->properties['name'];
                $configKey = ltrim((string) $name, '$');

                if (! $this->isAllowed($name, $hide, $show)) {
                    return;
                }

                $value = $config[$configKey] ?? $expression($node->properties['value']);

                $result['variables'][$name] = $value;
            },
            onMixin: fn($node) => $this->forwardCallable($node, 'mixins', $result, $hide, $show),
            onFunction: fn($node) => $this->forwardCallable($node, 'functions', $result, $hide, $show),
        );

        return $result;
    }

    public function processAst(
        array $ast,
        ?callable $onCssNode = null,
        ?callable $onVariable = null,
        ?callable $onMixin = null,
        ?callable $onFunction = null,
    ): void {
        foreach ($ast as $node) {
            match ($node->type) {
                'variable' => $onVariable && $onVariable($node),
                'mixin'    => $onMixin && $onMixin($node),
                'function' => $onFunction && $onFunction($node),
                default    => $onCssNode && $onCssNode($node),
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

    private function forwardCallable($node, string $type, array &$result, array $hide, array $show): void
    {
        $name = $node->properties['name'];

        if (! $this->isAllowed($name, $hide, $show)) {
            return;
        }

        $payload = [
            'args' => $node->properties['args'],
            'body' => $node->properties['body'],
        ];

        $result[$type][$name] = $payload;
    }
}
