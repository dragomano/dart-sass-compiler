<?php

declare(strict_types=1);

namespace DartSass\Compilers\Nodes;

use Closure;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\WarnNode;
use DartSass\Utils\LoggerInterface;

class WarnNodeCompiler extends AbstractNodeCompiler
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Closure $evaluateExpression,
        private readonly Closure $formatValue,
        private readonly Closure $getOptions
    ) {}

    protected function getNodeClass(): string
    {
        return WarnNode::class;
    }

    protected function compileNode(
        WarnNode|AstNode $node,
        string $parentSelector = '',
        int $nestingLevel = 0
    ): string {
        $value = ($this->evaluateExpression)($node->expression);
        $formattedValue = ($this->formatValue)($value);
        $options = ($this->getOptions)();

        $this->logger->debug($formattedValue, [
            'file' => $options['sourceFile'] ?? 'unknown',
            'line' => $node->line ?? 0,
        ]);

        return '';
    }
}
