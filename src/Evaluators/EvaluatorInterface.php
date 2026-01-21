<?php

declare(strict_types=1);

namespace DartSass\Evaluators;

interface EvaluatorInterface
{
    public function supports(mixed $expression): bool;

    public function evaluate(mixed $expression): mixed;
}
