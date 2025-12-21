<?php

declare(strict_types=1);

namespace DartSass\Parsers\Rules;

use DartSass\Exceptions\SyntaxException;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\WhileNode;

use function array_merge;
use function sprintf;

class WhileRuleParser extends MediaRuleParser
{
    /**
     * @throws SyntaxException
     */
    public function parse(): AstNode
    {
        $token = $this->consume('at_rule');

        if ($token->value !== '@while') {
            throw new SyntaxException(
                sprintf('Expected @while rule, got %s', $token->value),
                $token->line,
                $token->column
            );
        }

        while ($this->peek('whitespace')) {
            $this->incrementTokenIndex();
        }

        $condition = $this->parser->parseExpression();

        while ($this->currentToken() && $this->peek('whitespace')) {
            $this->incrementTokenIndex();
        }

        if (! $this->peek('brace_open')) {
            throw new SyntaxException(
                'Expected "{" to start @while block',
                $this->currentToken() ? $this->currentToken()->line : $token->line,
                $this->currentToken() ? $this->currentToken()->column : $token->column
            );
        }

        $this->consume('brace_open');
        $block = $this->parseBlock();

        $body = array_merge($block['declarations'], $block['nested']);

        return new WhileNode($condition, $body, $token->line);
    }
}
