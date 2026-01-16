<?php

declare(strict_types=1);

namespace DartSass\Compilers;

use Closure;
use DartSass\Exceptions\CompilationException;
use DartSass\Parsers\Nodes\IncludeNode;

use function array_map;
use function array_values;
use function explode;
use function is_array;
use function str_contains;
use function uniqid;

readonly class MixinCompiler
{
    public function __construct(private CompilerContext $context) {}

    public function compile(
        IncludeNode $node,
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
                $parentSelector,
                $nestingLevel,
                $evaluateExpression
            );
        }

        $evaluatedArgs = array_map($evaluateExpression, $node->args ?? []);

        try {
            return $this->context->mixinHandler->include(
                $includeName,
                $evaluatedArgs,
                $node->body ?? null,
                $parentSelector,
                $nestingLevel
            );
        } catch (CompilationException $compilationException) {
            $moduleState = $this->context->moduleHandler->getLoadedModules();
            $loadedNamespaces = array_values($moduleState['loadedModules']);

            foreach ($loadedNamespaces as $namespace) {
                try {
                    return $this->handleModuleMixinCall(
                        $namespace,
                        $includeName,
                        $node->args ?? [],
                        $node->body ?? null,
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
        string $parentSelector,
        int $nestingLevel,
        Closure $evaluateExpression
    ): string {
        $mixinData = $this->context->moduleHandler->getProperty($namespace, $mixinName, $evaluateExpression);

        if (! is_array($mixinData) || ! isset($mixinData['type']) || $mixinData['type'] !== 'mixin') {
            throw new CompilationException("Property $mixinName is not a mixin in module $namespace");
        }

        $tempName = 'temp_' . uniqid();

        $this->context->mixinHandler->define($tempName, $mixinData['args'], $mixinData['body']);

        $evaluatedArgs = array_map($evaluateExpression, $args);

        $css = $this->context->mixinHandler->include(
            $tempName,
            $evaluatedArgs,
            $content,
            $parentSelector,
            $nestingLevel
        );

        $this->context->mixinHandler->removeMixin($tempName);

        return $css;
    }
}
