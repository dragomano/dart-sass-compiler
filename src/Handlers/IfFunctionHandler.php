<?php

declare(strict_types=1);

namespace DartSass\Handlers;

use function count;

class IfFunctionHandler implements LazyEvaluationHandlerInterface
{
    private const BUILTIN_FUNCTIONS = [
        'if' => true,
    ];

    public function __construct(private $evaluateExpression) {}

    public function canHandle(string $functionName): bool
    {
        return isset(self::BUILTIN_FUNCTIONS[$functionName]);
    }

    public function requiresRawResult(string $functionName): bool
    {
        return $functionName === 'if';
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

    public function getSupportedFunctions(): array
    {
        return array_keys(self::BUILTIN_FUNCTIONS);
    }

    public function getModuleNamespace(): string
    {
        return 'builtin';
    }
}
