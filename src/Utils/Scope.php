<?php

declare(strict_types=1);

namespace DartSass\Utils;

use DartSass\Exceptions\CompilationException;

use function array_key_exists;

class Scope
{
    private array $variables = [];

    private array $mixins    = [];

    private array $functions = [];

    public function __construct(private readonly ?Scope $parent = null) {}

    public function getParent(): ?Scope
    {
        return $this->parent;
    }

    public function getGlobalScope(): Scope
    {
        $current = $this;
        while ($current->parent !== null) {
            $current = $current->parent;
        }

        return $current;
    }

    public function setVariable(string $name, mixed $value, bool $global = false, bool $default = false): void
    {
        if ($global) {
            $this->getGlobalScope()->setVariableForce($name, $value, $default);

            return;
        }

        if ($default && $this->hasVariable($name)) {
            return;
        }

        $scope = $this->findScopeForVariable($name) ?? $this;
        $scope->variables[$name] = $value;
    }

    private function setVariableForce(string $name, mixed $value, bool $default): void
    {
        if ($default && array_key_exists($name, $this->variables)) {
            return;
        }

        $this->variables[$name] = $value;
    }

    public function getVariable(string $name): mixed
    {
        if (array_key_exists($name, $this->variables)) {
            return $this->variables[$name];
        }

        if ($this->parent) {
            return $this->parent->getVariable($name);
        }

        throw new CompilationException("Undefined variable: $name");
    }

    public function hasVariable(string $name): bool
    {
        if (array_key_exists($name, $this->variables)) {
            return true;
        }

        return $this->parent !== null && $this->parent->hasVariable($name);
    }

    public function hasLocalVariable(string $name): bool
    {
        return array_key_exists($name, $this->variables);
    }

    public function setMixin(string $name, array $args, array $body, bool $global = false): void
    {
        $data = ['args' => $args, 'body' => $body];

        if ($global) {
            $this->getGlobalScope()->mixins[$name] = $data;
        } else {
            $this->mixins[$name] = $data;
        }
    }

    public function getMixin(string $name): ?array
    {
        if (array_key_exists($name, $this->mixins)) {
            return $this->mixins[$name];
        }

        if ($this->parent) {
            return $this->parent->getMixin($name);
        }

        throw new CompilationException("Undefined mixin: $name");
    }

    public function hasMixin(string $name): bool
    {
        if (array_key_exists($name, $this->mixins)) {
            return true;
        }

        return $this->parent?->hasMixin($name) ?? false;
    }

    public function removeMixin(string $name): void
    {
        if (array_key_exists($name, $this->mixins)) {
            unset($this->mixins[$name]);

            return;
        }

        $this->parent?->removeMixin($name);
    }

    public function setFunction(string $name, array $args, array $body, bool $global = false): void
    {
        $data = ['args' => $args, 'body' => $body];

        if ($global) {
            $this->getGlobalScope()->functions[$name] = $data;
        } else {
            $this->functions[$name] = $data;
        }
    }

    public function getFunction(string $name): array
    {
        if (array_key_exists($name, $this->functions)) {
            return $this->functions[$name];
        }

        if ($this->parent) {
            return $this->parent->getFunction($name);
        }

        throw new CompilationException("Undefined function: $name");
    }

    public function hasFunction(string $name): bool
    {
        if (array_key_exists($name, $this->functions)) {
            return true;
        }

        return $this->parent?->hasFunction($name) ?? false;
    }

    private function findScopeForVariable(string $name): ?Scope
    {
        if (array_key_exists($name, $this->variables)) {
            return $this;
        }

        $parent = $this->getParent();

        return $parent?->findScopeForVariable($name);
    }
}
