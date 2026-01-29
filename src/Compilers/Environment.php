<?php

declare(strict_types=1);

namespace DartSass\Compilers;

use DartSass\Utils\Scope;

class Environment
{
    private Scope $currentScope;

    public function __construct()
    {
        $this->currentScope = new Scope();
    }

    public function enterScope(): void
    {
        $this->currentScope = new Scope($this->currentScope);
    }

    public function exitScope(): void
    {
        if ($this->currentScope->getParent()) {
            $this->currentScope = $this->currentScope->getParent();
        }
    }

    public function getCurrentScope(): Scope
    {
        return $this->currentScope;
    }
}
