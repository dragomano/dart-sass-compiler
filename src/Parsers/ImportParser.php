<?php

declare(strict_types=1);

namespace DartSass\Parsers;

use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\AtRuleNode;
use DartSass\Parsers\Tokens\Token;
use DartSass\Utils\StringFormatter;

use function in_array;
use function preg_replace;
use function str_contains;
use function str_ends_with;
use function str_starts_with;
use function trim;

class ImportParser extends AbstractParser
{
    public function parse(): AstNode
    {
        $token = $this->consume('at_rule');

        $this->skipWhitespace();

        $rawValue        = $this->captureImportValue();
        $normalizedValue = $this->normalizeImportPath($rawValue);

        $this->consume('semicolon');

        return new AtRuleNode('@import', $normalizedValue, null, $token->line);
    }

    private function captureImportValue(): string
    {
        $value = '';

        $previousToken = null;
        while ($this->currentToken() && ! $this->peek('semicolon')) {
            $currentToken = $this->currentToken();

            if ($previousToken && $this->shouldAddSpace($previousToken, $currentToken)) {
                $value .= ' ';
            }

            $value         .= $currentToken->value;
            $previousToken  = $currentToken;

            $this->advanceToken();
        }

        return trim($value);
    }

    private function shouldAddSpace(Token $previous, Token $current): bool
    {
        if (in_array($current->type, ['colon', 'comma', 'semicolon', 'paren_close'], true)) {
            return false;
        }

        if ($current->type === 'paren_open') {
            return in_array($previous->type, ['identifier', 'logical_operator'], true);
        }

        if ($previous->type === 'paren_open') {
            return false;
        }

        if ($previous->type === 'colon') {
            return $current->type === 'identifier';
        }

        return true;
    }

    private function normalizeImportPath(string $value): string
    {
        if (str_starts_with($value, 'url(')) {
            $content = preg_replace('/^url\((.*)\)$/s', '$1', $value);
            $content = trim($content);

            if (str_starts_with($content, "'") && str_ends_with($content, "'")) {
                $content = StringFormatter::forceQuoteString(trim($content, "'"));
            } elseif (! str_starts_with($content, '"') && ! str_ends_with($content, '"')) {
                $content = StringFormatter::forceQuoteString($content);
            }

            return 'url(' . $content . ')';
        }

        if (! str_contains($value, ' ')) {
            return trim($value, '"\'');
        }

        return $value;
    }
}
