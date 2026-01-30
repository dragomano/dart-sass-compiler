<?php

declare(strict_types=1);

namespace DartSass\Parsers\Rules;

use DartSass\Exceptions\SyntaxException;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\ForwardNode;

use function ltrim;
use function sprintf;
use function trim;

class ForwardRuleParser extends AtRuleParser
{
    /**
     * @throws SyntaxException
     */
    public function parse(): AstNode
    {
        $token = $this->consume('at_rule');

        if ($token->value !== '@forward') {
            throw new SyntaxException(
                sprintf('Expected @forward rule, got %s', $token->value),
                $token->line,
                $token->column
            );
        }

        if ($this->peek('string')) {
            $pathToken = $this->consume('string');

            $path = trim(trim($pathToken->value, '"\''));
        } else {
            throw new SyntaxException(
                'Expected string path for @forward',
                $token->line,
                $token->column
            );
        }

        $namespace = null;

        $config = $hide = $show = [];

        while ($this->currentToken() && ! $this->peek('semicolon')) {
            if ($this->peek('identifier')) {
                $keyword = $this->consume('identifier')->value;

                switch ($keyword) {
                    case 'as':
                        if ($this->peek('identifier')) {
                            $namespace = $this->consume('identifier')->value;
                        }

                        break;

                    case 'with':
                        $config = $this->parseConfig();

                        break;

                    case 'hide':
                        $hide = $this->parseVariableList();

                        break;

                    case 'show':
                        $show = $this->parseVariableList();

                        break;

                    default:
                        throw new SyntaxException(
                            sprintf('Unexpected keyword %s in @forward rule', $keyword),
                            $this->currentToken()->line,
                            $this->currentToken()->column
                        );
                }
            }
        }

        $this->consume('semicolon');

        return new ForwardNode(
            $path,
            $namespace,
            $config,
            $hide,
            $show,
            $token->line
        );
    }

    /**
     * @throws SyntaxException
     */
    private function parseConfig(): array
    {
        $config = [];

        if (! $this->peek('paren_open')) {
            throw new SyntaxException(
                'Expected ( after with keyword',
                $this->currentToken()->line,
                $this->currentToken()->column
            );
        }

        $this->consume('paren_open');

        while (! $this->peek('paren_close')) {
            if ($this->peek('variable')) {
                $keyToken = $this->consume('variable');

                $key = ltrim($keyToken->value, '$');

                if (! $this->peek('colon')) {
                    throw new SyntaxException(
                        'Expected : after key in config',
                        $this->currentToken()->line,
                        $this->currentToken()->column
                    );
                }

                $this->consume('colon');

                $value = $this->consumeUntilCommaOrParenClose();

                $config[$key] = trim($value);

                if ($this->currentToken() && $this->peek('operator') && $this->currentToken()->value === ',') {
                    $this->consume('operator');
                }
            }
        }

        $this->consume('paren_close');

        return $config;
    }

    private function parseVariableList(): array
    {
        $variables = [];
        $hasParens = $this->peek('paren_open');

        if ($hasParens) {
            $this->consume('paren_open');
        }

        while (! $this->peek('semicolon') && (! $hasParens || ! $this->peek('paren_close'))) {
            if ($this->peek('variable')) {
                $varToken = $this->consume('variable');

                $variables[] = $varToken->value;

                if ($this->currentToken() && $this->peek('operator') && $this->currentToken()->value === ',') {
                    $this->consume('operator');
                }
            } else {
                break;
            }
        }

        if ($hasParens) {
            $this->consume('paren_close');
        }

        return $variables;
    }

    private function consumeUntilCommaOrParenClose(): string
    {
        $value = '';

        while ($this->currentToken()) {
            if ($this->peek('paren_close')) {
                break;
            }

            if ($this->peek('operator') && $this->currentToken()->value === ',') {
                break;
            }

            $token  = $this->currentToken();
            $value .= $token->value;

            $this->incrementTokenIndex();
        }

        return $value;
    }
}
