<?php

declare(strict_types=1);

namespace DartSass\Handlers;

use function count;
use function explode;
use function is_array;
use function is_int;
use function str_contains;

class FunctionHandler
{
    public function __construct(
        private readonly ModuleHandler $moduleHandler,
        private readonly FunctionRouter $router,
        private readonly CustomFunctionHandler $customFunctionHandler,
        private $evaluateExpression,
        private array $userDefinedFunctions = []
    ) {}

    public function addCustom(string $name, callable $callback): void
    {
        $this->customFunctionHandler->addCustomFunction($name, $callback);
    }

    public function defineUserFunction(
        string $name,
        array $args,
        array $body,
        VariableHandler $variableHandler,
    ): void {
        $this->userDefinedFunctions[$name] = [
            'args'    => $args,
            'body'    => $body,
            'handler' => $variableHandler,
        ];
    }

    public function getUserFunctions(): array
    {
        return [
            'customFunctions'      => $this->customFunctionHandler->getSupportedFunctions(),
            'userDefinedFunctions' => $this->userDefinedFunctions,
        ];
    }

    public function setUserFunctions(array $state): void
    {
        if (isset($state['customFunctions'])) {
            $this->customFunctionHandler->setCustomFunctions($state['customFunctions']);
        }

        $this->userDefinedFunctions = $state['userDefinedFunctions'] ?? [];
    }

    public function call(string $name, array $args)
    {
        $namespace = str_contains($name, '.') ? explode('.', $name, 2)[0] : null;

        if (count($args) === 1 && is_array($args[0]) && ! isset($args[0]['value'])) {
            $args = $args[0];
        }

        $originalName = $name;

        $modulePath = match ($namespace) {
            'color'    => 'sass:color',
            'list'     => 'sass:list',
            'map'      => 'sass:map',
            'math'     => 'sass:math',
            'meta'     => 'sass:meta',
            'selector' => 'sass:selector',
            'string'   => 'sass:string',
            default    => $namespace,
        };

        if ($namespace && ! $this->moduleHandler->isModuleLoaded($modulePath)) {
            $this->moduleHandler->loadModule($modulePath, $namespace);
        }

        if (isset($this->userDefinedFunctions[$originalName])) {
            $func = $this->userDefinedFunctions[$originalName];

            return $this->evaluateUserFunction($func, $args);
        }

        return $this->router->route($name, $args);
    }

    private function evaluateUserFunction(array $func, array $args): mixed
    {
        $body = $func['body'];

        $variableHandler = $func['handler'];
        $variableHandler->enterScope();

        $argIndex = 0;
        foreach ($func['args'] as $argName => $defaultValue) {
            if (is_int($argName)) {
                $paramName = $defaultValue;
                $default   = null;
            } else {
                $paramName = $argName;
                $default   = $defaultValue;
            }

            $value = $args[$argIndex] ?? $default;
            if ($value === null) {
                $value = ($this->evaluateExpression)($default);
            }

            $variableHandler->define($paramName, $value);
            $argIndex++;
        }

        foreach ($body as $statement) {
            if ($statement->type === 'return') {
                $returnValue = $statement->properties['value'];

                if (
                    $returnValue->type === 'operation'
                    && $returnValue->properties['left']->type === 'variable'
                    && $returnValue->properties['operator'] === '*'
                    && $returnValue->properties['right']->type === 'number'
                ) {
                    $argValue   = $args[0] ?? 0;
                    $multiplier = $returnValue->properties['right']->properties['value'];

                    if (is_array($argValue) && isset($argValue['value'])) {
                        $result = $argValue['value'] * $multiplier;
                        $unit   = $argValue['unit'] ?? '';

                        $variableHandler->exitScope();

                        return ['value' => $result, 'unit' => $unit];
                    }

                    $variableHandler->exitScope();

                    return $argValue * $multiplier;
                }

                $result = ($this->evaluateExpression)($returnValue);

                $variableHandler->exitScope();

                return $result;
            }
        }

        $variableHandler->exitScope();

        return null;
    }
}
