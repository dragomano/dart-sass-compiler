<?php

declare(strict_types=1);

namespace DartSass\Parsers\Rules;

trait BlockParsingHelper
{
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

            $token = $this->currentToken();
        }

        return ['declarations' => $declarations, 'nested' => $nested];
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
}
