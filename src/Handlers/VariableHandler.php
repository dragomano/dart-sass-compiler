<?php

declare(strict_types=1);

namespace DartSass\Handlers;

use DartSass\Exceptions\CompilationException;

use function array_key_exists;
use function array_key_last;
use function array_merge;
use function array_pop;
use function count;

class VariableHandler
{
    private array $scopes = [];

    private array $globalVariables = [];

    public function enterScope(): void
    {
        $this->scopes[] = [];
    }

    public function exitScope(): void
    {
        if (! empty($this->scopes)) {
            array_pop($this->scopes);
        }
    }

    public function define(string $name, mixed $value, bool $global = false, bool $default = false): void
    {
        if ($default && $this->variableExists($name)) {
            return;
        }

        if ($global || empty($this->scopes)) {
            $this->globalVariables[$name] = $value;
        } else {
            $this->scopes[array_key_last($this->scopes)][$name] = $value;
        }
    }

    public function get(string $name): mixed
    {
        for ($i = count($this->scopes) - 1; $i >= 0; $i--) {
            if (array_key_exists($name, $this->scopes[$i])) {
                return $this->scopes[$i][$name];
            }
        }

        if (array_key_exists($name, $this->globalVariables)) {
            return $this->globalVariables[$name];
        }

        throw new CompilationException("Undefined variable: $name");
    }

    public function getVariables(): array
    {
        $variables = $this->globalVariables;

        foreach ($this->scopes as $scope) {
            $variables = array_merge($variables, $scope);
        }

        return $variables;
    }

    public function setVariables(array $variables): void
    {
        $this->scopes = [];

        $this->globalVariables = $variables;
    }

    public function globalVariableExists(string $name): bool
    {
        return array_key_exists($name, $this->globalVariables);
    }

    private function variableExists(string $name): bool
    {
        if (! empty($this->scopes)) {
            $currentScope = $this->scopes[array_key_last($this->scopes)];
            if (array_key_exists($name, $currentScope)) {
                return true;
            }
        }

        return array_key_exists($name, $this->globalVariables);
    }
}
