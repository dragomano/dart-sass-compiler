<?php

declare(strict_types=1);

namespace DartSass\Compilers\Nodes;

use DartSass\Compilers\CompilerContext;
use DartSass\Exceptions\CompilationException;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\ErrorNode;
use DartSass\Parsers\Nodes\NodeType;
use DartSass\Utils\LoggerInterface;

class ErrorNodeCompiler extends AbstractNodeCompiler
{
    public function __construct(private readonly LoggerInterface $logger) {}

    protected function getNodeClass(): string
    {
        return ErrorNode::class;
    }

    protected function getNodeType(): NodeType
    {
        return NodeType::ERROR;
    }

    protected function compileNode(
        ErrorNode|AstNode $node,
        CompilerContext $context,
        string $parentSelector = '',
        int $nestingLevel = 0
    ): string {
        $value = $context->engine->evaluateExpression($node->expression);
        $formattedValue = $context->resultFormatter->format($value);

        $this->logger->error($formattedValue, [
            'file' => $context->options['sourceFile'] ?? 'unknown',
            'line' => $node->line ?? 0,
        ]);

        throw new CompilationException(sprintf(
            'Error at %s:%d: %s',
            $context->options['sourceFile'] ?? 'unknown',
            $node->line ?? 0,
            $formattedValue
        ));
    }
}
