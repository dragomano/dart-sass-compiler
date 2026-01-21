<?php

declare(strict_types=1);

namespace DartSass\Handlers\Builtins;

use DartSass\Handlers\SassModule;
use DartSass\Utils\ValueComparator;

use function count;

class IfFunctionHandler extends BaseModuleHandler implements LazyEvaluationInterface
{
    protected const GLOBAL_FUNCTIONS = ['if'];

    public function __construct(private $expression) {}

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

        $conditionResult = ($this->expression)($condition);

        $isTruthy = ValueComparator::isTruthy($conditionResult);

        return $isTruthy ? $trueValue : $falseValue;
    }

    public function getModuleNamespace(): SassModule
    {
        return SassModule::CSS;
    }
}
