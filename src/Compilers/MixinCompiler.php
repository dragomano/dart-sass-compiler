<?php

declare(strict_types=1);

namespace DartSass\Compilers;

use Closure;
use DartSass\Exceptions\CompilationException;
use DartSass\Handlers\MixinHandler;
use DartSass\Handlers\ModuleHandler;
use DartSass\Parsers\Nodes\IncludeNode;
use DartSass\Values\SassList;
use DartSass\Values\SassMixin;
use DartSass\Values\SassNumber;

use function array_column;
use function array_map;
use function array_slice;
use function count;
use function explode;
use function in_array;
use function is_array;
use function is_object;
use function is_string;
use function method_exists;
use function str_contains;
use function uniqid;

readonly class MixinCompiler
{
    public function __construct(
        private MixinHandler $mixinHandler,
        private ModuleHandler $moduleHandler
    ) {}

    public function compile(
        IncludeNode $node,
        string $parentSelector,
        int $nestingLevel,
        Closure $expression
    ): string {
        $includeName = $node->name;

        if (str_contains($includeName, '.')) {
            return $this->compileModuleMixin($node, $parentSelector, $nestingLevel, $expression);
        }

        return $this->compileLocalMixin($node, $parentSelector, $nestingLevel, $expression);
    }

    private function compileModuleMixin(
        IncludeNode $node,
        string $parentSelector,
        int $nestingLevel,
        Closure $expression
    ): string {
        [$namespace, $property] = explode('.', $node->name, 2);

        if ($namespace === 'meta' && $property === 'apply') {
            return $this->handleMetaApply($node, $parentSelector, $nestingLevel, $expression);
        }

        if ($namespace === 'meta' && ! in_array($property, ['apply', 'load-css'], true)) {
            throw new CompilationException("Unknown mixin: $node->name");
        }

        return $this->handleModuleMixinCall(
            $namespace,
            $property,
            $node->args ?? [],
            $node->body ?? null,
            $parentSelector,
            $nestingLevel,
            $expression
        );
    }

    private function compileLocalMixin(
        IncludeNode $node,
        string $parentSelector,
        int $nestingLevel,
        Closure $expression
    ): string {
        $evaluatedArgs = array_map($expression, $node->args ?? []);

        try {
            return $this->mixinHandler->include(
                $node->name,
                $evaluatedArgs,
                $node->body ?? null,
                $parentSelector,
                $nestingLevel
            );
        } catch (CompilationException $compilationException) {
            return $this->tryLoadedModules($node, $parentSelector, $nestingLevel, $expression, $compilationException);
        }
    }

    private function tryLoadedModules(
        IncludeNode $node,
        string $parentSelector,
        int $nestingLevel,
        Closure $expression,
        CompilationException $originalException
    ): string {
        $moduleState      = $this->moduleHandler->getLoadedModules();
        $loadedNamespaces = array_column($moduleState['loadedModules'], 'namespace');

        foreach ($loadedNamespaces as $namespace) {
            try {
                return $this->handleModuleMixinCall(
                    $namespace,
                    $node->name,
                    $node->args ?? [],
                    $node->body ?? null,
                    $parentSelector,
                    $nestingLevel,
                    $expression
                );
            } catch (CompilationException) {
                continue;
            }
        }

        throw $originalException;
    }

    private function handleMetaApply(
        IncludeNode $node,
        string $parentSelector,
        int $nestingLevel,
        Closure $expression
    ): string {
        $evaluatedArgs  = array_map($expression, $node->args ?? []);
        [$mixin, $args] = $this->extractMetaApplyArguments($evaluatedArgs);

        if ($mixin === null) {
            throw new CompilationException('apply() requires at least one argument');
        }

        if (is_object($mixin) && method_exists($mixin, 'apply')) {
            return $mixin->apply($args, $node->body);
        }

        if (is_string($mixin)) {
            return $this->applyStringMixin($mixin, $args, $node->body, $parentSelector, $nestingLevel);
        }

        throw new CompilationException('apply() first argument must be a SassMixin or callable');
    }

    private function extractMetaApplyArguments(array $evaluatedArgs): array
    {
        if (count($evaluatedArgs) === 1 && $evaluatedArgs[0] instanceof SassList) {
            $argsList = $evaluatedArgs[0];
            $mixin    = $argsList->value[0] ?? null;
            $args     = array_slice($argsList->value, 1);
            $args     = $this->normalizeArguments($args);

            return [$mixin, $args];
        }

        $mixin = $evaluatedArgs[0] ?? null;
        $args  = array_slice($evaluatedArgs, 1);

        return [$mixin, $args];
    }

    private function normalizeArguments(array $args): array
    {
        return array_map(function ($arg) {
            if (is_array($arg) && isset($arg['value']) && isset($arg['unit'])) {
                return new SassNumber((float) $arg['value'], $arg['unit']);
            }

            return $arg;
        }, $args);
    }

    private function applyStringMixin(
        string $mixin,
        array $args,
        mixed $body,
        string $parentSelector,
        int $nestingLevel
    ): string {
        if ($this->mixinHandler->hasMixin($mixin)) {
            return $this->mixinHandler->include(
                $mixin,
                $args,
                $body,
                $parentSelector,
                $nestingLevel
            );
        }

        $sassMixin = new SassMixin($this->mixinHandler, $mixin);

        return $sassMixin->apply($args, $body);
    }

    private function handleModuleMixinCall(
        string $namespace,
        string $mixinName,
        array $args,
        mixed $content,
        string $parentSelector,
        int $nestingLevel,
        Closure $expression
    ): string {
        $mixinData = $this->moduleHandler->getProperty($namespace, $mixinName, $expression);

        if (! is_array($mixinData) || ! isset($mixinData['type']) || $mixinData['type'] !== 'mixin') {
            throw new CompilationException("Property $mixinName is not a mixin in module $namespace");
        }

        $tempName = 'temp_' . uniqid();

        $this->mixinHandler->define($tempName, $mixinData['args'], $mixinData['body']);

        $evaluatedArgs = array_map($expression, $args);

        $css = $this->mixinHandler->include(
            $tempName,
            $evaluatedArgs,
            $content,
            $parentSelector,
            $nestingLevel
        );

        $this->mixinHandler->removeMixin($tempName);

        return $css;
    }
}
