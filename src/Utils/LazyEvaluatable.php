<?php

declare(strict_types=1);

namespace DartSass\Utils;

interface LazyEvaluatable
{
    public function evaluate(): mixed;
}
