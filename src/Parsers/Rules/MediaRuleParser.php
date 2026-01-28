<?php

declare(strict_types=1);

namespace DartSass\Parsers\Rules;

use Closure;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\MediaNode;
use DartSass\Parsers\Tokens\TokenStreamInterface;

use function preg_match;
use function trim;

class MediaRuleParser extends AtRuleParser
{
    public function __construct(
        TokenStreamInterface $stream,
        protected Closure    $parseAtRule,
        protected Closure    $parseInclude,
        protected Closure    $parseVariable,
        protected Closure    $parseRule,
        protected Closure    $parseDeclaration
    ) {
        parent::__construct($stream);
    }

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

        $token = $this->currentToken();

        while ($token && ! $this->peek('brace_close')) {
            if ($this->peek('at_rule')) {

                if ($token->value === '@include') {
                    $nested[] = ($this->parseInclude)();
                } else {
                    $nested[] = ($this->parseAtRule)();
                }
            } elseif ($this->peek('variable')) {
                $nested[] = ($this->parseVariable)();
            } elseif ($this->peek('operator')) {
                $nested[] = ($this->parseRule)();
            } elseif ($this->peek('identifier')) {
                $this->handleIdentifierInBlock($declarations, $nested);
            } else {
                $this->handleOtherTokensInBlock($nested);
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

        if ($this->peek('colon')) {
            $isSelector = $this->isPseudoClassSelector();

            $this->setTokenIndex($savedIndex);

            $isSelector
                ? $nested[] = ($this->parseRule)()
                : $declarations[] = ($this->parseDeclaration)();
        } else {
            $this->setTokenIndex($savedIndex);

            $nested[] = ($this->parseRule)();
        }
    }

    private function handleOtherTokensInBlock(array &$nested): void
    {
        $savedIndex = $this->getTokenIndex();

        $this->incrementTokenIndex();
        $this->setTokenIndex($savedIndex);

        $nested[] = ($this->parseRule)();
    }

    private function isPseudoClassSelector(): bool
    {
        $this->incrementTokenIndex();

        if ($this->peek('function')) {
            return $this->checkFunctionPseudoClass();
        }

        if ($this->peek('identifier')) {
            $this->incrementTokenIndex();

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
                if ($this->peek('paren_close')) {
                    $parenLevel--;
                }

                $this->incrementTokenIndex();
            }
        }

        return $this->peek('brace_open');
    }

    private function shouldAddSpace($currentToken, string $query): bool
    {
        if ($currentToken->type === 'logical_operator') {
            return true;
        }

        if ($currentToken->type === 'identifier' && preg_match('/\d$/', $query) === 1) {
            return false;
        }

        if ($currentToken->type === 'identifier') {
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
        }

        return false;
    }
}
