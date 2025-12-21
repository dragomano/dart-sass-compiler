<?php

declare(strict_types=1);

namespace DartSass\Parsers\Rules;

use DartSass\Exceptions\SyntaxException;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\EachNode;

use function array_merge;
use function sprintf;

class EachRuleParser extends AtRuleParser
{
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

        while ($this->peek('whitespace')) {
            $this->incrementTokenIndex();
        }

        if ($this->peek('variable')) {
            $varToken = $this->consume('variable');
            $variable = $varToken->value;
        } else {
            throw new SyntaxException(
                'Expected variable for @each loop',
                $token->line,
                $token->column
            );
        }

        while ($this->currentToken() && $this->peek('whitespace')) {
            $this->incrementTokenIndex();
        }

        if (! $this->peek('identifier') || $this->currentToken()->value !== 'in') {
            throw new SyntaxException(
                'Expected "in" keyword in @each rule',
                $this->currentToken() ? $this->currentToken()->line : $token->line,
                $this->currentToken() ? $this->currentToken()->column : $token->column
            );
        }

        $this->consume('identifier');

        while ($this->currentToken() && $this->peek('whitespace')) {
            $this->incrementTokenIndex();
        }

        $condition = $this->parser->parseExpression();

        while ($this->currentToken() && $this->peek('whitespace')) {
            $this->incrementTokenIndex();
        }

        if (! $this->peek('brace_open')) {
            throw new SyntaxException(
                'Expected "{" to start @each block',
                $this->currentToken() ? $this->currentToken()->line : $token->line,
                $this->currentToken() ? $this->currentToken()->column : $token->column
            );
        }

        $this->consume('brace_open');

        $block = $this->parser->parseBlock();
        $body = array_merge($block['declarations'], $block['nested']);

        return new EachNode($variable, $condition, $body, $token->line);
    }
}
