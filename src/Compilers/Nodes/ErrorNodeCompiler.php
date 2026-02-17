<?php

declare(strict_types=1);

namespace DartSass\Compilers\Nodes;

use DartSass\Compilers\CompilerEngineInterface;
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
        CompilerEngineInterface $engine,
        string $parentSelector = '',
        int $nestingLevel = 0
    ): string {
        $value = $engine->evaluateExpression($node->expression);
        $formattedValue = $engine->getResultFormatter()->format($value);
        $options = $engine->getOptions();

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
