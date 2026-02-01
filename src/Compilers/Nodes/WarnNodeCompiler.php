<?php

declare(strict_types=1);

namespace DartSass\Compilers\Nodes;

use DartSass\Compilers\CompilerContext;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\NodeType;
use DartSass\Parsers\Nodes\WarnNode;
use DartSass\Utils\LoggerInterface;

class WarnNodeCompiler extends AbstractNodeCompiler
{
    public function __construct(private readonly LoggerInterface $logger) {}

    protected function getNodeClass(): string
    {
        return WarnNode::class;
    }

    protected function getNodeType(): NodeType
    {
        return NodeType::WARN;
    }

    protected function compileNode(
        WarnNode|AstNode $node,
        CompilerContext $context,
        string $parentSelector = '',
        int $nestingLevel = 0
    ): string {
        $value = $context->engine->evaluateExpression($node->expression);
        $formattedValue = $context->resultFormatter->format($value);

        $this->logger->debug($formattedValue, [
            'file' => $context->options['sourceFile'] ?? 'unknown',
            'line' => $node->line ?? 0,
        ]);

        return '';
    }
}
