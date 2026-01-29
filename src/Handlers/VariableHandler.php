<?php

declare(strict_types=1);

namespace DartSass\Handlers;

use DartSass\Compilers\Environment;

readonly class VariableHandler
{
    public function __construct(private Environment $environment) {}

    public function define(string $name, mixed $value, bool $global = false, bool $default = false): void
    {
        $this->environment->getCurrentScope()->setVariable($name, $value, $global, $default);
    }

    public function get(string $name): mixed
    {
        return $this->environment->getCurrentScope()->getVariable($name);
    }

    public function exists(string $name): bool
    {
        return $this->environment->getCurrentScope()->hasVariable($name);
    }

    public function globalExists(string $name): bool
    {
        return $this->environment->getCurrentScope()->getGlobalScope()->hasLocalVariable($name);
    }
}
