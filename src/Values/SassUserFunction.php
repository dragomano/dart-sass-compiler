<?php

declare(strict_types=1);

namespace DartSass\Values;

use DartSass\Handlers\FunctionHandler;
use Stringable;

final readonly class SassUserFunction implements Stringable
{
    public function __construct(
        private FunctionHandler $functionHandler,
        private string $functionName
    ) {}

    public function __invoke(mixed ...$args)
    {
        return $this->functionHandler->call($this->functionName, $args);
    }

    public function __toString(): string
    {
        return $this->functionName;
    }
}
