<?php

declare(strict_types=1);

namespace DartSass\Parsers\Rules;

use DartSass\Exceptions\SyntaxException;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\WhileNode;

use DartSass\Parsers\SassParser;

use function array_merge;
use function sprintf;

class WhileRuleParser extends AtRuleParser
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
            if (! ($this->parser instanceof SassParser)) {
                throw new SyntaxException(
                    'Expected "{" to start @while block',
                    $this->currentToken() ? $this->currentToken()->line : $token->line,
                    $this->currentToken() ? $this->currentToken()->column : $token->column
                );
            }
        }

        // Handle both SCSS and SASS syntax
        if ($this->peek('brace_open')) {
            $this->consume('brace_open');
            $block = $this->parseBlock();
        } else {
            while ($this->currentToken() && ($this->peek('whitespace') || $this->peek('newline'))) {
                $this->incrementTokenIndex();
            }

            $block = $this->parseSassBlock();
        }

        $body = array_merge($block['declarations'], $block['nested']);

        return new WhileNode($condition, $body, $token->line);
    }

    protected function parseBlock(): array
    {
        $declarations = $nested = [];

        while ($this->currentToken() && ! $this->peek('brace_close')) {
            while ($this->peek('whitespace')) {
                $this->incrementTokenIndex();
            }

            if ($this->peek('brace_close')) {
                break;
            }

            if ($this->peek('at_rule')) {
                $token = $this->currentToken();
                if ($token->value === '@include') {
                    $nested[] = $this->parser->parseInclude();
                } else {
                    $nested[] = $this->parser->parseAtRule();
                }
            } elseif ($this->peek('variable')) {
                $nested[] = $this->parser->parseVariable();
            } elseif ($this->peek('selector')) {
                $nested[] = $this->parser->parseRule();
            } elseif ($this->peek('operator') && in_array($this->currentToken()->value, ['&', '.', '#'])) {
                $nested[] = $this->parser->parseRule();
            } elseif ($this->peek('identifier')) {
                $this->handleIdentifierInBlock($declarations, $nested);
            } else {
                $this->handleOtherTokensInBlock($declarations, $nested);
            }
        }

        $this->consume('brace_close');

        return ['declarations' => $declarations, 'nested' => $nested];
    }

    private function parseSassBlock(): array
    {
        $declarations = $nested = [];

        while ($this->currentToken()) {
            while ($this->peek('whitespace') || $this->peek('newline')) {
                $this->incrementTokenIndex();
            }

            if ($this->currentToken() === null) {
                break;
            }

            // Handle nested rules and declarations
            if ($this->peek('selector') || $this->peek('identifier')) {
                // Use the base parser's parseRule method for nested rules
                $nested[] = $this->parser->parseRule();
            } elseif ($this->peek('operator') && in_array($this->currentToken()->value, ['&', '.', '#'])) {
                $nested[] = $this->parser->parseRule();
            } elseif ($this->peek('at_rule')) {
                $nested[] = $this->parser->parseAtRule();
            } elseif ($this->peek('variable')) {
                $nested[] = $this->parser->parseVariable();
            } elseif ($this->peek('colon')) {
                // This is a CSS property at while level
                $declarations[] = $this->parser->parseDeclaration();
            } else {
                // Skip unknown tokens
                $this->incrementTokenIndex();
            }
        }

        return ['declarations' => $declarations, 'nested' => $nested];
    }

    private function handleIdentifierInBlock(array &$declarations, array &$nested): void
    {
        $savedIndex = $this->getTokenIndex();

        $this->incrementTokenIndex();

        while ($this->peek('whitespace')) {
            $this->incrementTokenIndex();
        }

        if ($this->peek('colon')) {
            $isSelector = $this->isPseudoClassSelector();

            $this->setTokenIndex($savedIndex);

            $isSelector ? $nested[] = $this->parser->parseRule() : $declarations[] = $this->parser->parseDeclaration();
        } else {
            $this->parser->setTokenIndex($savedIndex);

            $nested[] = $this->parser->parseRule();
        }
    }

    private function handleOtherTokensInBlock(array &$declarations, array &$nested): void
    {
        $savedIndex = $this->getTokenIndex();

        $this->incrementTokenIndex();

        while ($this->peek('whitespace')) {
            $this->incrementTokenIndex();
        }

        $this->setTokenIndex($savedIndex);

        $this->peek('colon')
            ? $declarations[] = $this->parser->parseDeclaration()
            : $nested[] = $this->parser->parseRule();
    }

    private function isPseudoClassSelector(): bool
    {
        $this->incrementTokenIndex();

        while ($this->peek('whitespace')) {
            $this->incrementTokenIndex();
        }

        if ($this->peek('function')) {
            return $this->checkFunctionPseudoClass();
        }

        if ($this->peek('identifier')) {
            $this->incrementTokenIndex();

            while ($this->peek('whitespace')) {
                $this->incrementTokenIndex();
            }

            return $this->peek('brace_open');
        }

        return false;
    }

    private function checkFunctionPseudoClass(): bool
    {
        $parenLevel = 1;

        $this->incrementTokenIndex();

        if ($this->peek('paren_open')) {
            $this->consume('paren_open');

            while ($this->currentToken() && $parenLevel > 0) {
                if ($this->peek('paren_open')) {
                    $parenLevel++;
                } elseif ($this->peek('paren_close')) {
                    $parenLevel--;
                }

                $this->incrementTokenIndex();
            }
        }

        while ($this->peek('whitespace')) {
            $this->incrementTokenIndex();
        }

        return $this->peek('brace_open');
    }
}
