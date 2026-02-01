<?php

declare(strict_types=1);

namespace DartSass\Parsers\Rules;

use Closure;
use DartSass\Exceptions\SyntaxException;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\AtRootNode;
use DartSass\Parsers\Tokens\TokenStreamInterface;

class AtRootRuleParser extends AtRuleParser
{
    use BlockParsingHelper;

    public function __construct(
        TokenStreamInterface     $stream,
        private readonly Closure $parseAtRule,
        private readonly Closure $parseInclude,
        private readonly Closure $parseVariable,
        private readonly Closure $parseRule,
        private readonly Closure $parseDeclaration
    ) {
        parent::__construct($stream);
    }

    /**
     * @throws SyntaxException
     */
    public function parse(): AstNode
    {
        $token = $this->consume('at_rule');

        if ($token->value !== '@at-root') {
            throw new SyntaxException(
                sprintf('Expected @at-root, got %s', $token->value),
                $token->line,
                $token->column
            );
        }

        $without = $with = null;

        if ($this->peek('paren_open')) {
            $this->consume('paren_open');
            $this->consume('identifier');

            $keyword = $this->previousToken()->value;

            $this->consume('colon');
            $this->consume('identifier');

            $value = $this->previousToken()->value;

            if ($keyword === 'without') {
                $without = $value;
            } elseif ($keyword === 'with') {
                $with = $value;
            }

            $this->consume('paren_close');
        }

        $this->consume('brace_open');

        $body = $this->parseBlock();

        $this->consume('brace_close');

        $line = $this->getStream()->getToken($this->getTokenIndex() - 1)->line;

        return new AtRootNode($without, $with, $body, $line);
    }
}
