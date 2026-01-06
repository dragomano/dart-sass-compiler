<?php

declare(strict_types=1);

namespace DartSass\Compilers;

use Closure;
use DartSass\Handlers\MixinHandler;
use DartSass\Handlers\ModuleHandler;
use DartSass\Handlers\VariableHandler;
use DartSass\Parsers\Nodes\VariableDeclarationNode;

use function is_array;

readonly class ModuleCompiler
{
    public function __construct(
        private ModuleHandler   $moduleHandler,
        private VariableHandler $variableHandler,
        private MixinHandler    $mixinHandler
    ) {}

    public function compile(
        array $result,
        string $actualNamespace,
        ?string $namespace,
        int $nestingLevel,
        Closure $evaluateExpression,
        Closure $compileAst
    ): string {
        $this->variableHandler->enterScope();

        $moduleVars = $this->moduleHandler->getVariables($actualNamespace);
        foreach ($moduleVars as $name => $varNode) {
            if ($varNode instanceof VariableDeclarationNode) {
                $value = $evaluateExpression($varNode->properties['value']);
                $this->variableHandler->define($name, $value);
            }
        }

        $css = $compileAst($result['cssAst'], '', $nestingLevel);

        $this->variableHandler->exitScope();

        if ($namespace === '*') {
            $this->defineGlobalVariablesFromModule($evaluateExpression);
        }

        return $css;
    }

    public function registerModuleMixins(string $namespace): void
    {
        $moduleProperties = $this->moduleHandler->getVariables($namespace);

        foreach ($moduleProperties as $propertyName => $propertyData) {
            if (is_array($propertyData) && isset($propertyData['type']) && $propertyData['type'] === 'mixin') {
                $this->mixinHandler->define(
                    $namespace . '.' . $propertyName,
                    $propertyData['args'],
                    $propertyData['body']
                );
            }
        }
    }

    private function defineGlobalVariablesFromModule(Closure $evaluateExpression): void
    {
        $globalVariables = $this->moduleHandler->getGlobalVariables();

        foreach ($globalVariables as $varName => $varValue) {
            if ($varValue instanceof VariableDeclarationNode) {
                $evaluatedValue = $evaluateExpression($varValue->properties['value']);

                $this->variableHandler->define($varName, $evaluatedValue, true);
            }
        }
    }
}
