<?php

declare(strict_types=1);

namespace DartSass\Parsers\Rules;

use Closure;
use DartSass\Exceptions\SyntaxException;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\DebugNode;
use DartSass\Parsers\Tokens\TokenStreamInterface;

use function sprintf;

class DebugRuleParser extends AtRuleParser
{
    public function __construct(
        TokenStreamInterface     $stream,
        private readonly Closure $parseExpression
    ) {
        parent::__construct($stream);
    }

    /**
     * @throws SyntaxException
     */
    public function parse(): AstNode
    {
        $token = $this->consume('at_rule');

        if ($token->value !== '@debug') {
            throw new SyntaxException(
                sprintf('Expected @debug rule, got %s', $token->value),
                $token->line,
                $token->column
            );
        }

        $expression = ($this->parseExpression)();

        if ($this->peek('semicolon')) {
            $this->consume('semicolon');
        }

        return new DebugNode($expression, $token->line);
    }
}
