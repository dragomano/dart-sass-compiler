<?php

declare(strict_types=1);

namespace DartSass\Compilers\Nodes;

use DartSass\Compilers\CompilerEngineInterface;
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
        CompilerEngineInterface $engine,
        string $parentSelector = '',
        int $nestingLevel = 0
    ): string {
        $value = $engine->evaluateExpression($node->expression);
        $formattedValue = $engine->getResultFormatter()->format($value);
        $options = $engine->getOptions();

        $this->logger->debug($formattedValue, [
            'file' => $options['sourceFile'] ?? 'unknown',
            'line' => $node->line ?? 0,
        ]);

        return '';
    }
}
