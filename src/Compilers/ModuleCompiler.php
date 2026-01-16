<?php

declare(strict_types=1);

namespace DartSass\Compilers;

use Closure;
use DartSass\Parsers\Nodes\VariableDeclarationNode;

use function is_array;

readonly class ModuleCompiler
{
    public function __construct(private CompilerContext $context) {}

    public function compile(
        array $result,
        string $actualNamespace,
        ?string $namespace,
        int $nestingLevel,
        Closure $evaluateExpression,
        Closure $compileAst
    ): string {
        $this->context->variableHandler->enterScope();

        $moduleVars = $this->context->moduleHandler->getVariables($actualNamespace);
        foreach ($moduleVars as $name => $varNode) {
            if ($varNode instanceof VariableDeclarationNode) {
                $value = $evaluateExpression($varNode->properties['value']);
                $this->context->variableHandler->define($name, $value);
            }
        }

        $css = $compileAst($result['cssAst'], '', $nestingLevel);

        $this->context->variableHandler->exitScope();

        if ($namespace === '*') {
            $this->defineGlobalVariablesFromModule($evaluateExpression);
        }

        return $css;
    }

    public function registerModuleMixins(string $namespace): void
    {
        $moduleProperties = $this->context->moduleHandler->getVariables($namespace);

        foreach ($moduleProperties as $propertyName => $propertyData) {
            if (is_array($propertyData) && isset($propertyData['type']) && $propertyData['type'] === 'mixin') {
                $this->context->mixinHandler->define(
                    $namespace . '.' . $propertyName,
                    $propertyData['args'],
                    $propertyData['body']
                );
            }
        }
    }

    private function defineGlobalVariablesFromModule(Closure $evaluateExpression): void
    {
        $globalVariables = $this->context->moduleHandler->getGlobalVariables();

        foreach ($globalVariables as $varName => $varValue) {
            if ($varValue instanceof VariableDeclarationNode) {
                $evaluatedValue = $evaluateExpression($varValue->properties['value']);

                $this->context->variableHandler->define($varName, $evaluatedValue, true);
            }
        }
    }
}
