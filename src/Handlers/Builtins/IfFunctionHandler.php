<?php

declare(strict_types=1);

namespace DartSass\Handlers\Builtins;

use DartSass\Handlers\SassModule;
use DartSass\Utils\ValueComparator;

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
        if (
            ! $this->hasArgument($args, 0, ['condition'])
            || ! $this->hasArgument($args, 1, ['if-true', 'then'])
            || ! $this->hasArgument($args, 2, ['if-false', 'else'])
        ) {
            return null;
        }

        $condition  = $this->getArgument($args, 0, ['condition']);
        $trueValue  = $this->getArgument($args, 1, ['if-true', 'then']);
        $falseValue = $this->getArgument($args, 2, ['if-false', 'else']);

        $conditionResult = ($this->expression)($condition);

        $isTruthy = ValueComparator::isTruthy($conditionResult);

        return $isTruthy ? $trueValue : $falseValue;
    }

    public function getModuleNamespace(): SassModule
    {
        return SassModule::CSS;
    }
}
