<?php

declare(strict_types=1);

namespace DartSass\Loaders;

interface LoaderInterface
{
    public function load(string $path): string;
}
