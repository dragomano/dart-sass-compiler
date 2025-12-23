<?php

declare(strict_types=1);

namespace DartSass\Parsers\Rules;

use DartSass\Exceptions\SyntaxException;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\AtRuleNode;

use function in_array;
use function sprintf;
use function trim;

class GenericAtRuleParser extends AtRuleParser
{
    /**
     * @throws SyntaxException
     */
    public function parse(): AstNode
    {
        $token = $this->consume('at_rule');
        $name  = $token->value;
        $value = '';
        $body  = null;

        while ($this->peek('whitespace')) {
            $this->incrementTokenIndex();
        }

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
        $declarations = [];
        $nested = [];

        while ($this->currentToken() && ! $this->peek('brace_close')) {
            while ($this->peek('whitespace')) {
                $this->incrementTokenIndex();
            }

            if ($this->peek('brace_close')) {
                break;
            }

            if ($this->peek('at_rule')) {
                $nested[] = $this->parser->parseAtRule();
            } elseif ($this->peek('variable')) {
                $nested[] = $this->parser->parseVariable();
            } elseif ($this->peek('selector')) {
                $nested[] = $this->parser->parseRule();
            } elseif ($this->peek('operator') && in_array($this->currentToken()->value, ['&', '.', '#'], true)) {
                $nested[] = $this->parser->parseRule();
            } elseif ($this->peek('identifier')) {
                $savedIndex = $this->getTokenIndex();
                $this->incrementTokenIndex();

                while ($this->peek('whitespace')) {
                    $this->incrementTokenIndex();
                }

                if ($this->peek('colon')) {
                    $this->parser->setTokenIndex($savedIndex);
                    $declarations[] = $this->parser->parseDeclaration();
                } else {
                    $this->parser->setTokenIndex($savedIndex);
                    $nested[] = $this->parser->parseRule();
                }
            } else {
                $declarations[] = $this->parser->parseDeclaration();
            }
        }

        $this->consume('brace_close');

        return ['declarations' => $declarations, 'nested' => $nested];
    }
}
