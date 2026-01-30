<?php

declare(strict_types=1);

namespace DartSass\Parsers\Rules;

use Closure;
use DartSass\Exceptions\SyntaxException;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\AtRuleNode;
use DartSass\Parsers\Tokens\TokenStreamInterface;

use function sprintf;
use function trim;

class GenericAtRuleParser extends AtRuleParser
{
    public function __construct(
        TokenStreamInterface     $stream,
        private readonly Closure $parseAtRule,
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
        $name  = $token->value;
        $value = '';
        $body  = null;

        while ($this->currentToken() && ! $this->peek('brace_open') && ! $this->peek('semicolon')) {
            $currentToken = $this->currentToken();

            $value .= $currentToken->value;

            $this->incrementTokenIndex();
        }

        $value = trim($value);

        if ($this->peek('brace_open')) {
            $this->consume('brace_open');

            $body = $this->parseBlock();
        } elseif ($this->peek('semicolon')) {
            $this->consume('semicolon');
        } else {
            throw new SyntaxException(
                sprintf('Expected "{" or ";" after %s', $name),
                $this->currentToken() ? $this->currentToken()->line : $token->line,
                $this->currentToken() ? $this->currentToken()->column : $token->column
            );
        }

        return new AtRuleNode($name, $value, $body, $token->line);
    }

    private function parseBlock(): array
    {
        $declarations = $nested = [];

        while ($this->currentToken() && ! $this->peek('brace_close')) {
            if ($this->peek('at_rule')) {
                $nested[] = ($this->parseAtRule)();
            } elseif ($this->peek('variable')) {
                $nested[] = ($this->parseVariable)();
            } elseif ($this->peek('operator')) {
                $nested[] = ($this->parseRule)();
            } elseif ($this->peek('identifier')) {
                $savedIndex = $this->getTokenIndex();

                $this->incrementTokenIndex();

                if ($this->peek('colon')) {
                    $this->setTokenIndex($savedIndex);

                    $declarations[] = ($this->parseDeclaration)();
                } else {
                    $this->setTokenIndex($savedIndex);

                    $nested[] = ($this->parseRule)();
                }
            }
        }

        $this->consume('brace_close');

        return ['declarations' => $declarations, 'nested' => $nested];
    }
}
