<?php

declare(strict_types=1);

namespace DartSass\Evaluators;

use DartSass\Compilers\CompilerContext;
use DartSass\Parsers\Nodes\AstNode;

use function is_array;

abstract class AbstractEvaluator implements EvaluatorInterface
{
    public function __construct(protected CompilerContext $context) {}

    protected function evaluateNode(AstNode $node): mixed
    {
        return $this->context->expressionEvaluator->evaluate($node);
    }

    protected function formatValue(mixed $value): string
    {
        return $this->context->resultFormatter->format($value);
    }

    protected function isStructuredValue(mixed $value): bool
    {
        return is_array($value) && isset($value['value']);
    }
}
