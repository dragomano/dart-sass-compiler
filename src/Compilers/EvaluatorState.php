<?php

declare(strict_types=1);

namespace DartSass\Compilers;

use DartSass\Evaluators\ExpressionEvaluator;
use DartSass\Evaluators\InterpolationEvaluator;
use DartSass\Evaluators\OperationEvaluator;

final class EvaluatorState
{
    public ?ExpressionEvaluator $expressionEvaluator = null;

    public ?InterpolationEvaluator $interpolationEvaluator = null;

    public ?OperationEvaluator $operationEvaluator = null;
}
