<?php

declare(strict_types=1);

namespace DartSass\Parsers\Rules;

use Closure;
use DartSass\Exceptions\SyntaxException;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\WhileNode;
use DartSass\Parsers\Tokens\TokenStreamInterface;

use function array_merge;
use function sprintf;

class WhileRuleParser extends AtRuleParser
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

        if ($token->value !== '@while') {
            throw new SyntaxException(
                sprintf('Expected @while rule, got %s', $token->value),
                $token->line,
                $token->column
            );
        }

        $condition = ($this->parseExpression)();

        $currentToken = $this->currentToken();

        if (! $this->peek('brace_open')) {
            throw new SyntaxException(
                'Expected "{" to start @while block',
                $currentToken ? $currentToken->line : $token->line,
                $currentToken ? $currentToken->column : $token->column
            );
        }

        $this->consume('brace_open');

        $block = ($this->parseBlock)();
        $body  = array_merge($block['declarations'], $block['nested']);

        return new WhileNode($condition, $body, $token->line);
    }
}
