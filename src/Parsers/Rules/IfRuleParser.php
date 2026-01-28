<?php

declare(strict_types=1);

namespace DartSass\Parsers\Rules;

use Closure;
use DartSass\Exceptions\SyntaxException;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\ConditionNode;
use DartSass\Parsers\Nodes\IfNode;
use DartSass\Parsers\Nodes\OperationNode;
use DartSass\Parsers\Tokens\TokenStreamInterface;

use function array_merge;
use function sprintf;

class IfRuleParser extends AtRuleParser
{
    public function __construct(
        TokenStreamInterface     $stream,
        private readonly Closure $parseExpression,
        private readonly Closure $parseBlock
    ) {
        parent::__construct($stream);
    }

    /**
     * @throws SyntaxException
     */
    public function parse(): AstNode
    {
        $token = $this->consume('at_rule');

        if ($token->value !== '@if') {
            throw new SyntaxException(
                sprintf('Expected @if rule, got %s', $token->value),
                $token->line,
                $token->column
            );
        }

        $condition = $this->parseCondition();

        if (! $this->peek('brace_open')) {
            $currentToken = $this->currentToken();

            $tokenInfo = $currentToken
                ? sprintf('Token: type=%s, value=%s', $currentToken->type, $currentToken->value ?? 'null')
                : 'No current token';

            throw new SyntaxException(
                sprintf('Expected "{" to start @if block. %s', $tokenInfo),
                $currentToken ? $currentToken->line : $token->line,
                $currentToken ? $currentToken->column : $token->column
            );
        }

        $this->consume('brace_open');

        $block = ($this->parseBlock)();
        $body  = array_merge($block['declarations'], $block['nested']);

        $elseBlock = $this->parseElseChain();

        return new IfNode($condition, $body, $elseBlock, $token->line);
    }

    private function parseCondition(): AstNode
    {
        $expression = $this->parseFullConditionExpression();

        return new ConditionNode($expression, 1);
    }

    private function parseFullConditionExpression(): AstNode
    {
        $left = ($this->parseExpression)();

        while ($this->currentToken() && $this->currentToken()->type === 'logical_operator') {
            $operatorToken = $this->currentToken();

            $operator = $operatorToken->value;

            $this->incrementTokenIndex();

            $right = ($this->parseExpression)();

            $left = new OperationNode($left, $operator, $right, $operatorToken->line ?? 1);

            if ($this->peek('brace_open')) {
                break;
            }
        }

        return $left;
    }

    /**
     * @throws SyntaxException
     */
    private function parseElseChain(): ?array
    {
        if (! $this->peek('at_rule') || $this->currentToken()->value !== '@else') {
            return null;
        }

        $this->consume('at_rule');

        if ($this->peek('identifier') && $this->currentToken()->value === 'if') {
            $this->consume('identifier');

            $condition = $this->parseCondition();

            if (! $this->peek('brace_open')) {
                throw new SyntaxException(
                    'Expected "{" to start @else if block',
                    $this->currentToken() ? $this->currentToken()->line : 0,
                    $this->currentToken() ? $this->currentToken()->column : 0
                );
            }

            $this->consume('brace_open');

            $block = ($this->parseBlock)();
            $body  = array_merge($block['declarations'], $block['nested']);

            $nextElse = $this->parseElseChain();

            return [new IfNode($condition, $body, $nextElse)];
        } else {
            if (! $this->peek('brace_open')) {
                throw new SyntaxException(
                    'Expected "{" to start @else block',
                    $this->currentToken() ? $this->currentToken()->line : 0,
                    $this->currentToken() ? $this->currentToken()->column : 0
                );
            }

            $this->consume('brace_open');

            $block = ($this->parseBlock)();

            return array_merge($block['declarations'], $block['nested']);
        }
    }
}
