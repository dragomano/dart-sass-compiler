<?php

declare(strict_types=1);

namespace DartSass\Compilers\Nodes;

use Closure;
use DartSass\Exceptions\CompilationException;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\ErrorNode;
use DartSass\Utils\LoggerInterface;

class ErrorNodeCompiler extends AbstractNodeCompiler
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Closure $evaluateExpression,
        private readonly Closure $formatValue,
        private readonly Closure $getOptions
    ) {}

    protected function getNodeClass(): string
    {
        return ErrorNode::class;
    }

    protected function compileNode(
        ErrorNode|AstNode $node,
        string $parentSelector = '',
        int $nestingLevel = 0
    ): string {
        $value = ($this->evaluateExpression)($node->expression);
        $formattedValue = ($this->formatValue)($value);
        $options = ($this->getOptions)();

        $this->logger->error($formattedValue, [
            'file' => $options['sourceFile'] ?? 'unknown',
            'line' => $node->line ?? 0,
        ]);

        throw new CompilationException(sprintf(
            'Error at %s:%d: %s',
            $options['sourceFile'] ?? 'unknown',
            $node->line ?? 0,
            $formattedValue
        ));
    }
}
