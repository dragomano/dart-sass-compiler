<?php

declare(strict_types=1);

namespace DartSass\Compilers\Nodes;

use DartSass\Compilers\CompilerEngineInterface;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\NodeType;
use DartSass\Parsers\Nodes\UseNode;
use DartSass\Parsers\Nodes\VariableDeclarationNode;

use function is_array;

class UseNodeCompiler extends AbstractNodeCompiler
{
    protected function getNodeClass(): string
    {
        return UseNode::class;
    }

    protected function getNodeType(): NodeType
    {
        return NodeType::USE;
    }

    protected function compileNode(
        UseNode|AstNode $node,
        CompilerEngineInterface $engine,
        string $parentSelector = '',
        int $nestingLevel = 0
    ): string {
        $path      = $node->path;
        $namespace = $node->namespace ?? null;

        $moduleHandler = $engine->getModuleHandler();
        if (! $moduleHandler->isModuleLoaded($path)) {
            $result          = $moduleHandler->loadModule($path, $namespace ?? '');
            $actualNamespace = $result['namespace'];

            $engine->getModuleCompiler()->registerModuleMixins($actualNamespace);

            // Register variables and mixins in current scope for @use without namespace
            $moduleVars = $moduleHandler->getVariables($actualNamespace);
            foreach ($moduleVars as $name => $varNode) {
                if ($varNode instanceof VariableDeclarationNode) {
                    $value = $engine->evaluateExpression($varNode->value);
                    $engine->getVariableHandler()->define($name, $value);
                } elseif (is_array($varNode) && isset($varNode['type']) && $varNode['type'] === 'mixin') {
                    $engine->getMixinHandler()->define($name, $varNode['args'], $varNode['body']);
                }
            }

            return $engine->getModuleCompiler()->compile(
                $result,
                $actualNamespace,
                $namespace,
                $nestingLevel,
                $engine->evaluateExpression(...),
                $engine->compileAst(...)
            );
        }

        return '';
    }
}
