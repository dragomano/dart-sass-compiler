<?php

declare(strict_types=1);

namespace DartSass\Utils;

use function fwrite;
use function sprintf;

use const STDERR;

class StderrLogger implements LoggerInterface
{
    public function debug(string $message, array $context = []): void
    {
        $this->log('DEBUG', $message, $context);
    }

    public function warn(string $message, array $context = []): void
    {
        $this->log('WARNING', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('ERROR', $message, $context);
    }

    private function log(string $level, string $message, array $context): void
    {
        $location = '';
        if (isset($context['file']) && isset($context['line'])) {
            $location = sprintf('%s:%d ', $context['file'], $context['line']);
        }

        fwrite(STDERR, sprintf("%s%s: %s\n", $location, $level, $message));
    }
}
