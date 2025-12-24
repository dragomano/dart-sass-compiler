<?php

declare(strict_types=1);

namespace DartSass\Compilers;

use Closure;
use DartSass\Compiler;
use DartSass\Exceptions\CompilationException;
use DartSass\Handlers\MixinHandler;
use DartSass\Handlers\ModuleHandler;
use DartSass\Parsers\Nodes\IncludeNode;

use function array_map;
use function array_values;
use function explode;
use function is_array;
use function str_contains;
use function uniqid;

readonly class MixinCompiler
{
    public function __construct(private MixinHandler $mixinHandler, private ModuleHandler $moduleHandler) {}

    public function compile(
        IncludeNode $node,
        Compiler $compiler,
        string $parentSelector,
        int $nestingLevel,
        Closure $evaluateExpression
    ): string {
        $includeName = $node->name;

        if (str_contains($includeName, '.')) {
            [$namespace, $property] = explode('.', $includeName, 2);
            return $this->handleModuleMixinCall(
                $namespace,
                $property,
                $node->args ?? [],
                $node->body ?? null,
                $compiler,
                $parentSelector,
                $nestingLevel,
                $evaluateExpression
            );
        }

        $evaluatedArgs = array_map($evaluateExpression, $node->args ?? []);

        try {
            return $this->mixinHandler->include(
                $includeName,
                $evaluatedArgs,
                $node->body ?? null,
                $compiler,
                $parentSelector,
                $nestingLevel
            );
        } catch (CompilationException $compilationException) {
            $moduleState = $this->moduleHandler->getLoadedModules();
            $loadedNamespaces = array_values($moduleState['loadedModules']);

            foreach ($loadedNamespaces as $namespace) {
                try {
                    return $this->handleModuleMixinCall(
                        $namespace,
                        $includeName,
                        $node->args ?? [],
                        $node->body ?? null,
                        $compiler,
                        $parentSelector,
                        $nestingLevel,
                        $evaluateExpression
                    );
                } catch (CompilationException) {
                    continue;
                }
            }

            throw $compilationException;
        }
    }

    private function handleModuleMixinCall(
        string $namespace,
        string $mixinName,
        array $args,
        mixed $content,
        Compiler $compiler,
        string $parentSelector,
        int $nestingLevel,
        Closure $evaluateExpression
    ): string {
        $mixinData = $this->moduleHandler->getProperty($namespace, $mixinName, $evaluateExpression);

        if (! is_array($mixinData) || ! isset($mixinData['type']) || $mixinData['type'] !== 'mixin') {
            throw new CompilationException("Property $mixinName is not a mixin in module $namespace");
        }

        $tempName = 'temp_' . uniqid();
        $this->mixinHandler->define($tempName, $mixinData['args'], $mixinData['body']);

        $evaluatedArgs = array_map($evaluateExpression, $args);

        $css = $this->mixinHandler->include(
            $tempName,
            $evaluatedArgs,
            $content,
            $compiler,
            $parentSelector,
            $nestingLevel
        );

        $this->mixinHandler->removeMixin($tempName);

        return $css;
    }
}
