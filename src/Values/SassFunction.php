<?php

declare(strict_types=1);

namespace DartSass\Values;

use DartSass\Handlers\Builtins\ModuleHandlerInterface;
use Stringable;

final readonly class SassFunction implements Stringable
{
    public function __construct(
        private ModuleHandlerInterface $handler,
        private string $functionName
    ) {}

    public function __invoke(mixed ...$args)
    {
        return $this->handler->handle($this->functionName, $args);
    }

    public function __toString(): string
    {
        return $this->functionName;
    }
}
