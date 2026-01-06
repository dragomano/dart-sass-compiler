<?php

declare(strict_types=1);

namespace DartSass\Compilers\Nodes;

use DartSass\Compilers\CompilerContext;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\UseNode;

class UseNodeCompiler extends AbstractNodeCompiler
{
    protected function getNodeClass(): string
    {
        return UseNode::class;
    }

    protected function getNodeType(): string
    {
        return 'use';
    }

    protected function compileNode(
        AstNode $node,
        CompilerContext $context,
        string $parentSelector = '',
        int $nestingLevel = 0
    ): string {
        $path      = $node->properties['path'];
        $namespace = $node->properties['namespace'] ?? null;

        if (! $context->moduleHandler->isModuleLoaded($path)) {
            $result          = $context->moduleHandler->loadModule($path, $namespace);
            $actualNamespace = $result['namespace'];

            $context->moduleCompiler->registerModuleMixins($actualNamespace);

            return $context->moduleCompiler->compile(
                $result,
                $actualNamespace,
                $namespace,
                $nestingLevel,
                $context->engine->evaluateExpression(...),
                $context->engine->compileAst(...)
            );
        }

        return '';
    }
}
