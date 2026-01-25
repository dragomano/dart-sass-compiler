<?php

declare(strict_types=1);

namespace DartSass\Utils;

use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\CommentNode;
use DartSass\Parsers\Nodes\IdentifierNode;
use DartSass\Parsers\Nodes\NodeType;
use DartSass\Parsers\Nodes\OperationNode;
use DartSass\Parsers\Nodes\VariableNode;
use DartSass\Values\CalcValue;

readonly class ResultFormatter implements ResultFormatterInterface
{
    public function __construct(private ValueFormatter $valueFormatter) {}

    public function format(mixed $result): string
    {
        if ($result instanceof AstNode) {
            return $this->formatAstNode($result);
        }

        return $this->valueFormatter->format($result);
    }

    private function formatAstNode(AstNode $node): string
    {
        if ($node instanceof OperationNode) {
            $leftFormatted  = $this->format($node->left);
            $rightFormatted = $this->format($node->right);

            $operator = $node->operator;

            return (string) new CalcValue($leftFormatted, $operator, $rightFormatted);
        }

        if ($node instanceof VariableNode) {
            return '$' . $node->name;
        }

        if ($node instanceof IdentifierNode || $node instanceof CommentNode) {
            return $node->value;
        }

        if ($node->type === NodeType::COLOR || $node->type === NodeType::HEX_COLOR) {
            return (string) $node;
        }

        return '[' . $node->type->value . ']';
    }
}
