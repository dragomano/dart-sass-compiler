<?php

declare(strict_types=1);

namespace DartSass\Compilers\Nodes;

use Closure;
use DartSass\Handlers\MixinHandler;
use DartSass\Handlers\ModuleHandler;
use DartSass\Handlers\VariableHandler;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\UseNode;
use DartSass\Parsers\Nodes\VariableDeclarationNode;

use function is_array;

class UseNodeCompiler extends AbstractNodeCompiler
{
    public function __construct(
        private readonly ModuleHandler $moduleHandler,
        private readonly VariableHandler $variableHandler,
        private readonly MixinHandler $mixinHandler,
        private readonly Closure $evaluateExpression,
        private readonly Closure $registerModuleMixins,
        private readonly Closure $compileModule
    ) {}

    protected function getNodeClass(): string
    {
        return UseNode::class;
    }

    protected function compileNode(
        UseNode|AstNode $node,
        string $parentSelector = '',
        int $nestingLevel = 0
    ): string {
        $path      = $node->path;
        $namespace = $node->namespace ?? null;

        if (! $this->moduleHandler->isModuleLoaded($path)) {
            $result          = $this->moduleHandler->loadModule($path, $namespace ?? '');
            $actualNamespace = $result['namespace'];

            ($this->registerModuleMixins)($actualNamespace);

            // Register variables and mixins in current scope for @use without namespace
            $moduleVars = $this->moduleHandler->getVariables($actualNamespace);
            foreach ($moduleVars as $name => $varNode) {
                if ($varNode instanceof VariableDeclarationNode) {
                    $value = ($this->evaluateExpression)($varNode->value);

                    $this->variableHandler->define($name, $value);
                } elseif (is_array($varNode) && isset($varNode['type']) && $varNode['type'] === 'mixin') {
                    $this->mixinHandler->define($name, $varNode['args'], $varNode['body']);
                }
            }

            return ($this->compileModule)(
                $result,
                $actualNamespace,
                $namespace,
                $nestingLevel
            );
        }

        return '';
    }
}
