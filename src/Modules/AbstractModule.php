<?php

declare(strict_types=1);

namespace DartSass\Modules;

use DartSass\Exceptions\CompilationException;

use function count;
use function is_string;

abstract class AbstractModule
{
    protected function validateArgs(array $args, int $expected, string $function, bool $minimum = false): array
    {
        $count = count($args);
        $valid = $minimum ? $count >= $expected : $count === $expected;

        if (! $valid) {
            if ($expected === 0 && ! $minimum) {
                throw new CompilationException("$function() takes no arguments");
            }

            $numbers = [1 => 'one', 2 => 'two', 3 => 'three'];
            $word    = $numbers[$expected] ?? (string) $expected;
            $plural  = $expected === 1 ? '' : 's';
            $type    = $minimum ? 'at least' : 'exactly';

            throw new CompilationException("$function() requires $type $word argument$plural");
        }

        return $args;
    }

    protected function validateStringArgs(array $args, string $function): void
    {
        foreach ($args as $arg) {
            if (! is_string($arg)) {
                throw new CompilationException("$function() arguments must be strings");
            }
        }
    }

    protected function validateArgRange(array $args, int $min, int $max, string $function): void
    {
        $count = count($args);
        if ($count < $min || $count > $max) {
            $numbers = [0 => 'zero', 1 => 'one', 2 => 'two', 3 => 'three'];
            $minWord = $numbers[$min] ?? (string) $min;
            $maxWord = $numbers[$max] ?? (string) $max;

            throw new CompilationException("$function() requires $minWord or $maxWord arguments");
        }
    }
}
