<?php

declare(strict_types=1);

namespace DartSass\Handlers;

use DartSass\Evaluators\UserFunctionEvaluator;
use DartSass\Handlers\Builtins\CustomFunctionHandler;

use function count;
use function explode;
use function is_array;
use function str_contains;

class FunctionHandler
{
    public function __construct(
        private readonly ModuleHandler $moduleHandler,
        private readonly FunctionRouter $router,
        private readonly CustomFunctionHandler $customFunctionHandler,
        private readonly UserFunctionEvaluator $userFunctionEvaluator,
        private $expression,
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
        $namespace = str_contains($name, '.') ? explode('.', $name, 2)[0] : '';

        if (count($args) === 1 && is_array($args[0]) && ! isset($args[0]['value'])) {
            $args = $args[0];
        }

        $originalName = $name;

        $modulePath = SassModule::getPath($namespace);

        if ($namespace && ! $this->moduleHandler->isModuleLoaded($modulePath)) {
            $this->moduleHandler->loadModule($modulePath, $namespace);
        }

        if (isset($this->userDefinedFunctions[$originalName])) {
            $func = $this->userDefinedFunctions[$originalName];

            return $this->userFunctionEvaluator->evaluate($func, $args, $this->expression);
        }

        return $this->router->route($name, $args);
    }
}
