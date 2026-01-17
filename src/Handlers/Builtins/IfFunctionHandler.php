<?php

declare(strict_types=1);

namespace DartSass\Handlers\Builtins;

use DartSass\Handlers\SassModule;

use function count;
use function is_string;
use function strtolower;

class IfFunctionHandler extends BaseModuleHandler implements LazyEvaluationInterface
{
    protected const GLOBAL_FUNCTIONS = ['if'];

    public function __construct(private $evaluateExpression) {}

    public function requiresRawResult(string $functionName): bool
    {
        return true;
    }

    public function handle(string $functionName, array $args): mixed
    {
        if (isset($args['condition']) && isset($args['then']) && isset($args['else'])) {
            $condition  = $args['condition'];
            $trueValue  = $args['then'];
            $falseValue = $args['else'];
        } elseif (count($args) >= 3) {
            $condition  = $args[0];
            $trueValue  = $args[1];
            $falseValue = $args[2];
        } else {
            return null;
        }

        $conditionResult = ($this->evaluateExpression)($condition);

        $isTruthy = $this->isTruthy($conditionResult);

        return $isTruthy ? $trueValue : $falseValue;
    }

    public function getModuleNamespace(): SassModule
    {
        return SassModule::CSS;
    }

    private function isTruthy(mixed $value): bool
    {
        if ($value === null || $value === false) {
            return false;
        }

        if (is_string($value) && strtolower($value) === 'null') {
            return false;
        }

        return true;
    }
}
