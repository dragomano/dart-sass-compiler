<?php

declare(strict_types=1);

namespace DartSass\Utils;

class NullLogger implements LoggerInterface
{
    public function debug(string $message, array $context = []): void {}

    public function warn(string $message, array $context = []): void {}

    public function error(string $message, array $context = []): void {}
}
