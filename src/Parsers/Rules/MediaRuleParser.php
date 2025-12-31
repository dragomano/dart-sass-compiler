<?php

declare(strict_types=1);

namespace DartSass\Parsers\Rules;

use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\MediaNode;

use function in_array;
use function preg_match;
use function trim;

class MediaRuleParser extends AtRuleParser
{
    public function parse(): AstNode
    {
        $token = $this->consume('at_rule');
        $query = $this->parseMediaQuery();

        $this->consume('brace_open');

        $body = $this->parseBlock();

        return $this->createNode($query, $body, $token->line);
    }

    protected function createNode(string $query, array $body, int $line): AstNode
    {
        return new MediaNode($query, $body, $line);
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
            } elseif ($this->peek('operator') && in_array($this->currentToken()->value, ['&', '.', '#'], true)) {
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

    private function parseMediaQuery(): string
    {
        $query = '';

        while ($this->currentToken() && ! $this->peek('brace_open') && ! $this->peek('newline')) {
            $currentToken = $this->currentToken();

            if ($query !== '' && $this->shouldAddSpace($currentToken, $query)) {
                $query .= ' ';
            }

            $query .= $currentToken->value;
            $this->incrementTokenIndex();
        }

        return trim($query);
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

    private function shouldAddSpace($currentToken, string $query): bool
    {
        if (preg_match('/\s$/', $query) === 1) {
            return false;
        }

        if ($currentToken->type === 'logical_operator') {
            return true;
        }

        if ($currentToken->type === 'identifier' && preg_match('/\d$/', $query) === 1) {
            return false;
        }

        if ($currentToken->type === 'identifier') {
            if (preg_match('/\b(and|or)\b$/', $query) === 1) {
                return true;
            }

            if (preg_match('/[a-zA-Z0-9_-]+$/', $query) === 1) {
                return true;
            }
        }

        if ($currentToken->type === 'paren_open') {
            return preg_match('/\b(and|or)\b$/', $query) === 1;
        }

        if ($currentToken->type === 'colon') {
            return false;
        }

        if ($currentToken->type === 'number') {
            if (preg_match('/:$/', $query) === 1) {
                return true;
            }

            if (preg_match('/\)$/', $query) === 1) {
                return true;
            }
        }

        return false;
    }
}
