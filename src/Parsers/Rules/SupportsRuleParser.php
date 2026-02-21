<?php

declare(strict_types=1);

namespace DartSass\Parsers\Rules;

use Closure;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\SupportsNode;
use DartSass\Parsers\Tokens\TokenStreamInterface;

use function in_array;
use function preg_match;
use function trim;

class SupportsRuleParser extends AtRuleParser
{
    use BlockParsingHelper;

    public function __construct(
        TokenStreamInterface $stream,
        private readonly Closure $parseAtRule,
        private readonly Closure $parseVariable,
        private readonly Closure $parseRule,
        private readonly Closure $parseDeclaration
    ) {
        parent::__construct($stream);
    }

    public function parse(): AstNode
    {
        $this->consume('at_rule');

        $query = $this->parseSupportsCondition();

        if ($this->peek('brace_open')) {
            $this->consume('brace_open');

            $body = $this->parseBlock();
        } elseif ($this->peek('semicolon')) {
            $this->consume('semicolon');

            $body = [];
        } else {
            $body = [];
        }

        return new SupportsNode($query, $body, $this->currentToken()?->line ?? 1);
    }

    private function parseSupportsCondition(): string
    {
        $condition = '';

        while ($this->currentToken() && ! $this->peek('brace_open') && ! $this->peek('semicolon')) {
            $currentToken = $this->currentToken();
            $nextToken    = $this->getStream()->peek(1);

            if ($condition !== '' && $this->shouldAddSpaceBefore($currentToken, $condition)) {
                $condition .= ' ';
            }

            $condition .= $currentToken->value;

            if ($nextToken && $this->shouldAddSpaceAfter($currentToken, $nextToken)) {
                $condition .= ' ';
            }

            $this->incrementTokenIndex();
        }

        return trim($condition);
    }

    private function shouldAddSpaceBefore($currentToken, string $condition): bool
    {
        if ($currentToken->type === 'logical_operator') {
            return true;
        }

        if ($currentToken->type === 'identifier' && preg_match('/\d$/', $condition) === 1) {
            return false;
        }

        if ($currentToken->type === 'identifier') {
            if (preg_match('/[a-zA-Z0-9_-]+$/', $condition) === 1) {
                return true;
            }
        }

        if ($currentToken->type === 'paren_open') {
            return preg_match('/\b(and|or|not)\b$/', $condition) === 1;
        }

        if ($currentToken->type === 'colon') {
            return false;
        }

        return false;
    }

    private function shouldAddSpaceAfter($currentToken, $nextToken): bool
    {
        if ($currentToken->type === 'colon') {
            $prevToken = $this->previousToken();

            if ($prevToken && $prevToken->type === 'paren_open') {
                return false;
            }

            return true;
        }

        if ($currentToken->type === 'number' && $nextToken->type === 'number') {
            return true;
        }

        if ($currentToken->type === 'identifier' && $currentToken->value === 'not' && $nextToken->type === 'function' && $nextToken->value === 'selector') {
            return true;
        }

        if ($currentToken->type === 'identifier' && $currentToken->value === 'not' && $nextToken->type === 'identifier' && preg_match('/^[a-zA-Z]/', $nextToken->value)) {
            return true;
        }

        if ($currentToken->type === 'identifier' && $currentToken->value === 'not' && $nextToken->type === 'paren_open') {
            return true;
        }

        if ($currentToken->type === 'operator' && (in_array($currentToken->value, ['>', '<', '~'], true))) {
            return true;
        }

        if ($currentToken->type === 'identifier' && $nextToken->type === 'operator' && $nextToken->value !== ',') {
            return true;
        }

        if ($currentToken->type === 'operator' && $currentToken->value === ',' && ($nextToken->type === 'identifier' || $nextToken->type === 'selector')) {
            return true;
        }

        return false;
    }

    private function parseBlock(): array
    {
        $declarations = [];
        $nested       = [];

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
