<?php

declare(strict_types=1);

namespace DartSass\Parsers\Rules;

use Closure;
use DartSass\Exceptions\SyntaxException;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\EachNode;
use DartSass\Parsers\Tokens\TokenStreamInterface;

use function array_merge;
use function sprintf;

class EachRuleParser extends AtRuleParser
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

        if ($token->value !== '@each') {
            throw new SyntaxException(
                sprintf('Expected @each rule, got %s', $token->value),
                $token->line,
                $token->column
            );
        }

        $variables = [];

        do {
            if ($this->peek('variable')) {
                $varToken = $this->consume('variable');

                $variables[] = $varToken->value;
            } else {
                throw new SyntaxException(
                    'Expected variable for @each loop',
                    $token->line,
                    $token->column
                );
            }

            if ($this->peek('operator') && $this->currentToken()->value === ',') {
                $this->consume('operator');
            } else {
                break;
            }
        } while (true);

        $currentToken = $this->currentToken();

        if (! $this->peek('identifier') || $currentToken->value !== 'in') {
            throw new SyntaxException(
                'Expected "in" keyword in @each rule',
                $currentToken ? $currentToken->line : $token->line,
                $currentToken ? $currentToken->column : $token->column
            );
        }

        $this->consume('identifier');

        $condition = ($this->parseExpression)();

        $currentToken = $this->currentToken();

        if (! $this->peek('brace_open')) {
            throw new SyntaxException(
                'Expected "{" to start @each block',
                $currentToken ? $currentToken->line : $token->line,
                $currentToken ? $currentToken->column : $token->column
            );
        }

        $this->consume('brace_open');

        $block = ($this->parseBlock)();
        $body  = array_merge($block['declarations'], $block['nested']);

        return new EachNode($variables, $condition, $body, $token->line);
    }
}
