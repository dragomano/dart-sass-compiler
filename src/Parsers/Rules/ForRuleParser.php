<?php

declare(strict_types=1);

namespace DartSass\Parsers\Rules;

use Closure;
use DartSass\Exceptions\SyntaxException;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\ForNode;
use DartSass\Parsers\Tokens\TokenStreamInterface;

use function array_merge;
use function sprintf;

class ForRuleParser extends AtRuleParser
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

        if ($token->value !== '@for') {
            throw new SyntaxException(
                sprintf('Expected @for rule, got %s', $token->value),
                $token->line,
                $token->column
            );
        }

        if ($this->peek('variable')) {
            $varToken = $this->consume('variable');
            $variable = $varToken->value;
        } else {
            throw new SyntaxException(
                'Expected variable for @for loop',
                $token->line,
                $token->column
            );
        }

        $currentToken = $this->currentToken();

        if (! $this->peek('identifier') || $currentToken->value !== 'from') {
            throw new SyntaxException(
                'Expected "from" keyword in @for rule',
                $currentToken ? $currentToken->line : $token->line,
                $currentToken ? $currentToken->column : $token->column
            );
        }

        $this->consume('identifier');

        $from = ($this->parseExpression)();

        $currentToken = $this->currentToken();

        if (
            ! $this->peek('identifier')
            || ($currentToken->value !== 'to' && $currentToken->value !== 'through')
        ) {
            throw new SyntaxException(
                'Expected "to" or "through" keyword in @for rule',
                $currentToken ? $currentToken->line : $token->line,
                $currentToken ? $currentToken->column : $token->column
            );
        }

        $limitKeyword = $this->consume('identifier')->value;
        $inclusive    = $limitKeyword === 'through';

        $to = ($this->parseExpression)();

        $currentToken = $this->currentToken();

        if (! $this->peek('brace_open')) {
            throw new SyntaxException(
                'Expected "{" to start @for block',
                $currentToken ? $currentToken->line : $token->line,
                $currentToken ? $currentToken->column : $token->column
            );
        }

        $this->consume('brace_open');

        $block = ($this->parseBlock)();
        $body  = array_merge($block['declarations'], $block['nested']);

        return new ForNode(
            $variable,
            $from,
            $to,
            $inclusive,
            $body,
            $token->line
        );
    }
}
