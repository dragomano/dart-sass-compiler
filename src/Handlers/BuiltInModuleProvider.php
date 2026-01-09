<?php

declare(strict_types=1);

namespace DartSass\Handlers;

use const M_E;
use const M_PI;
use const PHP_FLOAT_EPSILON;
use const PHP_FLOAT_MAX;
use const PHP_FLOAT_MIN;
use const PHP_INT_MAX;
use const PHP_INT_MIN;

class BuiltInModuleProvider
{
    public function provideProperties(string $path): array
    {
        $properties = [];

        if ($path === SassModule::MATH->path()) {
            $properties = [
                '$e'                => M_E,
                '$epsilon'          => PHP_FLOAT_EPSILON,
                '$max-number'       => PHP_FLOAT_MAX,
                '$min-number'       => PHP_FLOAT_MIN,
                '$max-safe-integer' => PHP_INT_MAX,
                '$min-safe-integer' => PHP_INT_MIN,
                '$pi'               => M_PI,
            ];
        }

        return $properties;
    }
}
